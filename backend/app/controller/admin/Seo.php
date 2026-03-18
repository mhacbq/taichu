<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\service\SchemaInspector;
use think\Request;
use think\facade\Db;

/**
 * 后台 SEO 管理控制器
 */
class Seo extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    protected const DEFAULT_PAGE_SIZE = 20;
    
    protected function canManageSeo(): bool
    {
        return $this->hasAdminPermission('config_manage')
            || $this->hasAdminRole('admin');
    }

    protected const MAX_PAGE_SIZE = 100;
    protected const SEO_CHANGE_FREQUENCIES = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];
    protected const SEO_ENGINES = ['baidu', 'bing', '360', 'sogou'];
    protected const SEO_SUBMIT_TYPES = ['url', 'sitemap'];

    /**
     * 获取 SEO 配置列表
     */
    public function seoConfigList(Request $request)
    {
        if (!$this->canManageSeo()) {
            return $this->error('无权限查看SEO配置', 403);
        }


        try {
            $pagination = $this->normalizePagination(
                $request->get('page', 1),
                $request->get('pageSize', self::DEFAULT_PAGE_SIZE),
                self::DEFAULT_PAGE_SIZE,
                self::MAX_PAGE_SIZE
            );
            $page = $pagination['page'];
            $pageSize = $pagination['pageSize'];
            $keyword = trim((string) $request->get('keyword', ''));
            $isActive = $request->get('is_active', '');

            $configTable = $this->resolveSeoTable('config');
            $submissionTable = $this->resolveSeoTable('submissions');
            if ($configTable === null) {
                return $this->success([
                    'list' => [],
                    'total' => 0,
                    'sitemap' => [
                        'lastModified' => date('Y-m-d H:i:s'),
                        'urlCount' => 0,
                        'fileSize' => '0.1 KB',
                        'baiduIndexed' => false,
                    ],
                    'submitStatus' => $this->formatSeoSubmitStatus(),
                ], '获取成功');
            }

            $query = Db::table($configTable)
                ->order('priority', 'desc')
                ->order('updated_at', 'desc');

            if ($keyword !== '') {
                $escapedKeyword = addcslashes($keyword, '%_\\');
                $query->whereLike('route|title|description', '%' . $escapedKeyword . '%');
            }

            if ($isActive !== '') {
                $activeValue = (int) $isActive;
                if (!in_array($activeValue, [0, 1], true)) {
                    return $this->error('SEO状态无效', 400);
                }
                $query->where('is_active', $activeValue);
            }

            $total = $query->count();
            $rows = $query->page($page, $pageSize)->select()->toArray();
            $activeRows = Db::table($configTable)
                ->where('is_active', 1)
                ->field('route,updated_at')
                ->select()
                ->toArray();

            $lastModified = '';
            foreach ($activeRows as $row) {
                if (($row['updated_at'] ?? '') > $lastModified) {
                    $lastModified = (string) $row['updated_at'];
                }
            }

            $xmlSize = 128;
            foreach ($activeRows as $row) {
                $xmlSize += strlen((string) ($row['route'] ?? '')) + 128;
            }
            $fileSize = number_format($xmlSize / 1024, 1) . ' KB';
            $baiduIndexed = false;
            if ($submissionTable !== null) {
                $baiduIndexed = Db::table($submissionTable)
                    ->where('engine', 'baidu')
                    ->where('type', 'sitemap')
                    ->where('status', 'success')
                    ->count() > 0;
            }

            return $this->success([
                'list' => array_map([$this, 'formatSeoConfigRow'], $rows),
                'total' => $total,
                'sitemap' => [
                    'lastModified' => $lastModified ?: date('Y-m-d H:i:s'),
                    'urlCount' => count($activeRows),
                    'fileSize' => $fileSize,
                    'baiduIndexed' => $baiduIndexed,

                ],
                'submitStatus' => $this->formatSeoSubmitStatus(),
            ], '获取成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('seo_config_list', $e, '获取SEO配置失败，请稍后重试', [
                'params' => $request->get(),
            ]);
        }
    }

    /**
     * 保存 SEO 配置
     */
    public function saveSeoConfig(Request $request, int $id = 0)
    {
        if (!$this->canManageSeo()) {
            return $this->error('无权限维护SEO配置', 403);
        }


        $data = $request->isPut() ? $request->put() : $request->post();
        $id = max($id, (int) ($data['id'] ?? 0));
        $route = trim((string) ($data['route'] ?? ''));
        $title = trim((string) ($data['title'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $keywords = $this->normalizeSeoKeywords($data['keywords'] ?? []);
        $image = trim((string) ($data['image'] ?? ''));
        $robots = trim((string) ($data['robots'] ?? 'index,follow'));
        $ogType = trim((string) ($data['og_type'] ?? ($data['ogType'] ?? 'website')));
        $canonical = trim((string) ($data['canonical'] ?? ''));
        $priority = filter_var($data['priority'] ?? 0.5, FILTER_VALIDATE_FLOAT);
        $priority = $priority === false ? 0.5 : max(0, min(1, (float) $priority));
        $changefreq = trim((string) ($data['changefreq'] ?? 'weekly'));
        $isActive = isset($data['is_active']) ? (int) $data['is_active'] : (isset($data['isActive']) ? (int) $data['isActive'] : 1);

        if ($route === '' || !str_starts_with($route, '/')) {
            return $this->error('页面路由必须以 / 开头', 400);
        }
        if ($title === '' || $description === '') {
            return $this->error('SEO标题和描述不能为空', 400);
        }
        if (empty($keywords)) {
            return $this->error('至少需要填写一个关键词', 400);
        }
        if (!in_array($robots, ['index,follow', 'noindex,follow', 'noindex,nofollow'], true)) {
            return $this->error('Robots配置无效', 400);
        }
        if (!in_array($changefreq, self::SEO_CHANGE_FREQUENCIES, true)) {
            return $this->error('更新频率无效', 400);
        }
        if (!in_array($isActive, [0, 1], true)) {
            return $this->error('SEO状态无效', 400);
        }

        try {
            $configTable = $this->resolveSeoTable('config');
            if ($configTable === null) {
                return $this->error('SEO配置表不存在，请先执行 database/20260318_create_seo_tables.sql', 500);
            }

            $columns = $this->getSeoTableColumns($configTable);
            $routeColumn = $this->resolveSeoConfigColumn($columns, ['route', 'page_route', 'path']);
            if ($routeColumn === null) {
                return $this->error('SEO配置表缺少页面路由字段，无法保存', 500);
            }

            $duplicateQuery = Db::table($configTable)->where($routeColumn, $route);
            if ($id > 0) {
                $duplicateQuery->where('id', '<>', $id);
            }
            if ($duplicateQuery->find()) {
                return $this->error('该页面路由的SEO配置已存在', 400);
            }

            $existing = $id > 0 ? Db::table($configTable)->where('id', $id)->find() : null;
            if ($id > 0 && !$existing) {
                return $this->error('SEO配置不存在', 404);
            }

            $payload = $this->buildSeoConfigPayload([
                'route' => $route,
                'title' => $title,
                'description' => $description,
                'keywords' => $keywords,
                'image' => $image,
                'robots' => $robots,
                'og_type' => $ogType === '' ? 'website' : $ogType,
                'canonical' => $canonical,
                'priority' => $priority,
                'changefreq' => $changefreq,
                'is_active' => $isActive,
            ], $columns, $existing === null);

            if (empty($payload)) {
                return $this->error('SEO配置表字段不兼容，无法保存当前数据', 500);
            }

            if ($existing) {
                Db::table($configTable)->where('id', $id)->update($payload);
            } else {
                $id = (int) Db::table($configTable)->insertGetId($payload);
            }

            $saved = Db::table($configTable)->where('id', $id)->find();

            $afterData = $saved ? $this->formatSeoConfigRow($saved) : ['id' => $id, 'route' => $route];

            $this->logOperation('save_seo_config', 'config', [
                'target_id' => $id,
                'target_type' => 'seo_config',
                'detail' => ($existing ? '更新' : '新增') . 'SEO配置: ' . $route,
                'before_data' => $existing ?: [],
                'after_data' => $afterData,
            ]);

            return $this->success($afterData, '保存成功');
        } catch (\Throwable $e) {

            return $this->respondSystemException('seo_config_save', $e, '保存SEO配置失败，请稍后重试', [
                'id' => $id,
                'route' => $route,
            ]);
        }
    }

    /**
     * 删除 SEO 配置
     */
    public function deleteSeoConfig(int $id)
    {
        if (!$this->canManageSeo()) {
            return $this->error('无权限删除SEO配置', 403);
        }


        try {
            $configTable = $this->resolveSeoTable('config') ?? 'tc_seo_config';
            $existing = Db::table($configTable)->where('id', $id)->find();
            if (!$existing) {
                return $this->error('SEO配置不存在', 404);
            }

            Db::table($configTable)->where('id', $id)->delete();


            $this->logOperation('delete_seo_config', 'config', [
                'target_id' => $id,
                'target_type' => 'seo_config',
                'detail' => '删除SEO配置: ' . ($existing['route'] ?? ''),
                'before_data' => $existing,
            ]);

            return $this->success(null, '删除成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('seo_config_delete', $e, '删除SEO配置失败，请稍后重试', [
                'id' => $id,
            ]);
        }
    }

    /**
     * SEO 统计数据
     */
    public function seoStats(Request $request)
    {
        if (!$this->canManageSeo()) {
            return $this->error('无权限查看SEO统计', 403);
        }


        try {
            $pagination = $this->normalizePagination(
                $request->get('page', 1),
                $request->get('pageSize', self::DEFAULT_PAGE_SIZE),
                self::DEFAULT_PAGE_SIZE,
                self::MAX_PAGE_SIZE
            );
            $page = $pagination['page'];
            $pageSize = $pagination['pageSize'];
            $pageKeyword = trim((string) $request->get('keyword', ''));
            $indexedTable = $this->resolveSeoTable('indexed_pages');
            $keywordTable = $this->resolveSeoTable('keywords');
            $trafficTable = $this->resolveSeoTable('traffic_daily');
            $configTable = $this->resolveSeoTable('config');
            $submissionTable = $this->resolveSeoTable('submissions');

            $baiduIndexed = 0;
            $bingIndexed = 0;
            $baiduRecent = 0;
            $baiduPrevious = 0;
            $bingRecent = 0;
            $bingPrevious = 0;
            $pageTotal = 0;
            $pageRows = [];
            $pendingPages = 0;
            if ($indexedTable !== null) {
                $baiduIndexed = (int) Db::table($indexedTable)->where('baidu_status', 'indexed')->count();
                $bingIndexed = (int) Db::table($indexedTable)->where('bing_status', 'indexed')->count();
                $baiduRecent = (int) Db::table($indexedTable)
                    ->where('baidu_status', 'indexed')
                    ->where('indexed_at', '>=', date('Y-m-d H:i:s', strtotime('-7 days')))
                    ->count();
                $baiduPrevious = (int) Db::table($indexedTable)
                    ->where('baidu_status', 'indexed')
                    ->where('indexed_at', '>=', date('Y-m-d H:i:s', strtotime('-14 days')))
                    ->where('indexed_at', '<', date('Y-m-d H:i:s', strtotime('-7 days')))
                    ->count();
                $bingRecent = (int) Db::table($indexedTable)
                    ->where('bing_status', 'indexed')
                    ->where('indexed_at', '>=', date('Y-m-d H:i:s', strtotime('-7 days')))
                    ->count();
                $bingPrevious = (int) Db::table($indexedTable)
                    ->where('bing_status', 'indexed')
                    ->where('indexed_at', '>=', date('Y-m-d H:i:s', strtotime('-14 days')))
                    ->where('indexed_at', '<', date('Y-m-d H:i:s', strtotime('-7 days')))
                    ->count();

                $pageQuery = Db::table($indexedTable)->order('updated_at', 'desc');
                if ($pageKeyword !== '') {
                    $escapedKeyword = addcslashes($pageKeyword, '%_\\');
                    $pageQuery->whereLike('url|title|page_route', '%' . $escapedKeyword . '%');
                }
                $pageTotal = $pageQuery->count();
                $pageRows = $pageQuery->page($page, $pageSize)->select()->toArray();

                $allIndexedRows = Db::table($indexedTable)->field('baidu_status,bing_status')->select()->toArray();
                foreach ($allIndexedRows as $indexedRow) {
                    if (($indexedRow['baidu_status'] ?? '') !== 'indexed' || ($indexedRow['bing_status'] ?? '') !== 'indexed') {
                        $pendingPages++;
                    }
                }
            }

            $allKeywordRows = [];
            if ($keywordTable !== null) {
                $allKeywordRows = Db::table($keywordTable)
                    ->where('is_target', 1)
                    ->order('baidu_rank', 'asc')
                    ->order('bing_rank', 'asc')
                    ->select()
                    ->toArray();
            }
            $keywordTotal = count($allKeywordRows);
            $keywordTop10 = 0;
            foreach ($allKeywordRows as $row) {
                $baiduRank = (int) ($row['baidu_rank'] ?? 0);
                $bingRank = (int) ($row['bing_rank'] ?? 0);
                if (($baiduRank >= 1 && $baiduRank <= 10) || ($bingRank >= 1 && $bingRank <= 10)) {
                    $keywordTop10++;
                }
            }
            $keywordRows = array_slice($allKeywordRows, 0, 50);

            $trafficCurrent = 0;
            $trafficPrevious = 0;
            if ($trafficTable !== null) {
                $trafficCurrent = (int) Db::table($trafficTable)
                    ->where('stat_date', '>=', date('Y-m-d', strtotime('-30 days')))
                    ->sum('organic_sessions');
                $trafficPrevious = (int) Db::table($trafficTable)
                    ->where('stat_date', '>=', date('Y-m-d', strtotime('-60 days')))
                    ->where('stat_date', '<', date('Y-m-d', strtotime('-30 days')))
                    ->sum('organic_sessions');
            }

            $configRows = $configTable !== null ? Db::table($configTable)->select()->toArray() : [];
            $submissions = $submissionTable !== null
                ? Db::table($submissionTable)
                    ->order('submitted_at', 'desc')
                    ->limit(10)
                    ->select()
                    ->toArray()
                : [];

            return $this->success([
                'stats' => [
                    'baidu' => [
                        'indexed' => $baiduIndexed,
                        'trend' => $this->calculatePercentageTrend($baiduRecent, $baiduPrevious),
                    ],
                    'bing' => [
                        'indexed' => $bingIndexed,
                        'trend' => $this->calculatePercentageTrend($bingRecent, $bingPrevious),
                    ],
                    'keywords' => [
                        'total' => $keywordTotal,
                        'top10' => $keywordTop10,
                    ],
                    'traffic' => [
                        'organic' => $trafficCurrent,
                        'trend' => $this->calculatePercentageTrend($trafficCurrent, $trafficPrevious),
                    ],
                ],
                'keywords' => array_map([$this, 'formatSeoKeywordRow'], $keywordRows),
                'pages' => [
                    'list' => array_map([$this, 'formatSeoIndexedPageRow'], $pageRows),
                    'total' => $pageTotal,
                    'page' => $page,
                    'pageSize' => $pageSize,
                ],
                'suggestions' => $this->buildSeoSuggestions($configRows, $keywordTotal, $keywordTop10, $pendingPages),
                'submissions' => $submissions,
            ], '获取成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('seo_stats', $e, '获取SEO统计失败，请稍后重试', [
                'params' => $request->get(),
            ]);
        }
    }

    /**
     * 获取 robots.txt 配置
     */
    public function seoRobots()
    {
        if (!$this->canManageSeo()) {
            return $this->error('无权限查看robots配置', 403);
        }


        try {
            $robotsTable = $this->resolveSeoTable('robots');
            if ($robotsTable === null) {
                return $this->success([
                    'content' => '',
                    'list' => [],
                    'updated_at' => '',
                ], '获取成功');
            }

            $rows = Db::table($robotsTable)
                ->where('is_active', 1)
                ->order('sort_order', 'asc')
                ->order('id', 'asc')
                ->select()
                ->toArray();

            return $this->success([
                'content' => $this->buildRobotsContent($rows),
                'list' => array_map(function (array $row): array {
                    return [
                        'id' => (int) ($row['id'] ?? 0),
                        'user_agent' => $row['user_agent'],
                        'rules' => $this->normalizeRobotsRulePayload($row['rules'] ?? ''),
                        'crawl_delay' => (int) ($row['crawl_delay'] ?? 0),
                        'sitemap_url' => $row['sitemap_url'] ?? '',
                        'sort_order' => (int) ($row['sort_order'] ?? 0),
                        'updated_at' => $row['updated_at'] ?? '',
                    ];
                }, $rows),
                'updated_at' => empty($rows) ? '' : max(array_column($rows, 'updated_at')),
            ], '获取成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('seo_robots_get', $e, '获取robots配置失败，请稍后重试');
        }
    }

    /**
     * 保存 robots.txt 配置
     */
    public function saveSeoRobots(Request $request)
    {
        if (!$this->canManageSeo()) {
            return $this->error('无权限保存robots配置', 403);
        }


        $content = trim((string) ($request->put('content', '') ?: $request->post('content', '')));
        if ($content === '') {
            return $this->error('robots.txt 内容不能为空', 400);
        }

        $parsedEntries = $this->parseRobotsContent($content);
        if (empty($parsedEntries)) {
            return $this->error('robots.txt 内容格式无效', 400);
        }

        try {
            $robotsTable = $this->resolveSeoTable('robots') ?? 'tc_seo_robots';
            $beforeRows = Db::table($robotsTable)->order('sort_order', 'asc')->select()->toArray();
            $insertData = [];

            $now = date('Y-m-d H:i:s');
            foreach ($parsedEntries as $index => $entry) {
                $insertData[] = [
                    'user_agent' => $entry['user_agent'],
                    'rules' => json_encode($entry['rules'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'crawl_delay' => (int) $entry['crawl_delay'],
                    'sitemap_url' => $entry['sitemap_url'],
                    'is_active' => 1,
                    'sort_order' => $index,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            Db::startTrans();
            Db::table($robotsTable)->delete();
            Db::table($robotsTable)->insertAll($insertData);

            Db::commit();

            $this->logOperation('save_seo_robots', 'config', [
                'target_type' => 'seo_robots',
                'detail' => '更新robots.txt配置',
                'before_data' => $beforeRows,
                'after_data' => $insertData,
            ]);

            return $this->success([
                'count' => count($insertData),
                'content' => $content,
            ], '保存成功');
        } catch (\Throwable $e) {
            Db::rollback();
            return $this->respondSystemException('seo_robots_save', $e, '保存robots配置失败，请稍后重试');
        }
    }

    /**
     * 提交 SEO 收录请求
     */
    public function seoSubmit(Request $request)
    {
        if (!$this->canManageSeo()) {
            return $this->error('无权限执行收录提交', 403);
        }


        $engine = trim((string) $request->post('engine', ''));
        $type = trim((string) $request->post('type', 'sitemap'));
        $url = trim((string) $request->post('url', ''));

        if (!in_array($engine, self::SEO_ENGINES, true)) {
            return $this->error('搜索引擎类型无效', 400);
        }
        if (!in_array($type, self::SEO_SUBMIT_TYPES, true)) {
            return $this->error('提交类型无效', 400);
        }
        if ($url === '') {
            $url = rtrim((string) env('SITE_URL', 'https://taichu.chat'), '/') . ($type === 'sitemap' ? '/sitemap.xml' : '/');
        }

        try {
            $submissionTable = $this->resolveSeoTable('submissions') ?? 'tc_seo_submissions';
            $payload = [
                'engine' => $engine,
                'type' => $type,
                'url' => $url,
                'status' => 'success',
                'response' => '已记录提交请求，待搜索平台同步结果',
                'submitted_at' => date('Y-m-d H:i:s'),
                'completed_at' => date('Y-m-d H:i:s'),
            ];
            $id = (int) Db::table($submissionTable)->insertGetId($payload);


            $this->logOperation('submit_seo', 'config', [
                'target_id' => $id,
                'target_type' => 'seo_submission',
                'detail' => sprintf('提交%s到%s', $type, $engine),
                'after_data' => $payload,
            ]);

            return $this->success(['id' => $id] + $payload, '提交成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('seo_submit', $e, '提交收录请求失败，请稍后重试', [
                'engine' => $engine,
                'type' => $type,
                'url' => $url,
            ]);
        }
    }

    /**
     * 解析 SEO 相关数据表
     */
    protected function resolveSeoTable(string $type): ?string
    {
        return match ($type) {
            'config' => $this->resolveFirstExistingTable(['tc_seo_config', 'seo_config']),
            'keywords' => $this->resolveFirstExistingTable(['tc_seo_keywords', 'seo_keywords']),
            'indexed_pages' => $this->resolveFirstExistingTable(['tc_seo_indexed_pages', 'seo_indexed_pages']),
            'submissions' => $this->resolveFirstExistingTable(['tc_seo_submissions', 'seo_submissions']),
            'traffic_daily' => $this->resolveFirstExistingTable(['tc_seo_traffic_daily', 'seo_traffic_daily']),
            'robots' => $this->resolveFirstExistingTable(['tc_seo_robots', 'seo_robots']),
            default => null,
        };

    }

    /**
     * 返回首个存在的数据表名
     */
    protected function resolveFirstExistingTable(array $tables): ?string
    {
        foreach ($tables as $table) {
            if (SchemaInspector::tableExists((string) $table)) {
                return (string) $table;
            }
        }

        return null;
    }

    protected function getSeoTableColumns(string $table): array
    {
        return SchemaInspector::getTableColumns($table);
    }

    protected function resolveSeoConfigColumn(array $columns, array $candidates): ?string
    {
        foreach ($candidates as $column) {
            if (isset($columns[$column])) {
                return $column;
            }
        }

        return null;
    }

    protected function assignSeoConfigValue(array &$payload, array $columns, array $candidates, mixed $value): void
    {
        $column = $this->resolveSeoConfigColumn($columns, $candidates);
        if ($column !== null) {
            $payload[$column] = $value;
        }
    }

    protected function buildSeoConfigPayload(array $data, array $columns, bool $isCreate): array
    {
        $payload = [];
        $now = date('Y-m-d H:i:s');

        $this->assignSeoConfigValue($payload, $columns, ['route', 'page_route', 'path'], (string) ($data['route'] ?? ''));
        $this->assignSeoConfigValue($payload, $columns, ['title'], (string) ($data['title'] ?? ''));
        $this->assignSeoConfigValue($payload, $columns, ['description', 'desc'], (string) ($data['description'] ?? ''));
        $this->assignSeoConfigValue($payload, $columns, ['keywords', 'keyword', 'meta_keywords'], $this->encodeSeoKeywordsForStorage($data['keywords'] ?? [], $columns));
        $this->assignSeoConfigValue($payload, $columns, ['image', 'share_image', 'og_image'], (string) ($data['image'] ?? ''));
        $this->assignSeoConfigValue($payload, $columns, ['robots'], (string) ($data['robots'] ?? 'index,follow'));
        $this->assignSeoConfigValue($payload, $columns, ['og_type', 'ogType'], (string) ($data['og_type'] ?? 'website'));
        $this->assignSeoConfigValue($payload, $columns, ['canonical', 'canonical_url'], (string) ($data['canonical'] ?? ''));
        $this->assignSeoConfigValue($payload, $columns, ['priority'], (float) ($data['priority'] ?? 0.5));
        $this->assignSeoConfigValue($payload, $columns, ['changefreq', 'change_freq'], (string) ($data['changefreq'] ?? 'weekly'));
        $this->assignSeoConfigValue($payload, $columns, ['is_active', 'status'], (int) ($data['is_active'] ?? 1));
        $this->assignSeoConfigValue($payload, $columns, ['updated_at', 'update_time'], $now);
        if ($isCreate) {
            $this->assignSeoConfigValue($payload, $columns, ['created_at', 'create_time'], $now);
        }

        return $payload;
    }

    protected function encodeSeoKeywordsForStorage(array $keywords, array $columns): string
    {
        $keywords = $this->normalizeSeoKeywords($keywords);
        if (isset($columns['keyword']) || isset($columns['meta_keywords'])) {
            return implode(',', $keywords);
        }

        $encoded = json_encode($keywords, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $encoded === false ? '[]' : $encoded;
    }

    /**
     * 格式化 SEO 配置行数据
     */

    protected function formatSeoConfigRow(array $row): array
    {
        $keywords = $row['keywords'] ?? ($row['keyword'] ?? ($row['meta_keywords'] ?? ''));
        $image = $row['image'] ?? ($row['share_image'] ?? ($row['og_image'] ?? ''));
        $canonical = $row['canonical'] ?? ($row['canonical_url'] ?? '');
        $changefreq = $row['changefreq'] ?? ($row['change_freq'] ?? 'weekly');
        $updatedAt = $row['updated_at'] ?? ($row['update_time'] ?? ($row['created_at'] ?? ($row['create_time'] ?? '')));
        $normalizedActive = (int) ($row['is_active'] ?? ($row['status'] ?? 1));

        return [
            'id' => (int) ($row['id'] ?? 0),
            'route' => $row['route'] ?? ($row['page_route'] ?? ($row['path'] ?? '')),
            'title' => $row['title'] ?? '',
            'description' => $row['description'] ?? ($row['desc'] ?? ''),
            'keywords' => $this->normalizeSeoKeywords($keywords),
            'image' => $image,
            'robots' => $row['robots'] ?? 'index,follow',
            'ogType' => $row['og_type'] ?? ($row['ogType'] ?? 'website'),
            'canonical' => $canonical,
            'priority' => isset($row['priority']) ? (float) $row['priority'] : 0.5,
            'changefreq' => $changefreq,
            'isActive' => $normalizedActive,
            'is_active' => $normalizedActive,
            'updated_at' => $updatedAt,
        ];
    }


    /**
     * 格式化 SEO 关键词数据
     */
    protected function formatSeoKeywordRow(array $row): array
    {
        $categoryMap = [
            'core' => '核心词',
            'long' => '长尾词',
            'related' => '相关词',
            'brand' => '品牌词',
        ];

        $baiduRank = (int) ($row['baidu_rank'] ?? 0);
        $bingRank = (int) ($row['bing_rank'] ?? 0);
        $ranks = array_filter([$baiduRank, $bingRank]);
        $bestRank = empty($ranks) ? 0 : min($ranks);
        $trend = $bestRank === 0 ? 0 : ($bestRank <= 20 ? 1 : -1);

        return [
            'id' => (int) ($row['id'] ?? 0),
            'keyword' => $row['keyword'] ?? '',
            'category' => $categoryMap[$row['category'] ?? 'general'] ?? ($row['category'] ?? '通用词'),
            'baiduRank' => $baiduRank,
            'bingRank' => $bingRank,
            'searchVolume' => (int) ($row['search_volume'] ?? 0),
            'trend' => $trend,
        ];
    }

    /**
     * 格式化 SEO 页面收录数据
     */
    protected function formatSeoIndexedPageRow(array $row): array
    {
        return [
            'id' => (int) ($row['id'] ?? 0),
            'pageRoute' => $row['page_route'] ?? '',
            'url' => $row['url'] ?? '',
            'title' => $row['title'] ?? '',
            'baiduStatus' => $row['baidu_status'] ?? 'pending',
            'bingStatus' => $row['bing_status'] ?? 'pending',
            'indexedAt' => $row['indexed_at'] ?? '',
            'updatedAt' => $row['updated_at'] ?? '',
            'traffic' => (int) ($row['organic_traffic'] ?? 0),
        ];
    }

    /**
     * 解析数组或 JSON 输入
     */
    protected function normalizeJsonArrayInput($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value)) {
            return [];
        }

        $value = trim($value);
        if ($value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * 规范化 SEO 关键词输入
     */
    protected function normalizeSeoKeywords($keywords): array
    {
        if (is_string($keywords)) {
            $decoded = json_decode($keywords, true);
            if (is_array($decoded)) {
                $keywords = $decoded;
            } else {
                $keywords = preg_split('/[,，]/', $keywords) ?: [];
            }
        }

        if (!is_array($keywords)) {
            return [];
        }

        $keywords = array_map(static function ($item): string {
            return trim((string) $item);
        }, $keywords);

        return array_values(array_filter(array_unique($keywords), static function (string $item): bool {
            return $item !== '';
        }));
    }

    /**
     * 解析 robots 规则
     */
    protected function normalizeRobotsRulePayload($value): array
    {
        $rows = $this->normalizeJsonArrayInput($value);
        $result = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $type = strtolower(trim((string) ($row['type'] ?? '')));
            $path = trim((string) ($row['path'] ?? ''));
            if ($type === '' || $path === '') {
                continue;
            }

            $result[] = [
                'type' => $type,
                'path' => $path,
            ];
        }

        return $result;
    }

    /**
     * 解析 robots 文本
     */
    protected function parseRobotsContent(string $content): array
    {
        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];
        $entries = [];
        $current = null;

        $flush = static function (?array $entry, array &$target): void {
            if (!$entry || empty($entry['user_agent'])) {
                return;
            }
            $target[] = $entry;
        };

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (preg_match('/^User-agent:\s*(.+)$/i', $line, $matches)) {
                $flush($current, $entries);
                $current = [
                    'user_agent' => trim($matches[1]),
                    'rules' => [],
                    'crawl_delay' => 0,
                    'sitemap_url' => '',
                ];
                continue;
            }

            if ($current === null) {
                continue;
            }

            if (preg_match('/^(Allow|Disallow):\s*(.*)$/i', $line, $matches)) {
                $current['rules'][] = [
                    'type' => strtolower($matches[1]),
                    'path' => trim($matches[2]) === '' ? '/' : trim($matches[2]),
                ];
                continue;
            }

            if (preg_match('/^Crawl-delay:\s*(\d+)$/i', $line, $matches)) {
                $current['crawl_delay'] = (int) $matches[1];
                continue;
            }

            if (preg_match('/^Sitemap:\s*(.+)$/i', $line, $matches)) {
                $current['sitemap_url'] = trim($matches[1]);
            }
        }

        $flush($current, $entries);

        return array_values(array_filter($entries, static function (array $entry): bool {
            return $entry['user_agent'] !== '';
        }));
    }

    /**
     * 生成 robots 文本
     */
    protected function buildRobotsContent(array $rows): string
    {
        if (empty($rows)) {
            return '';
        }

        $blocks = [];
        foreach ($rows as $row) {
            $lines = ['User-agent: ' . ($row['user_agent'] ?? '*')];
            foreach ($this->normalizeRobotsRulePayload($row['rules'] ?? '') as $rule) {
                $prefix = $rule['type'] === 'allow' ? 'Allow' : 'Disallow';
                $lines[] = $prefix . ': ' . $rule['path'];
            }
            $crawlDelay = (int) ($row['crawl_delay'] ?? 0);
            if ($crawlDelay > 0) {
                $lines[] = 'Crawl-delay: ' . $crawlDelay;
            }
            $sitemapUrl = trim((string) ($row['sitemap_url'] ?? ''));
            if ($sitemapUrl !== '') {
                $lines[] = 'Sitemap: ' . $sitemapUrl;
            }
            $blocks[] = implode("\r\n", $lines);
        }

        return implode("\r\n\r\n", $blocks) . "\r\n";
    }

    /**
     * SEO 提交状态
     */
    protected function formatSeoSubmitStatus(): array
    {
        $statusMap = [];
        $submissionTable = $this->resolveSeoTable('submissions');
        foreach (self::SEO_ENGINES as $engine) {
            if ($submissionTable === null) {
                $statusMap[$engine] = ['type' => 'info', 'text' => '未提交'];
                continue;
            }

            $row = Db::table($submissionTable)
                ->where('engine', $engine)
                ->order('submitted_at', 'desc')
                ->find();

            if (!$row) {
                $statusMap[$engine] = ['type' => 'info', 'text' => '未提交'];
                continue;
            }


            $status = (string) ($row['status'] ?? 'pending');
            if ($status === 'success') {
                $statusMap[$engine] = ['type' => 'success', 'text' => '已提交'];
            } elseif ($status === 'failed') {
                $statusMap[$engine] = ['type' => 'danger', 'text' => '提交失败'];
            } else {
                $statusMap[$engine] = ['type' => 'warning', 'text' => '处理中'];
            }
        }

        return $statusMap;
    }

    /**
     * 计算趋势百分比
     */
    protected function calculatePercentageTrend(int $current, int $previous): int
    {
        if ($previous <= 0) {
            return $current > 0 ? 100 : 0;
        }

        return (int) round((($current - $previous) / $previous) * 100);
    }

    /**
     * 构建 SEO 建议
     */
    protected function buildSeoSuggestions(array $configRows, int $keywordTotal, int $keywordTop10, int $pendingPages): array
    {
        $suggestions = [];

        $missingDesc = 0;
        $shortTitle = 0;
        foreach ($configRows as $row) {
            $titleLength = mb_strlen((string) ($row['title'] ?? ''));
            $descLength = mb_strlen((string) ($row['description'] ?? ''));
            if ($titleLength < 20 || $titleLength > 60) {
                $shortTitle++;
            }
            if ($descLength < 50 || $descLength > 200) {
                $missingDesc++;
            }
        }

        if ($pendingPages > 0) {
            $suggestions[] = [
                'priority' => 'high',
                'title' => '存在待收录页面',
                'description' => '当前仍有' . $pendingPages . '个页面未完成搜索引擎收录，建议优先补提 sitemap 或 URL。',
                'action' => '检查收录',
            ];
        }

        if ($shortTitle > 0 || $missingDesc > 0) {
            $suggestions[] = [
                'priority' => 'medium',
                'title' => '部分页面 TDK 质量不足',
                'description' => '共有' . ($shortTitle + $missingDesc) . '项标题或描述长度不在建议区间内，建议继续优化。',
                'action' => '优化配置',
            ];
        }

        if ($keywordTotal > 0 && $keywordTop10 < max(1, (int) ceil($keywordTotal * 0.2))) {
            $suggestions[] = [
                'priority' => 'medium',
                'title' => '核心关键词 Top10 占比偏低',
                'description' => '当前目标关键词' . $keywordTotal . '个，其中仅' . $keywordTop10 . '个进入前10，建议继续优化内容与内链。',
                'action' => '查看关键词',
            ];
        }

        if (empty($suggestions)) {
            $suggestions[] = [
                'priority' => 'low',
                'title' => '当前 SEO 基础状态稳定',
                'description' => '页面配置、关键词和收录状态暂无明显阻塞项，可以持续观察搜索表现。',
                'action' => '查看报告',
            ];
        }

        return $suggestions;
    }
}
