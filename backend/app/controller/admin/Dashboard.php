<?php

declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\BaziRecord;
use app\model\Feedback;
use app\model\TarotRecord;
use app\model\User;
use app\service\AdminStatsService;
use app\service\SchemaInspector;
use think\facade\Log;

/**
 * 后台Dashboard控制器
 */
class Dashboard extends BaseController
{
    /**
     * 获取Dashboard统计数据
     */
    public function index()
    {
        if (!$this->checkPermission('stats_view')) {
            return $this->error('无权限访问统计数据', 403);
        }

        try {
            $stats = AdminStatsService::getDashboardStats();
            return $this->success($stats);
        } catch (\Throwable $e) {
            return $this->respondWithDashboardError('dashboard_index', '获取统计数据失败，请稍后重试', $e);
        }
    }

    /**
     * 获取趋势数据
     */
    public function trend()
    {
        if (!$this->checkPermission('stats_view')) {
            return $this->error('无权限访问统计数据', 403);
        }

        $days = (int) $this->request->get('days', 30);

        try {
            $stats = AdminStatsService::getTrendStats($days);
            return $this->success($stats);
        } catch (\Throwable $e) {
            return $this->respondWithDashboardError('dashboard_trend', '获取趋势数据失败，请稍后重试', $e, [
                'days' => $days,
            ]);
        }
    }

    /**
     * 手动更新统计数据
     */
    public function updateStats()
    {
        if (!$this->checkPermission('stats_view')) {
            return $this->error('无权限刷新统计数据', 403);
        }

        $date = trim((string) $this->request->post('date', ''));
        if ($date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $this->error('统计日期格式无效，请使用 YYYY-MM-DD', 400);
        }

        $targetDate = $date !== '' ? $date : date('Y-m-d');

        try {
            AdminStatsService::updateDailyStats($targetDate);
            return $this->success([
                'date' => $targetDate,
            ], '统计数据更新成功');
        } catch (\Throwable $e) {
            return $this->respondWithDashboardError('dashboard_update_stats', '更新统计数据失败，请稍后重试', $e, [
                'date' => $targetDate,
            ]);
        }
    }

    /**
     * 统一处理 Dashboard 异常
     */
    private function respondWithDashboardError(string $action, string $message, \Throwable $e, array $context = [])
    {
        Log::error('后台Dashboard接口异常', [
            'action'   => $action,
            'admin_id' => $this->getAdminId(),
            'context'  => $context,
            'error'    => $e->getMessage(),
        ]);

        return $this->error($message, 500);
    }

    /**
     * 获取图表数据
     */
    public function chartData($type)
    {
        if (!$this->checkPermission('stats_view')) {
            return $this->error('无权限访问统计数据', 403);
        }

        try {
            switch ($type) {
                case 'user_source':
                    $data = [
                        ['name' => '直接访问', 'value' => User::where('source', 'direct')->count()],
                        ['name' => '搜索引擎', 'value' => User::where('source', 'search')->count()],
                        ['name' => '社交媒体', 'value' => User::where('source', 'social')->count()],
                        ['name' => '邀请注册', 'value' => User::where('source', 'invite')->count()],
                        ['name' => '其他', 'value' => User::where('source', 'other')->count()],
                    ];
                    break;

                case 'feature_usage':
                    $data = [
                        ['name' => '八字排盘', 'value' => BaziRecord::count()],
                        ['name' => '塔罗占卜', 'value' => TarotRecord::count()],
                    ];
                    break;

                case 'user_status':
                    $data = [
                        ['name' => '正常', 'value' => User::where('status', 1)->count()],
                        ['name' => '禁用', 'value' => User::where('status', 0)->count()],
                    ];
                    break;

                default:
                    return $this->error('未知的图表类型', 400);
            }

            return $this->success($data, '获取成功');
        } catch (\Exception $e) {
            Log::error('获取图表数据失败: ' . $e->getMessage());
            return $this->error('获取图表数据失败', 500);
        }
    }

    /**
     * 获取实时看板数据
     */
    public function realtime()
    {
        if (!$this->checkPermission('stats_view')) {
            return $this->error('无权限访问统计数据', 403);
        }

        try {
            $limit = max(1, min(50, (int) $this->request->get('limit', 10)));
            return $this->success($this->buildRealtimePayload($limit), '获取成功');
        } catch (\Throwable $e) {
            Log::error('获取实时数据失败: ' . $e->getMessage());
            return $this->error('获取实时数据失败', 500);
        }
    }

    /**
     * 导出实时看板快照
     */
    public function exportRealtime()
    {
        if (!$this->checkPermission('stats_view')) {
            return $this->error('无权限导出实时数据', 403);
        }

        try {
            $limit = max(1, min(100, (int) $this->request->get('limit', 50)));
            $payload = $this->buildRealtimePayload($limit);

            $rows = [
                ['指标', '数值'],
                ['今日新增用户', (string) ($payload['today_users'] ?? 0)],
                ['今日八字排盘', (string) ($payload['today_bazi'] ?? 0)],
                ['今日塔罗占卜', (string) ($payload['today_tarot'] ?? 0)],
                ['今日反馈', (string) ($payload['today_feedback'] ?? 0)],
                ['近15分钟活跃用户', (string) ($payload['online_users'] ?? 0)],
                ['待处理反馈', (string) ($payload['pending_feedback'] ?? 0)],
                ['生成时间', (string) ($payload['timestamp'] ?? '')],
                [],
                ['时间', '事件', '用户'],
            ];

            foreach ($payload['realtime_list'] ?? [] as $item) {
                $rows[] = [
                    (string) ($item['time'] ?? ''),
                    (string) ($item['action'] ?? ''),
                    (string) ($item['user'] ?? ''),
                ];
            }

            $csvLines = array_map(function (array $row): string {
                return implode(',', array_map(fn ($value) => $this->escapeCsv((string) $value), $row));
            }, $rows);
            $csv = "\xEF\xBB\xBF" . implode("\n", $csvLines) . "\n";

            $this->logOperation('export_realtime', 'stats', [
                'detail' => '导出实时看板快照，条数：' . count($payload['realtime_list'] ?? []),
            ]);

            return response($csv, 200, [
                'Content-Type'        => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="dashboard_realtime_' . date('YmdHis') . '.csv"',
            ]);
        } catch (\Throwable $e) {
            Log::error('导出实时看板快照失败: ' . $e->getMessage());
            return $this->error('导出实时数据失败，请稍后重试', 500);
        }
    }

    // ─── 私有辅助方法 ────────────────────────────────────────────────────────

    private function buildRealtimePayload(int $limit = 10): array
    {
        $todayStart = date('Y-m-d 00:00:00');
        $recentActiveAt = date('Y-m-d H:i:s', strtotime('-15 minutes'));
        $realtimeList = $this->buildRealtimeList($limit);

        return [
            'today_users'      => User::where('created_at', '>=', $todayStart)->count(),
            'today_bazi'       => BaziRecord::where('created_at', '>=', $todayStart)->count(),
            'today_tarot'      => TarotRecord::where('created_at', '>=', $todayStart)->count(),
            'today_feedback'   => Feedback::where('created_at', '>=', $todayStart)->count(),
            'online_users'     => User::where('last_login_at', '>=', $recentActiveAt)->count(),
            'pending_feedback' => Feedback::where(function ($q) {
                $q->where('status', 0)->whereOr('status', '=', 'pending');
            })->count(),
            'realtime_list' => $realtimeList,
            'timestamp'     => date('Y-m-d H:i:s'),
        ];
    }

    private function buildRealtimeList(int $limit = 10): array
    {
        $userRows = User::order('created_at', 'desc')
            ->limit($limit)
            ->field($this->buildRealtimeUserFieldList(true))
            ->select()
            ->toArray();

        $baziRows = BaziRecord::order('created_at', 'desc')
            ->limit($limit)
            ->field('user_id,created_at')
            ->select()
            ->toArray();

        $tarotRows = TarotRecord::order('created_at', 'desc')
            ->limit($limit)
            ->field('user_id,created_at,spread_type')
            ->select()
            ->toArray();

        $feedbackRows = Feedback::where(function ($q) {
            $q->where('status', 0)->whereOr('status', '=', 'pending');
        })->order('created_at', 'desc')
            ->limit($limit)
            ->field('user_id,type,created_at')
            ->select()
            ->toArray();

        $userNameMap = $this->resolveRealtimeUserNames(array_merge(
            array_column($baziRows, 'user_id'),
            array_column($tarotRows, 'user_id'),
            array_column($feedbackRows, 'user_id')
        ));

        $events = [];
        foreach ($userRows as $row) {
            $sortTime = (string) ($row['created_at'] ?? $row['updated_at'] ?? '');
            $events[] = [
                'time'      => date('H:i:s', strtotime($sortTime ?: 'now')),
                'action'    => '新用户注册',
                'user'      => $this->formatRealtimeUserName($row),
                'sort_time' => $sortTime,
            ];
        }
        foreach ($baziRows as $row) {
            $userId = (int) ($row['user_id'] ?? 0);
            $events[] = [
                'time'      => date('H:i:s', strtotime((string) ($row['created_at'] ?? 'now'))),
                'action'    => '提交八字排盘',
                'user'      => $userNameMap[$userId] ?? ('用户#' . $userId),
                'sort_time' => (string) ($row['created_at'] ?? ''),
            ];
        }
        foreach ($tarotRows as $row) {
            $userId = (int) ($row['user_id'] ?? 0);
            $spreadType = trim((string) ($row['spread_type'] ?? ''));
            $events[] = [
                'time'      => date('H:i:s', strtotime((string) ($row['created_at'] ?? 'now'))),
                'action'    => $spreadType !== '' ? '进行塔罗占卜（' . $spreadType . '）' : '进行塔罗占卜',
                'user'      => $userNameMap[$userId] ?? ('用户#' . $userId),
                'sort_time' => (string) ($row['created_at'] ?? ''),
            ];
        }
        foreach ($feedbackRows as $row) {
            $userId = (int) ($row['user_id'] ?? 0);
            $feedbackType = trim((string) ($row['type'] ?? '反馈'));
            $events[] = [
                'time'      => date('H:i:s', strtotime((string) ($row['created_at'] ?? 'now'))),
                'action'    => '提交待处理反馈（' . $feedbackType . '）',
                'user'      => $userNameMap[$userId] ?? ($userId > 0 ? '用户#' . $userId : '匿名用户'),
                'sort_time' => (string) ($row['created_at'] ?? ''),
            ];
        }

        usort($events, static function (array $left, array $right): int {
            return strcmp((string) ($right['sort_time'] ?? ''), (string) ($left['sort_time'] ?? ''));
        });

        return array_map(static function (array $event): array {
            unset($event['sort_time']);
            return $event;
        }, array_slice($events, 0, $limit));
    }

    private function buildRealtimeUserFieldList(bool $includeTimeField = false): string
    {
        $columns = SchemaInspector::getTableColumns('tc_user');
        $fields = ['id'];

        foreach (['nickname', 'username', 'phone'] as $field) {
            if (isset($columns[$field])) {
                $fields[] = $field;
            }
        }

        if ($includeTimeField) {
            foreach (['created_at', 'updated_at'] as $field) {
                if (isset($columns[$field])) {
                    $fields[] = $field;
                    break;
                }
            }
        }

        return implode(',', array_values(array_unique($fields)));
    }

    private function formatRealtimeUserName(array $row): string
    {
        $id = (int) ($row['id'] ?? 0);
        foreach (['nickname', 'username', 'phone'] as $field) {
            $value = trim((string) ($row[$field] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '用户#' . $id;
    }

    private function resolveRealtimeUserNames(array $userIds): array
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if (empty($userIds)) {
            return [];
        }

        $rows = User::whereIn('id', $userIds)
            ->field($this->buildRealtimeUserFieldList())
            ->select()
            ->toArray();

        $result = [];
        foreach ($rows as $row) {
            $id = (int) ($row['id'] ?? 0);
            $result[$id] = $this->formatRealtimeUserName($row);
        }

        return $result;
    }

    private function escapeCsv(string $field): string
    {
        $field = str_replace('"', '""', $field);
        if (strpos($field, ',') !== false || strpos($field, '"') !== false || strpos($field, "\n") !== false) {
            $field = '"' . $field . '"';
        }
        return $field;
    }
}
