<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use think\facade\Db;
use think\facade\Log;
use think\Request;

/**
 * 后台知识库管理控制器
 */
class Knowledge extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    protected const DEFAULT_PAGE_SIZE = 20;
    protected const MAX_PAGE_SIZE = 100;

    /**
     * 文章列表
     */
    public function articleList(Request $request)
    {
        if (!$this->hasAdminPermission('content_manage')) {
            return $this->error('无权限查看文章', 403);
        }

        try {
            $pagination = $this->normalizePagination(
                $request->get('page', 1),
                $request->get('pageSize', self::DEFAULT_PAGE_SIZE),
                self::DEFAULT_PAGE_SIZE,
                self::MAX_PAGE_SIZE
            );
            $keyword = trim((string) $request->get('keyword', ''));
            $categoryId = (int) $request->get('category_id', 0);

            $query = Db::name('tc_article')->alias('a')
                ->leftJoin('tc_article_category c', 'a.category_id = c.id')
                ->field('a.*, c.name as category_name')
                ->order('a.created_at', 'desc');

            if ($keyword !== '') {
                $keyword = preg_replace('/[%_\\\\]/', '', $keyword);
                $query->whereLike('a.title|a.summary', '%' . $keyword . '%');
            }

            if ($categoryId > 0) {
                $query->where('a.category_id', $categoryId);
            }

            $total = $query->count();
            $list = $query->page($pagination['page'], $pagination['pageSize'])->select()->toArray();

            return $this->success([
                'list' => $list,
                'total' => $total,
                'page' => $pagination['page'],
                'pageSize' => $pagination['pageSize'],
            ]);
        } catch (\Throwable $e) {
            $this->logKnowledgeError('article_list', $e, [
                'keyword' => $keyword,
                'category_id' => $categoryId,
                'page' => $pagination['page'],
                'page_size' => $pagination['pageSize'],
            ]);
            return $this->error('获取文章列表失败', 500);
        }
    }

    /**
     * 文章详情
     */
    public function articleDetail($id)
    {
        if (!$this->hasAdminPermission('content_manage')) {
            return $this->error('无权限查看文章', 403);
        }

        try {
            $article = Db::table('tc_article')
                ->alias('a')
                ->leftJoin('tc_article_category c', 'a.category_id = c.id')
                ->field('a.*, c.name as category_name, c.parent_id as category_parent_id')
                ->where('a.id', (int) $id)
                ->find();
            if (!$article) {
                return $this->error('文章不存在', 404);
            }

            $categoryPayload = $this->buildArticleCategoryPayload();
            $article = $this->normalizeArticleListRow($article);
            $article['category_path'] = $this->resolveArticleCategoryPath((int) ($article['category_id'] ?? 0), $categoryPayload['map']);
            return $this->success($article);
        } catch (\Throwable $e) {
            $this->logKnowledgeError('article_detail', $e, [
                'article_id' => (int) $id,
            ]);
            return $this->error('获取文章详情失败', 500);
        }
    }

    /**
     * 保存文章
     */
    public function saveArticle(Request $request)
    {
        if (!$this->hasAdminPermission('content_manage')) {
            return $this->error('无权限编辑内容', 403);
        }

        $data = $request->post();
        $title = trim((string) ($data['title'] ?? ''));
        $content = trim((string) ($data['content'] ?? ''));
        if ($title === '' || $content === '') {
            return $this->error('标题和内容不能为空', 400);
        }

        try {
            $category = $this->resolveArticleCategoryForWrite((int) ($data['category_id'] ?? 0));
            if (!$category) {
                return $this->error('请选择有效且已启用的分类', 400);
            }

            $slug = $this->sanitizeArticleSlug((string) ($data['slug'] ?? ''), $title, 'article_');
            if (!$this->isUniqueArticleSlug($slug)) {
                return $this->error('文章标识已存在，请更换 slug', 400);
            }

            $status = $this->normalizeArticleStatusValue($data['status'] ?? 1);
            if ($status === null) {
                return $this->error('文章状态参数无效', 400);
            }

            $saveData = [
                'category_id' => (int) $category['id'],
                'title' => $title,
                'slug' => $slug,
                'summary' => trim((string) ($data['summary'] ?? '')),
                'content' => $content,
                'thumbnail' => trim((string) ($data['thumbnail'] ?? '')),
                'status' => $status,
                'is_hot' => $this->normalizeBoolNumber($data['is_hot'] ?? 0),
                'author_id' => $this->getOperatorId(),
                'author_name' => $this->getAdminName(),
                'published_at' => in_array($status, [1, 2], true)
                    ? trim((string) ($data['published_at'] ?? '')) ?: date('Y-m-d H:i:s')
                    : null,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            $id = (int) Db::table('tc_article')->insertGetId($saveData);
            return $this->success([
                'id' => $id,
                'category_id' => (int) $category['id'],
                'category_name' => (string) $category['name'],
                'slug' => $slug,
            ], '保存成功');
        } catch (\Throwable $e) {
            $this->logKnowledgeError('article_create', $e, [
                'category_id' => (int) ($data['category_id'] ?? 0),
                'title_length' => mb_strlen($title),
                'status' => $status ?? null,
            ]);
            return $this->error('保存失败，请稍后重试', 500);
        }
    }

    /**
     * 更新文章
     */
    public function updateArticle(Request $request, $id)
    {
        if (!$this->hasAdminPermission('content_manage')) {
            return $this->error('无权限编辑内容', 403);
        }

        $articleId = (int) $id;
        $article = Db::table('tc_article')->where('id', $articleId)->find();
        if (!$article) {
            return $this->error('文章不存在', 404);
        }

        $data = $request->post();
        try {
            $updateData = [];

            if (array_key_exists('title', $data)) {
                $title = trim((string) $data['title']);
                if ($title === '') {
                    return $this->error('标题不能为空', 400);
                }
                $updateData['title'] = $title;
            }
            if (array_key_exists('summary', $data)) {
                $updateData['summary'] = trim((string) $data['summary']);
            }
            if (array_key_exists('content', $data)) {
                $content = trim((string) $data['content']);
                if ($content === '') {
                    return $this->error('内容不能为空', 400);
                }
                $updateData['content'] = $content;
            }
            if (array_key_exists('thumbnail', $data)) {
                $updateData['thumbnail'] = trim((string) $data['thumbnail']);
            }
            if (array_key_exists('is_hot', $data)) {
                $updateData['is_hot'] = $this->normalizeBoolNumber($data['is_hot']);
            }
            if (array_key_exists('status', $data)) {
                $status = $this->normalizeArticleStatusValue($data['status']);
                if ($status === null) {
                    return $this->error('文章状态参数无效', 400);
                }
                $updateData['status'] = $status;
                $updateData['published_at'] = in_array($status, [1, 2], true)
                    ? trim((string) ($data['published_at'] ?? ($article['published_at'] ?? ''))) ?: date('Y-m-d H:i:s')
                    : null;
            }
            if (array_key_exists('category_id', $data)) {
                $category = $this->resolveArticleCategoryForWrite((int) $data['category_id']);
                if (!$category) {
                    return $this->error('请选择有效且已启用的分类', 400);
                }
                $updateData['category_id'] = (int) $category['id'];
            }
            if (array_key_exists('slug', $data) || array_key_exists('title', $data)) {
                $slug = $this->sanitizeArticleSlug(
                    (string) ($data['slug'] ?? ($article['slug'] ?? '')),
                    (string) ($updateData['title'] ?? $article['title'] ?? ''),
                    'article_'
                );
                if (!$this->isUniqueArticleSlug($slug, $articleId)) {
                    return $this->error('文章标识已存在，请更换 slug', 400);
                }
                $updateData['slug'] = $slug;
            }

            if (empty($updateData)) {
                return $this->error('没有可更新的字段', 400);
            }

            $updateData['updated_at'] = date('Y-m-d H:i:s');
            Db::table('tc_article')->where('id', $articleId)->update($updateData);
            return $this->success(null, '更新成功');
        } catch (\Throwable $e) {
            $this->logKnowledgeError('article_update', $e, [
                'article_id' => $articleId,
                'updated_fields' => array_keys($updateData ?? []),
            ]);
            return $this->error('更新失败，请稍后重试', 500);
        }
    }

    /**
     * 删除文章
     */
    public function deleteArticle($id)
    {
        if (!$this->hasAdminPermission('content_manage')) {
            return $this->error('无权限删除内容', 403);
        }

        try {
            $deleted = Db::table('tc_article')->where('id', (int) $id)->delete();
            if ($deleted === 0) {
                return $this->error('文章不存在', 404);
            }

            return $this->success(null, '删除成功');
        } catch (\Throwable $e) {
            $this->logKnowledgeError('article_delete', $e, [
                'article_id' => (int) $id,
            ]);
            return $this->error('删除失败，请稍后重试', 500);
        }
    }

    /**
     * 文章分类列表
     */
    public function articleCategories(Request $request)
    {
        if (!$this->hasAdminPermission('content_manage')) {
            return $this->error('无权限查看分类', 403);
        }

        $includeDisabled = $this->normalizeBoolNumber($request->get('include_disabled', 0)) === 1;
        $selectedId = (int) $request->get('selected_id', 0);

        try {
            $payload = $this->buildArticleCategoryPayload($includeDisabled);

            return $this->success([
                'list' => $payload['list'],
                'tree' => $payload['tree'],
                'selected_path' => $selectedId > 0 ? $this->resolveArticleCategoryPath($selectedId, $payload['map']) : [],
                'updated_at' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            $this->logKnowledgeError('category_list', $e, [
                'include_disabled' => $includeDisabled,
                'selected_id' => $selectedId,
            ]);
            return $this->error('获取分类失败', 500);
        }
    }

    /**
     * 保存分类
     */
    public function saveArticleCategory(Request $request)
    {
        if (!$this->hasAdminPermission('content_manage')) {
            return $this->error('无权限编辑内容', 403);
        }

        $data = $request->post();
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            return $this->error('分类名称不能为空', 400);
        }

        $categoryId = (int) ($data['id'] ?? 0);
        $parentId = max(0, (int) ($data['parent_id'] ?? 0));
        if ($categoryId > 0 && $categoryId === $parentId) {
            return $this->error('父分类不能选择自己', 400);
        }

        try {
            if ($parentId > 0) {
                $parent = Db::table('tc_article_category')->where('id', $parentId)->find();
                if (!$parent) {
                    return $this->error('父分类不存在', 404);
                }
            }

            $slug = $this->sanitizeArticleSlug((string) ($data['slug'] ?? ''), $name, 'category_');
            if (!$this->isUniqueArticleCategorySlug($slug, $categoryId)) {
                return $this->error('分类标识已存在，请更换 slug', 400);
            }

            $saveData = [
                'name' => $name,
                'slug' => $slug,
                'description' => trim((string) ($data['description'] ?? '')),
                'parent_id' => $parentId,
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'status' => $this->normalizeArticleCategoryStatus($data['status'] ?? 1),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($categoryId > 0) {
                $exists = Db::table('tc_article_category')->where('id', $categoryId)->find();
                if (!$exists) {
                    return $this->error('分类不存在', 404);
                }
                Db::table('tc_article_category')->where('id', $categoryId)->update($saveData);
            } else {
                $saveData['created_at'] = date('Y-m-d H:i:s');
                $categoryId = (int) Db::table('tc_article_category')->insertGetId($saveData);
            }

            $payload = $this->buildArticleCategoryPayload(true);
            return $this->success([
                'id' => $categoryId,
                'slug' => $slug,
                'categories' => $payload,
            ], '保存成功');
        } catch (\Throwable $e) {
            $this->logKnowledgeError('category_save', $e, [
                'category_id' => $categoryId,
                'parent_id' => $parentId,
                'name_length' => mb_strlen($name),
            ]);
            return $this->error('操作失败，请稍后重试', 500);
        }
    }

    /**
     * 删除分类
     */
    public function deleteArticleCategory($id)
    {
        if (!$this->hasAdminPermission('content_manage')) {
            return $this->error('无权限删除内容', 403);
        }

        $categoryId = (int) $id;
        try {
            $category = Db::table('tc_article_category')->where('id', $categoryId)->find();
            if (!$category) {
                return $this->error('分类不存在', 404);
            }

            $articleCount = Db::table('tc_article')->where('category_id', $categoryId)->count();
            if ($articleCount > 0) {
                return $this->error('该分类下尚有文章，无法删除', 400);
            }

            $childCount = Db::table('tc_article_category')->where('parent_id', $categoryId)->count();
            if ($childCount > 0) {
                return $this->error('该分类下仍有子分类，无法删除', 400);
            }

            Db::table('tc_article_category')->where('id', $categoryId)->delete();
            return $this->success(null, '删除成功');
        } catch (\Throwable $e) {
            $this->logKnowledgeError('category_delete', $e, [
                'category_id' => $categoryId,
            ]);
            return $this->error('删除失败，请稍后重试', 500);
        }
    }

    /**
     * 校验并返回可写入的文章分类
     */
    protected function resolveArticleCategoryForWrite(int $categoryId): ?array
    {
        if ($categoryId <= 0) {
            return null;
        }

        $category = Db::table('tc_article_category')->where('id', $categoryId)->find();
        if (!$category) {
            return null;
        }

        return (int) ($category['status'] ?? 0) === 1 ? $category : null;
    }

    /**
     * 构建文章分类联动载荷
     */
    protected function buildArticleCategoryPayload(bool $includeDisabled = false): array
    {
        $query = Db::table('tc_article_category')
            ->alias('c')
            ->leftJoin('tc_article a', 'a.category_id = c.id')
            ->field([
                'c.id',
                'c.name',
                'c.slug',
                'c.description',
                'c.parent_id',
                'c.sort_order',
                'c.status',
                'c.created_at',
                'c.updated_at',
                'COUNT(a.id) AS article_count',
            ])
            ->group('c.id')
            ->order('c.sort_order', 'asc')
            ->order('c.id', 'asc');

        if (!$includeDisabled) {
            $query->where('c.status', 1);
        }

        $rows = $query->select()->toArray();
        $list = array_map(function (array $row): array {
            return $this->normalizeArticleCategoryRow($row);
        }, $rows);

        $map = [];
        foreach ($list as $item) {
            $map[(int) $item['id']] = $item;
        }

        return [
            'list' => $list,
            'tree' => $this->buildArticleCategoryTree($list),
            'map' => $map,
        ];
    }

    /**
     * 组装文章分类树
     */
    protected function buildArticleCategoryTree(array $list): array
    {
        $treeMap = [];
        foreach ($list as $item) {
            $item['children'] = [];
            $treeMap[(int) $item['id']] = $item;
        }

        $tree = [];
        foreach ($treeMap as $id => &$item) {
            $parentId = (int) ($item['parent_id'] ?? 0);
            if ($parentId > 0 && isset($treeMap[$parentId])) {
                $treeMap[$parentId]['children'][] = &$item;
                continue;
            }

            $tree[] = &$item;
        }
        unset($item);

        return array_values($tree);
    }

    /**
     * 归一化知识库分类行，统一修复名称/描述乱码与类型字段
     */
    protected function normalizeArticleCategoryRow(array $row): array
    {
        return array_merge($row, [
            'id' => (int) ($row['id'] ?? 0),
            'name' => $this->normalizePossiblyGarbledText((string) ($row['name'] ?? '')),
            'slug' => trim((string) ($row['slug'] ?? '')),
            'description' => $this->normalizePossiblyGarbledText((string) ($row['description'] ?? '')),
            'parent_id' => (int) ($row['parent_id'] ?? 0),
            'sort_order' => (int) ($row['sort_order'] ?? 0),
            'status' => $this->normalizeArticleCategoryStatus($row['status'] ?? 0),
            'article_count' => (int) ($row['article_count'] ?? 0),
            'created_at' => (string) ($row['created_at'] ?? ''),
            'updated_at' => (string) ($row['updated_at'] ?? ''),
        ]);
    }

    /**
     * 归一化文章列表/详情中的分类展示字段
     */
    protected function normalizeArticleListRow(array $row): array
    {
        $row['category_name'] = $this->normalizePossiblyGarbledText((string) ($row['category_name'] ?? ''));
        return $row;
    }

    /**
     * 尝试修复常见的 UTF-8 -> Windows-1252 乱码
     */
    protected function normalizePossiblyGarbledText(string $value): string
    {
        $value = trim($value);
        if ($value === '' || preg_match('/[\x{4e00}-\x{9fff}]/u', $value)) {
            return $value;
        }

        if (!preg_match('/[åäæçéöüÃÂâ€]/u', $value)) {
            return $value;
        }

        $repaired = @iconv('UTF-8', 'Windows-1252//IGNORE', $value);
        if (is_string($repaired) && $repaired !== '' && preg_match('//u', $repaired) && preg_match('/[\x{4e00}-\x{9fff}]/u', $repaired)) {
            return $repaired;
        }

        return $value;
    }


    /**
     * 解析分类路径，供编辑页快速跳转使用
     */
    protected function resolveArticleCategoryPath(int $categoryId, array $categoryMap): array
    {

        $path = [];
        $visited = [];
        while ($categoryId > 0 && isset($categoryMap[$categoryId]) && !isset($visited[$categoryId])) {
            $visited[$categoryId] = true;
            $current = $categoryMap[$categoryId];
            array_unshift($path, [
                'id' => (int) ($current['id'] ?? 0),
                'name' => (string) ($current['name'] ?? ''),
                'slug' => (string) ($current['slug'] ?? ''),
            ]);
            $categoryId = (int) ($current['parent_id'] ?? 0);
        }

        return $path;
    }

    /**
     * 归一化文章状态
     */
    protected function normalizeArticleStatusValue(mixed $status): ?int
    {
        $status = (int) $status;
        return in_array($status, [0, 1, 2, 3], true) ? $status : null;
    }

    /**
     * 归一化分类状态
     */
    protected function normalizeArticleCategoryStatus(mixed $status): int
    {
        return (int) ((int) $status === 1);
    }

    /**
     * 归一化布尔型数值字段
     */
    protected function normalizeBoolNumber(mixed $value): int
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_numeric($value)) {
            return (int) ((int) $value !== 0);
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true) ? 1 : 0;
    }

    /**
     * 生成安全 slug
     */
    protected function sanitizeArticleSlug(string $slug, string $fallback, string $prefix = ''): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9\-_]+/i', '-', $slug) ?: '';
        $slug = trim($slug, '-_');

        if ($slug === '') {
            $base = strtolower(trim($fallback));
            $base = preg_replace('/[^a-z0-9\-_]+/i', '-', $base) ?: '';
            $base = trim($base, '-_');
            $slug = $base !== '' ? $base : $prefix . time();
        }

        return $slug;
    }

    /**
     * 校验文章 slug 唯一性
     */
    protected function isUniqueArticleSlug(string $slug, int $excludeId = 0): bool
    {
        $query = Db::table('tc_article')->where('slug', $slug);
        if ($excludeId > 0) {
            $query->where('id', '<>', $excludeId);
        }

        return $query->count() === 0;
    }

    /**
     * 校验分类 slug 唯一性
     */
    protected function isUniqueArticleCategorySlug(string $slug, int $excludeId = 0): bool
    {
        $query = Db::table('tc_article_category')->where('slug', $slug);
        if ($excludeId > 0) {
            $query->where('id', '<>', $excludeId);
        }

        return $query->count() === 0;
    }

    protected function logKnowledgeError(string $action, \Throwable $e, array $context = []): void
    {
        Log::error('后台知识库接口异常', [
            'action' => $action,
            'admin_id' => $this->getAdminId(),
            'context' => $context,
            'error' => $e->getMessage(),
            'exception' => get_class($e),
        ]);
    }

    /**
     * 获取当前管理员名称
     */
    protected function getAdminName(): string
    {
        $adminUser = $this->request->adminUser ?? [];
        return trim((string) ($adminUser['username'] ?? '')) ?: 'Unknown';
    }
}
