<?php

declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\service\SchemaInspector;
use think\Request;
use think\facade\Db;

/**
 * 六爻测算结果管理控制器
 */
class LiuyaoManage extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    private string $tableName = 'tc_liuyao_record';

    /**
     * 获取六爻测算结果列表
     */
    public function index()
    {
        if (!$this->hasAdminPermission('liuyao_view')) {
            return $this->error('无权限查看六爻测算结果', 403);
        }

        try {
            $tableName = SchemaInspector::resolveFirstExistingTable([
                'tc_liuyao_record',
                'liuyao_records'
            ]);

            if (!$tableName) {
                return $this->error('六爻测算表不存在', 500);
            }

            $params = $this->request->get();
            $page = (int) ($params['page'] ?? 1);
            $pageSize = min((int) ($params['page_size'] ?? 20), 100);

            $query = Db::table($tableName)->order('created_at', 'desc');

            // 用户筛选
            if (!empty($params['user_id'])) {
                $query->where('user_id', $params['user_id']);
            }

            // 日期范围筛选
            if (!empty($params['start_date'])) {
                $query->where('created_at', '>=', $params['start_date']);
            }
            if (!empty($params['end_date'])) {
                $query->where('created_at', '<=', $params['end_date'] . ' 23:59:59');
            }

            // 关键词搜索
            if (!empty($params['keyword'])) {
                $keyword = '%' . $params['keyword'] . '%';
                $query->where(function ($q) use ($keyword) {
                    $q->whereLike('question', $keyword)
                      ->whereOr('result', $keyword);
                });
            }

            $total = $query->count();
            $list = $query->page($page, $pageSize)->select()->toArray();

            return $this->success([
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
                'list' => $list,
            ]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_liuyao_index', $e, '获取六爻测算结果列表失败');
        }
    }

    /**
     * 获取六爻测算结果详情
     */
    public function detail(int $id)
    {
        if (!$this->hasAdminPermission('liuyao_view')) {
            return $this->error('无权限查看六爻测算结果详情', 403);
        }

        try {
            $tableName = SchemaInspector::resolveFirstExistingTable([
                'tc_liuyao_record',
                'liuyao_records'
            ]);

            if (!$tableName) {
                return $this->error('六爻测算表不存在', 500);
            }

            $record = Db::table($tableName)->where('id', $id)->find();
            if (!$record) {
                return $this->error('测算结果不存在', 404);
            }

            return $this->success($record);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_liuyao_detail', $e, '获取六爻测算结果详情失败');
        }
    }

    /**
     * 删除六爻测算结果
     */
    public function delete(int $id)
    {
        if (!$this->hasAdminPermission('liuyao_delete')) {
            return $this->error('无权限删除六爻测算结果', 403);
        }

        try {
            $tableName = SchemaInspector::resolveFirstExistingTable([
                'tc_liuyao_record',
                'liuyao_records'
            ]);

            if (!$tableName) {
                return $this->error('六爻测算表不存在', 500);
            }

            $record = Db::table($tableName)->where('id', $id)->find();
            if (!$record) {
                return $this->error('测算结果不存在', 404);
            }

            Db::table($tableName)->where('id', $id)->delete();
            return $this->success(null, '删除成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_liuyao_delete', $e, '删除六爻测算结果失败');
        }
    }

    /**
     * 批量删除六爻测算结果
     */
    public function batchDelete()
    {
        if (!$this->hasAdminPermission('liuyao_delete')) {
            return $this->error('无权限删除六爻测算结果', 403);
        }

        try {
            $tableName = SchemaInspector::resolveFirstExistingTable([
                'tc_liuyao_record',
                'liuyao_records'
            ]);

            if (!$tableName) {
                return $this->error('六爻测算表不存在', 500);
            }

            $ids = $this->request->post('ids', []);
            if (empty($ids) || !is_array($ids)) {
                return $this->error('请选择要删除的记录');
            }

            Db::table($tableName)->whereIn('id', $ids)->delete();
            return $this->success(null, '批量删除成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_liuyao_batch_delete', $e, '批量删除失败');
        }
    }

    /**
     * 获取统计数据
     */
    public function stats()
    {
        if (!$this->hasAdminPermission('liuyao_view')) {
            return $this->error('无权限查看统计数据', 403);
        }

        try {
            $tableName = SchemaInspector::resolveFirstExistingTable([
                'tc_liuyao_record',
                'liuyao_records'
            ]);

            if (!$tableName) {
                return $this->error('六爻测算表不存在', 500);
            }

            $stats = [
                'total' => Db::table($tableName)->count(),
                'today' => Db::table($tableName)->where('created_at', '>=', date('Y-m-d'))->count(),
                'this_month' => Db::table($tableName)->where('created_at', '>=', date('Y-m-01'))->count(),
                'this_year' => Db::table($tableName)->where('created_at', '>=', date('Y-01-01'))->count(),
            ];

            return $this->success($stats);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_liuyao_stats', $e, '获取统计数据失败');
        }
    }

    /**
     * 获取趋势图表数据
     */
    public function trend()
    {
        if (!$this->hasAdminPermission('liuyao_view')) {
            return $this->error('无权限查看统计数据', 403);
        }

        try {
            $tableName = SchemaInspector::resolveFirstExistingTable([
                'tc_liuyao_record',
                'liuyao_records'
            ]);

            if (!$tableName) {
                return $this->error('六爻测算表不存在', 500);
            }

            $params = $this->request->get();
            $period = $params['period'] ?? '30d'; // 7d, 30d, 90d, 1y

            // 根据时间范围生成分组条件
            $dateFormat = match($period) {
                '7d' => '%Y-%m-%d',
                '30d' => '%Y-%m-%d',
                '90d' => '%Y-%m-%d',
                '1y' => '%Y-%m',
                default => '%Y-%m-%d'
            };

            // 计算起始时间
            $startDate = match($period) {
                '7d' => date('Y-m-d', strtotime('-7 days')),
                '30d' => date('Y-m-d', strtotime('-30 days')),
                '90d' => date('Y-m-d', strtotime('-90 days')),
                '1y' => date('Y-01-01'),
                default => date('Y-m-d', strtotime('-30 days'))
            };

            // 按起卦方式统计
            $methodStats = Db::table($tableName)
                ->field("CAST(method AS CHAR) as method, COUNT(*) as count")
                ->where('created_at', '>=', $startDate)
                ->group('method')
                ->select()
                ->toArray();

            // 按日期统计
            $dateStats = Db::table($tableName)
                ->field("DATE_FORMAT(created_at, '{$dateFormat}') as date, COUNT(*) as count")
                ->where('created_at', '>=', $startDate)
                ->group('date')
                ->order('date', 'asc')
                ->select()
                ->toArray();

            // AI分析使用统计
            $aiStats = Db::table($tableName)
                ->field('is_ai, COUNT(*) as count')
                ->where('created_at', '>=', $startDate)
                ->group('is_ai')
                ->select()
                ->toArray();

            // 按时段统计（每日0-6点、6-12点、12-18点、18-24点）
            $hourStats = Db::table($tableName)
                ->field("CASE 
                    WHEN HOUR(created_at) BETWEEN 0 AND 5 THEN '凌晨(0-6点)'
                    WHEN HOUR(created_at) BETWEEN 6 AND 11 THEN '上午(6-12点)'
                    WHEN HOUR(created_at) BETWEEN 12 AND 17 THEN '下午(12-18点)'
                    ELSE '晚上(18-24点)'
                END as time_slot, COUNT(*) as count")
                ->where('created_at', '>=', $startDate)
                ->group('time_slot')
                ->select()
                ->toArray();

            // 补充数据确保图表完整
            $methodLabels = ['time' => '时间起卦', 'number' => '数字起卦', 'manual' => '手动摇卦'];
            $methodData = [];
            foreach ($methodLabels as $key => $label) {
                $found = false;
                foreach ($methodStats as $stat) {
                    if ($stat['method'] === $key) {
                        $methodData[] = ['name' => $label, 'value' => $stat['count']];
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $methodData[] = ['name' => $label, 'value' => 0];
                }
            }

            return $this->success([
                'period' => $period,
                'method_distribution' => $methodData,
                'date_trend' => $dateStats,
                'ai_usage' => [
                    ['name' => '基础分析', 'value' => $aiStats[0]['count'] ?? 0],
                    ['name' => 'AI分析', 'value' => $aiStats[1]['count'] ?? 0]
                ],
                'time_distribution' => $hourStats
            ]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_liuyao_trend', $e, '获取趋势数据失败');
        }
    }

    /**
     * 获取热门问题统计
     */
    public function hotQuestions()
    {
        if (!$this->hasAdminPermission('liuyao_view')) {
            return $this->error('无权限查看统计数据', 403);
        }

        try {
            $tableName = SchemaInspector::resolveFirstExistingTable([
                'tc_liuyao_record',
                'liuyao_records'
            ]);

            if (!$tableName) {
                return $this->error('六爻测算表不存在', 500);
            }

            $limit = min((int) $this->request->get('limit', 20), 50);
            $days = min((int) $this->request->get('days', 30), 365);
            $startDate = date('Y-m-d', strtotime("-{$days} days"));

            // 获取热门问题（按出现频率排序）
            $hotQuestions = Db::table($tableName)
                ->field('question, COUNT(*) as count')
                ->where('created_at', '>=', $startDate)
                ->whereNotNull('question')
                ->where('question', '<>', '')
                ->group('question')
                ->order('count', 'desc')
                ->limit($limit)
                ->select()
                ->toArray();

            // 获取高频关键词
            $keywords = Db::table($tableName)
                ->field('result')
                ->where('created_at', '>=', $startDate)
                ->whereNotNull('result')
                ->where('result', '<>', '')
                ->column('result');

            // 简单关键词提取（提取2-4字的中文词）
            $keywordMap = [];
            foreach ($keywords as $text) {
                preg_match_all('/[\x{4e00}-\x{9fa5}]{2,4}/u', $text, $matches);
                foreach ($matches[0] as $word) {
                    $keywordMap[$word] = ($keywordMap[$word] ?? 0) + 1;
                }
            }

            arsort($keywordMap);
            $topKeywords = array_slice($keywordMap, 0, $limit, true);
            $keywordList = [];
            foreach ($topKeywords as $word => $count) {
                $keywordList[] = ['keyword' => $word, 'count' => $count];
            }

            return $this->success([
                'hot_questions' => $hotQuestions,
                'hot_keywords' => $keywordList,
                'total_questions' => count($hotQuestions),
                'analysis_period' => $days
            ]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_liuyao_hot_questions', $e, '获取热门问题失败');
        }
    }
}
