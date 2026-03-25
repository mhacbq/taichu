<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\PaymentConfig;
use app\model\RechargeOrder;
use app\model\User;
use app\service\SchemaInspector;
use app\service\WechatPayService;
use think\Request;
use think\facade\Db;
use think\facade\Log;


/**
 * 后台支付管理控制器
 */
class Payment extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    protected const ORDER_STATUSES = [
        RechargeOrder::STATUS_PENDING,
        RechargeOrder::STATUS_PAID,
        RechargeOrder::STATUS_CANCELLED,
        RechargeOrder::STATUS_REFUNDED,
    ];

    /**
     * 读取支付查看权限
     */
    protected function requirePaymentViewPermission(string $message)
    {
        if (!$this->hasAnyAdminPermission(['stats_view', 'config_manage'])) {
            return $this->error($message, 403);
        }

        return null;
    }

    /**
     * 读取支付管理权限
     */
    protected function requirePaymentManagePermission(string $message)
    {
        if (!$this->hasAdminPermission('config_manage')) {
            return $this->error($message, 403);
        }

        return null;
    }
    
    /**
     * 获取微信支付配置
     */

    public function getConfig()
    {
        if ($response = $this->requirePaymentManagePermission('无权限查看支付配置')) {
            return $response;
        }

        $config = PaymentConfig::getSafeConfig();
        
        if (!$config) {
            return $this->success([
                'mch_id' => '',
                'app_id' => '',
                'api_key' => '',
                'api_key_masked' => false,
                'has_cert' => false,
                'has_key_pem' => false,
                'notify_url' => '',
                'is_enabled' => false,
            ]);
        }
        
        return $this->success($config);
    }
    
    /**
     * 保存微信支付配置
     */
    public function saveConfig(Request $request)
    {
        if ($response = $this->requirePaymentManagePermission('无权限修改支付配置')) {
            return $response;
        }

        $data = $request->post();
        
        if (empty($data['mch_id'])) {
            return $this->error('商户号不能为空');
        }
        if (empty($data['app_id'])) {
            return $this->error('应用ID不能为空');
        }
        if (empty($data['notify_url'])) {
            return $this->error('回调地址不能为空');
        }
        if (!filter_var($data['notify_url'], FILTER_VALIDATE_URL)) {
            return $this->error('回调地址格式无效');
        }
        if (empty($data['api_key']) && empty($data['api_key_masked'])) {
            return $this->error('API密钥不能为空');
        }
        
        $saveData = [
            'type' => 'wechat_jsapi',
            'mch_id' => trim((string) $data['mch_id']),
            'app_id' => trim((string) $data['app_id']),
            'notify_url' => trim((string) $data['notify_url']),
            'is_enabled' => !empty($data['is_enabled']),
            'api_key_masked' => !empty($data['api_key_masked']),
        ];
        
        if (!empty($data['api_key'])) {
            $saveData['api_key'] = trim((string) $data['api_key']);
            $saveData['api_key_masked'] = false;
        }
        if (!empty($data['api_cert'])) {
            $saveData['api_cert'] = (string) $data['api_cert'];
        }
        if (!empty($data['api_key_pem'])) {
            $saveData['api_key_pem'] = (string) $data['api_key_pem'];
        }
        
        try {
            if (PaymentConfig::saveConfig($saveData)) {
                return $this->success(null, '配置保存成功');
            }
        } catch (\Throwable $e) {
            Log::error('保存支付配置失败: ' . $e->getMessage(), [
                'admin_id' => $this->getOperatorId(),
            ]);
        }
        
        return $this->error('配置保存失败');
    }
    
    /**
     * 获取充值订单列表
     */
    public function getOrders(Request $request)
    {
        if ($response = $this->requirePaymentViewPermission('无权限查看充值订单')) {
            return $response;
        }

        $pagination = $this->getPaginationParams('page', 'limit', 20, 100);

        $page = $pagination['page'];
        $limit = $pagination['pageSize'];
        $status = trim((string) $request->get('status', ''));
        $keyword = trim((string) $request->get('keyword', ''));
        $userId = trim((string) $request->get('user_id', ''));
        $startDate = trim((string) $request->get('start_date', ''));
        $endDate = trim((string) $request->get('end_date', ''));
        $rechargeOrderColumns = $this->getRechargeOrderColumns();
        
        $query = RechargeOrder::with('user');
        
        
        if ($status !== '') {
            if (!in_array($status, self::ORDER_STATUSES, true)) {
                return $this->error('订单状态参数无效');
            }
            $this->applyRechargeOrderStatusFilter($query, $status);
        }

        
        if ($userId !== '') {
            if (!ctype_digit($userId)) {
                return $this->error('用户ID参数无效');
            }
            $query->where('user_id', (int) $userId);
        }


        if ($keyword !== '') {
            $keyword = preg_replace('/[%_\\\\]/', '', $keyword) ?: '';
            $matchedUserIds = User::whereLike('nickname', '%' . $keyword . '%')->column('id');

            $query->where(function ($q) use ($keyword, $matchedUserIds, $rechargeOrderColumns) {
                $q->whereLike('order_no', '%' . $keyword . '%');

                if (!empty($rechargeOrderColumns)) {
                    if (isset($rechargeOrderColumns['pay_order_no'])) {
                        $q->whereOrLike('pay_order_no', '%' . $keyword . '%');
                    }
                    if (isset($rechargeOrderColumns['transaction_id'])) {
                        $q->whereOrLike('transaction_id', '%' . $keyword . '%');
                    }
                }

                if (ctype_digit($keyword)) {
                    $q->whereOr('user_id', (int) $keyword);
                }

                if (!empty($matchedUserIds)) {
                    $q->whereOrIn('user_id', $matchedUserIds);
                }
            });
        }

        
        if ($startDate !== '') {
            if (!$this->isDateOnly($startDate)) {
                return $this->error('开始日期格式无效');
            }
            $query->where('created_at', '>=', $startDate . ' 00:00:00');
        }
        if ($endDate !== '') {
            if (!$this->isDateOnly($endDate)) {
                return $this->error('结束日期格式无效');
            }
            $query->where('created_at', '<=', $endDate . ' 23:59:59');
        }
        
        $total = $query->count();
        $orders = $query->order('created_at', 'desc')
            ->page($page, $limit)
            ->select();
        
        $list = [];
        foreach ($orders as $order) {
            $list[] = $this->buildRechargeOrderPayload($order);
        }

        
        return $this->success([
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 导出充值订单
     */
    public function exportOrders(Request $request)
    {
        if ($response = $this->requirePaymentViewPermission('无权限导出充值订单')) {
            return $response;
        }

        $status = trim((string) $request->get('status', ''));
        $keyword = trim((string) $request->get('keyword', ''));
        $startDate = trim((string) $request->get('start_date', ''));
        $endDate = trim((string) $request->get('end_date', ''));
        $rechargeOrderColumns = $this->getRechargeOrderColumns();

        try {
            $query = RechargeOrder::with('user');


            if ($status !== '') {
                if (!in_array($status, self::ORDER_STATUSES, true)) {
                    return $this->error('订单状态参数无效');
                }
                $this->applyRechargeOrderStatusFilter($query, $status);
            }


            if ($keyword !== '') {
                $keyword = preg_replace('/[%_\\]/', '', $keyword) ?: '';
                $matchedUserIds = User::whereLike('nickname', '%' . $keyword . '%')->column('id');
                $query->where(function ($q) use ($keyword, $matchedUserIds, $rechargeOrderColumns) {
                    $q->whereLike('order_no', '%' . $keyword . '%');

                    if (isset($rechargeOrderColumns['pay_order_no'])) {
                        $q->whereOrLike('pay_order_no', '%' . $keyword . '%');
                    }
                    if (isset($rechargeOrderColumns['transaction_id'])) {
                        $q->whereOrLike('transaction_id', '%' . $keyword . '%');
                    }

                    if (ctype_digit($keyword)) {
                        $q->whereOr('user_id', (int) $keyword);
                    }

                    if (!empty($matchedUserIds)) {
                        $q->whereOrIn('user_id', $matchedUserIds);
                    }
                });
            }


            if ($startDate !== '') {
                if (!$this->isDateOnly($startDate)) {
                    return $this->error('开始日期格式无效');
                }
                $query->where('created_at', '>=', $startDate . ' 00:00:00');
            }
            if ($endDate !== '') {
                if (!$this->isDateOnly($endDate)) {
                    return $this->error('结束日期格式无效');
                }
                $query->where('created_at', '<=', $endDate . ' 23:59:59');
            }

            $orders = $query->order('created_at', 'desc')->select();
            $csv = "\xEF\xBB\xBF" . implode(',', ['订单号', '支付单号', '退款单号', '用户ID', '用户昵称', '金额', '积分', '状态', '支付方式', '支付时间', '退款金额', '退款时间', '创建时间']) . "\n";

            foreach ($orders as $order) {
                $payload = $this->buildRechargeOrderPayload($order);
                $row = [
                    $this->escapeCsv((string) ($payload['order_no'] ?? '')),
                    $this->escapeCsv((string) ($payload['pay_order_no'] ?? '')),
                    $this->escapeCsv((string) ($payload['refund_no'] ?? '')),
                    (string) ($payload['user_id'] ?? ''),
                    $this->escapeCsv((string) ($payload['user_nickname'] ?? '未知用户')),
                    (string) ($payload['amount'] ?? ''),
                    (string) ($payload['points'] ?? ''),
                    $this->escapeCsv((string) ($payload['status'] ?? '')),
                    $this->escapeCsv((string) ($payload['payment_type'] ?? '')),
                    (string) ($payload['pay_time'] ?? ''),
                    (string) ($payload['refund_amount'] ?? ''),
                    (string) ($payload['refund_time'] ?? ''),
                    (string) ($payload['created_at'] ?? ''),
                ];

                $csv .= implode(',', $row) . "\n";
            }


            return response($csv, 200, [
                'Content-Type' => 'text/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="recharge_orders_' . date('YmdHis') . '.csv"',
            ]);
        } catch (\Throwable $e) {
            Log::error('导出充值订单失败: ' . $e->getMessage(), [
                'admin_id' => $this->getOperatorId(),
            ]);
            return $this->error('导出充值订单失败，请稍后重试', 500);
        }
    }
    
    /**
     * 获取订单详情
     */

    public function getOrderDetail(Request $request, string $id)
    {
        if ($response = $this->requirePaymentViewPermission('无权限查看订单详情')) {
            return $response;
        }

        $identifier = trim($id !== '' ? $id : (string) $request->param('id', ''));

        if ($identifier === '') {
            return $this->error('订单标识不能为空');
        }

        $order = $this->findOrderByIdentifier($identifier);
        if (!$order) {
            return $this->error('订单不存在', 404);
        }
        
        return $this->success($this->buildRechargeOrderPayload($order, true));

    }
    
    /**
     * 手动补单（管理员手动标记订单为已支付）
     */
    public function manualComplete(Request $request, string $id)
    {
        if ($response = $this->requirePaymentManagePermission('无权限执行手动补单')) {
            return $response;
        }

        $identifier = trim($id !== '' ? $id : (string) $request->param('id', ''));
        if ($identifier === '') {
            return $this->error('订单标识不能为空');
        }

        $order = $this->findOrderByIdentifier($identifier);
        if (!$order) {
            return $this->error('订单不存在', 404);
        }

        $remark = trim((string) $request->param('remark', '管理员手动补单')) ?: '管理员手动补单';
        $result = $this->applyStatusTransition($order, RechargeOrder::STATUS_PAID, $remark);
        if (($result['success'] ?? false) !== true) {
            return $this->error($result['message'] ?? '补单失败');
        }

        return $this->success([
            'order_no' => $order->order_no,
            'status' => $order->status,
        ], '补单成功');
    }
    
    /**
     * 取消订单
     */
    public function cancelOrder(Request $request, string $id)
    {
        if ($response = $this->requirePaymentManagePermission('无权限取消订单')) {
            return $response;
        }

        $identifier = trim($id !== '' ? $id : (string) $request->param('id', ''));
        if ($identifier === '') {
            return $this->error('订单标识不能为空');
        }

        $order = $this->findOrderByIdentifier($identifier);
        if (!$order) {
            return $this->error('订单不存在', 404);
        }

        $remark = trim((string) $request->param('remark', '管理员取消订单')) ?: '管理员取消订单';
        $result = $this->applyStatusTransition($order, RechargeOrder::STATUS_CANCELLED, $remark);
        if (($result['success'] ?? false) !== true) {
            return $this->error($result['message'] ?? '取消失败');
        }

        return $this->success([
            'order_no' => $order->order_no,
            'status' => $order->status,
        ], $result['message'] ?? '订单已取消');
    }

    /**
     * 批量更新订单状态
     */
    public function batchUpdateStatus(Request $request)
    {
        if ($response = $this->requirePaymentManagePermission('无权限批量更新充值订单')) {
            return $response;
        }

        $ids = $request->param('ids', []);
        if (is_string($ids)) {
            $ids = preg_split('/[\s,]+/', $ids, -1, PREG_SPLIT_NO_EMPTY);
        }
        if (!is_array($ids) || empty($ids)) {
            return $this->error('订单标识列表不能为空');
        }

        $status = trim((string) $request->param('status', ''));
        if (!in_array($status, self::ORDER_STATUSES, true) || $status === RechargeOrder::STATUS_PENDING) {
            return $this->error('批量状态参数无效');
        }

        $reason = trim((string) $request->param('reason', '管理员批量更新订单状态')) ?: '管理员批量更新订单状态';
        $identifiers = array_values(array_unique(array_filter(array_map(static fn ($value) => trim((string) $value), $ids))));
        if (empty($identifiers)) {
            return $this->error('订单标识列表无效');
        }

        $successItems = [];
        $failedItems = [];
        foreach ($identifiers as $identifier) {
            $order = $this->findOrderByIdentifier($identifier);
            if (!$order) {
                $failedItems[] = [
                    'identifier' => $identifier,
                    'message' => '订单不存在',
                ];
                continue;
            }

            $result = $this->applyStatusTransition($order, $status, $reason);
            if (($result['success'] ?? false) === true) {
                $successItems[] = [
                    'identifier' => $identifier,
                    'order_no' => $order->order_no,
                    'status' => $order->status,
                ];
                continue;
            }

            $failedItems[] = [
                'identifier' => $identifier,
                'order_no' => $order->order_no,
                'message' => $result['message'] ?? '更新失败',
            ];
        }

        $this->logOperation('batch_update_recharge_order_status', 'recharge_order', [
            'detail' => sprintf('批量更新充值订单状态为 %s，成功 %d，失败 %d', $status, count($successItems), count($failedItems)),
            'after_data' => [
                'status' => $status,
                'success' => $successItems,
                'failed' => $failedItems,
            ],
        ]);

        if (empty($successItems)) {
            return $this->error('批量更新失败', 400, [
                'success_count' => 0,
                'failed_count' => count($failedItems),
                'failed' => $failedItems,
            ]);
        }

        return $this->success([
            'status' => $status,
            'success_count' => count($successItems),
            'failed_count' => count($failedItems),
            'success_list' => $successItems,
            'failed_list' => $failedItems,
        ], empty($failedItems) ? '批量更新成功' : '批量更新已完成，部分订单处理失败');
    }

    /**
     * 更新订单状态
     */
    public function updateOrderStatus(Request $request, string $id)
    {
        if ($response = $this->requirePaymentManagePermission('无权限更新订单状态')) {
            return $response;
        }

        $identifier = trim($id !== '' ? $id : (string) $request->param('id', ''));
        $status = trim((string) $request->param('status', ''));

        if ($identifier === '') {
            return $this->error('订单标识不能为空');
        }
        if (!in_array($status, self::ORDER_STATUSES, true)) {
            return $this->error('订单状态参数无效');
        }
        if ($status === RechargeOrder::STATUS_PENDING) {
            return $this->error('不支持手动改回待支付状态');
        }

        $order = $this->findOrderByIdentifier($identifier);
        if (!$order) {
            return $this->error('订单不存在', 404);
        }

        $remark = trim((string) $request->param('remark', $request->param('reason', '管理员更新订单状态'))) ?: '管理员更新订单状态';
        $result = $this->applyStatusTransition($order, $status, $remark);
        if (($result['success'] ?? false) !== true) {
            return $this->error($result['message'] ?? '更新订单状态失败');
        }

        return $this->success([
            'order_no' => $order->order_no,
            'status' => $order->status,
        ], $result['message'] ?? '订单状态更新成功');
    }

    /**
     * 订单退款
     */
    public function refundOrder(Request $request, string $id)
    {
        if ($response = $this->requirePaymentManagePermission('无权限执行退款')) {
            return $response;
        }

        $identifier = trim($id !== '' ? $id : (string) $request->param('id', ''));
        if ($identifier === '') {
            return $this->error('订单标识不能为空');
        }

        $order = $this->findOrderByIdentifier($identifier);
        if (!$order) {
            return $this->error('订单不存在', 404);
        }

        $reason = trim((string) $request->param('reason', '管理员手动退款')) ?: '管理员手动退款';
        $result = $this->applyStatusTransition($order, RechargeOrder::STATUS_REFUNDED, $reason);
        if (($result['success'] ?? false) !== true) {
            return $this->error($result['message'] ?? '退款失败');
        }

        return $this->success([
            'order_no' => $order->order_no,
            'status' => $order->status,
            'refund_no' => $order->refund_no,
        ], '退款成功');
    }

    
    /**
     * 获取充值统计
     */
    public function getStats()
    {
        if ($response = $this->requirePaymentViewPermission('无权限查看充值统计')) {
            return $response;
        }

        $params = $this->request->get();
        $startDate = trim((string) ($params['start_date'] ?? date('Y-m-01')));
        $endDate = trim((string) ($params['end_date'] ?? date('Y-m-d')));

        if (!$this->isDateOnly($startDate)) {
            return $this->error('开始日期格式无效');
        }
        if (!$this->isDateOnly($endDate)) {
            return $this->error('结束日期格式无效');
        }
        if ($startDate > $endDate) {
            return $this->error('开始日期不能晚于结束日期');
        }

        try {
            $rechargeOrderTable = $this->resolveRechargeOrderTable();
            if ($rechargeOrderTable === null) {
                return $this->success([
                    'total_amount' => 0,
                    'total_points' => 0,
                    'order_count' => 0,
                    'user_count' => 0,
                    'pending_count' => 0,
                    'avg_amount' => 0,
                ]);
            }

            $paidBaseQuery = Db::table($rechargeOrderTable)
                ->where('pay_time', '>=', $startDate . ' 00:00:00')
                ->where('pay_time', '<=', $endDate . ' 23:59:59');
            $this->applyRechargeOrderStatusFilter($paidBaseQuery, 'paid');

            $totalAmount = (float) ((clone $paidBaseQuery)->sum('amount') ?? 0);
            $totalPoints = (int) ((clone $paidBaseQuery)->sum('points') ?? 0);
            $orderCount = (int) (clone $paidBaseQuery)->count();
            $rechargeOrderColumns = $this->getRechargeOrderColumns();
            $paidUserIds = isset($rechargeOrderColumns['user_id'])
                ? (clone $paidBaseQuery)->column('user_id')
                : [];
            $userCount = count(array_unique(array_filter(array_map('intval', (array) $paidUserIds), static fn (int $userId): bool => $userId > 0)));

            $pendingBaseQuery = Db::table($rechargeOrderTable)
                ->where('created_at', '>=', $startDate . ' 00:00:00')
                ->where('created_at', '<=', $endDate . ' 23:59:59');
            $this->applyRechargeOrderStatusFilter($pendingBaseQuery, 'pending');
            $pendingCount = (int) $pendingBaseQuery->count();

            // 补充最近7天充值趋势图表数据
            $chartDates = [];
            $chartAmounts = [];
            $chartCounts = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-{$i} days"));
                $dayQuery = Db::table($rechargeOrderTable)
                    ->where('pay_time', '>=', $date . ' 00:00:00')
                    ->where('pay_time', '<=', $date . ' 23:59:59');
                $this->applyRechargeOrderStatusFilter($dayQuery, 'paid');
                $chartDates[]   = $date;
                $chartAmounts[] = round((float) ((clone $dayQuery)->sum('amount') ?? 0), 2);
                $chartCounts[]  = (int) (clone $dayQuery)->count();
            }

            return $this->success([
                'total_amount' => round($totalAmount, 2),
                'total_points' => $totalPoints,
                'order_count' => $orderCount,
                'user_count' => $userCount,
                'pending_count' => $pendingCount,
                'avg_amount' => $orderCount > 0 ? round($totalAmount / $orderCount, 2) : 0,
                'chart_data' => [
                    'dates'   => $chartDates,
                    'amounts' => $chartAmounts,
                    'counts'  => $chartCounts,
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_payment_stats', $e, '获取充值统计失败，请稍后重试', [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);
        }
    }

    
    /**
     * 获取近7天充值趋势
     */
    public function getTrend()
    {
        if ($response = $this->requirePaymentViewPermission('无权限查看支付趋势')) {
            return $response;
        }

        $days = 7;
        $data = [];

        $rechargeOrderTable = $this->resolveRechargeOrderTable();
        if ($rechargeOrderTable === null) {
            for ($i = $days - 1; $i >= 0; $i--) {
                $data[] = [
                    'date' => date('Y-m-d', strtotime("-{$i} days")),
                    'amount' => 0,
                    'count' => 0,
                ];
            }

            return $this->success($data);
        }

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));

            $baseQuery = Db::table($rechargeOrderTable)
                ->where('pay_time', '>=', $date . ' 00:00:00')
                ->where('pay_time', '<=', $date . ' 23:59:59');
            $this->applyRechargeOrderStatusFilter($baseQuery, 'paid');

            $amount = (float) ((clone $baseQuery)->sum('amount') ?? 0);
            $count = (int) (clone $baseQuery)->count();

            $data[] = [
                'date' => $date,
                'amount' => round($amount, 2),
                'count' => $count,
            ];
        }

        return $this->success($data);
    }

    /**
     * 按订单ID、订单号或支付单号查找订单
     */
    protected function findOrderByIdentifier(string $identifier): ?RechargeOrder
    {
        if ($identifier === '') {
            return null;
        }

        $query = RechargeOrder::with('user');
        if (ctype_digit($identifier)) {
            $byId = $query->find((int) $identifier);
            if ($byId) {
                return $byId;
            }
        }

        $rechargeOrderColumns = $this->getRechargeOrderColumns();

        return RechargeOrder::with('user')
            ->where(function ($query) use ($identifier, $rechargeOrderColumns) {
                $query->where('order_no', $identifier);

                if (isset($rechargeOrderColumns['pay_order_no'])) {
                    $query->whereOr('pay_order_no', $identifier);
                }
                if (isset($rechargeOrderColumns['transaction_id'])) {
                    $query->whereOr('transaction_id', $identifier);
                }
                if (isset($rechargeOrderColumns['refund_no'])) {
                    $query->whereOr('refund_no', $identifier);
                }
            })
            ->find();
    }


    /**
     * 统一处理订单状态流转
     */
    protected function applyStatusTransition(RechargeOrder $order, string $targetStatus, string $remark = ''): array
    {
        if (!in_array($targetStatus, self::ORDER_STATUSES, true)) {
            return ['success' => false, 'message' => '订单状态参数无效'];
        }

        if ($targetStatus === RechargeOrder::STATUS_PENDING) {
            return ['success' => false, 'message' => '不支持手动改回待支付状态'];
        }

        if ($order->status === $targetStatus) {
            $order->refresh();
            return ['success' => true, 'message' => '订单状态未变化'];
        }

        return match ($targetStatus) {
            RechargeOrder::STATUS_PAID => $this->performManualComplete($order, $remark),
            RechargeOrder::STATUS_CANCELLED => $this->performCancel($order, $remark),
            RechargeOrder::STATUS_REFUNDED => $this->performRefund($order, $remark),
            default => ['success' => false, 'message' => '订单状态参数无效'],
        };
    }

    /**
     * 执行手动补单
     */
    protected function performManualComplete(RechargeOrder $order, string $remark): array
    {
        if ($order->status !== RechargeOrder::STATUS_PENDING) {
            return ['success' => false, 'message' => '该订单状态不允许补单'];
        }

        $result = $order->markAsPaid($this->buildManualPayOrderNo($order), [
            'remark' => $remark,
            'admin_id' => $this->getOperatorId(),
        ]);

        if (($result['success'] ?? false) !== true) {
            return ['success' => false, 'message' => $result['message'] ?? '补单失败'];
        }

        $order->refresh();
        return ['success' => true, 'message' => '补单成功'];
    }

    /**
     * 执行取消订单
     */
    protected function performCancel(RechargeOrder $order, string $remark): array
    {
        if ($order->status === RechargeOrder::STATUS_CANCELLED) {
            $order->refresh();
            return ['success' => true, 'message' => '订单已取消'];
        }

        if ($order->status !== RechargeOrder::STATUS_PENDING) {
            return ['success' => false, 'message' => '该订单状态不允许取消'];
        }

        if (!$order->cancel()) {
            return ['success' => false, 'message' => '取消失败'];
        }

        $this->logOperation('cancel_recharge_order', 'recharge_order', [
            'target_id' => $order->id,
            'detail' => '取消充值订单：' . $order->order_no,
            'after_data' => [
                'status' => RechargeOrder::STATUS_CANCELLED,
                'remark' => $remark,
            ],
        ]);

        $order->refresh();
        return ['success' => true, 'message' => '订单已取消'];
    }

    /**
     * 生成手动补单用的支付流水号
     */
    protected function buildManualPayOrderNo(RechargeOrder $order): string
    {
        return 'MANUAL_' . date('YmdHis') . '_' . str_pad((string) $order->id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * 执行退款并回退积分
     */
    protected function performRefund(RechargeOrder $order, string $reason): array
    {
        $reason = trim($reason) ?: '管理员手动退款';

        if ($order->status === RechargeOrder::STATUS_REFUNDED) {
            $order->refresh();
            return ['success' => true, 'message' => '订单已退款'];
        }

        if ($order->status !== RechargeOrder::STATUS_PAID) {
            return ['success' => false, 'message' => '仅已支付订单允许退款'];
        }

        Db::startTrans();
        try {
            $rechargeOrderTable = $this->resolveRechargeOrderTable();
            if ($rechargeOrderTable === null) {
                Db::rollback();
                return ['success' => false, 'message' => '订单表未初始化'];
            }

            $userTable = $this->resolveUserTable();
            if ($userTable === null) {
                Db::rollback();
                return ['success' => false, 'message' => '用户表未初始化'];
            }

            $orderColumns = $this->getRechargeOrderColumns();
            $lockedOrder = Db::table($rechargeOrderTable)
                ->where('id', $order->id)
                ->lock(true)
                ->find();

            if (!$lockedOrder) {
                Db::rollback();
                return ['success' => false, 'message' => '订单不存在'];
            }

            if ($this->extractRechargeOrderStatus($lockedOrder) === RechargeOrder::STATUS_REFUNDED) {
                Db::commit();
                $order->refresh();
                return ['success' => true, 'message' => '订单已退款'];
            }

            if ($this->extractRechargeOrderStatus($lockedOrder) !== RechargeOrder::STATUS_PAID) {
                Db::rollback();
                return ['success' => false, 'message' => '当前订单状态不允许退款'];
            }

            $refundAmount = round((float) ($lockedOrder['amount'] ?? $order->amount), 2);
            if ($refundAmount <= 0) {
                Db::rollback();
                return ['success' => false, 'message' => '退款金额无效'];
            }

            $refundNo = trim((string) ($lockedOrder['refund_no'] ?? ''));
            if ($refundNo === '') {
                $refundNo = 'RFD' . date('YmdHis') . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            }

            $refundResult = WechatPayService::refund(
                (string) ($lockedOrder['order_no'] ?? $order->order_no),
                $refundNo,
                (float) ($lockedOrder['amount'] ?? $order->amount),
                $refundAmount,
                $reason
            );

            if (($refundResult['success'] ?? false) !== true) {
                Db::rollback();
                return ['success' => false, 'message' => (string) ($refundResult['message'] ?? '微信退款失败')];
            }

            $user = Db::table($userTable)
                ->where('id', $order->user_id)
                ->lock(true)
                ->find();

            if (!$user) {
                Db::rollback();
                return ['success' => false, 'message' => '用户不存在'];
            }

            if (!isset($user['points'])) {
                Db::rollback();
                return ['success' => false, 'message' => '用户积分字段不存在'];
            }

            $currentPoints = (int) ($user['points'] ?? 0);
            if ($currentPoints < (int) $order->points) {
                Db::rollback();
                return ['success' => false, 'message' => '用户当前积分不足，无法执行退款'];
            }

            Db::table($userTable)
                ->where('id', $order->user_id)
                ->dec('points', (int) $order->points)
                ->update();

            $this->insertRefundPointsRecordCompat(
                (int) $order->user_id,
                (int) $order->id,
                -((int) $order->points),
                $currentPoints,
                $currentPoints - (int) $order->points,
                $reason
            );

            $refundTime = date('Y-m-d H:i:s');
            $orderUpdatePayload = [];
            if (isset($orderColumns['status'])) {
                $orderUpdatePayload['status'] = RechargeOrder::STATUS_REFUNDED;
            }
            if (isset($orderColumns['pay_status'])) {
                $orderUpdatePayload['pay_status'] = 3;
            }
            if (isset($orderColumns['refund_no'])) {
                $orderUpdatePayload['refund_no'] = $refundNo;
            }
            if (isset($orderColumns['refund_amount'])) {
                $orderUpdatePayload['refund_amount'] = $refundAmount;
            }
            if (isset($orderColumns['refund_time'])) {
                $orderUpdatePayload['refund_time'] = $refundTime;
            }
            if (isset($orderColumns['refund_reason'])) {
                $orderUpdatePayload['refund_reason'] = $reason;
            }
            if (isset($orderColumns['wechat_refund_id'])) {
                $orderUpdatePayload['wechat_refund_id'] = (string) ($refundResult['refund_id'] ?? '');
            }
            if (isset($orderColumns['refund_response'])) {
                $orderUpdatePayload['refund_response'] = json_encode($refundResult, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            if (isset($orderColumns['updated_at'])) {
                $orderUpdatePayload['updated_at'] = $refundTime;
            }

            if (empty($orderUpdatePayload)) {
                Db::rollback();
                return ['success' => false, 'message' => '订单退款字段不存在，无法回写退款结果'];
            }

            Db::table($rechargeOrderTable)
                ->where('id', $order->id)
                ->update($orderUpdatePayload);

            $this->logOperation('refund_recharge_order', 'recharge_order', [
                'target_id' => $order->id,
                'detail' => sprintf('充值订单退款成功：%s，退款金额 %.2f', $order->order_no, $refundAmount),
                'after_data' => [
                    'refund_no' => $refundNo,
                    'refund_amount' => $refundAmount,
                    'refund_reason' => $reason,
                ],
            ]);

            Db::commit();
            $order->refresh();

            return [
                'success' => true,
                'message' => '退款成功',
                'refund_no' => $refundNo,
            ];
        } catch (\Throwable $e) {
            Db::rollback();
            Log::error('后台订单退款失败: ' . $e->getMessage(), [
                'order_id' => $order->id,
                'order_no' => $order->order_no,
                'admin_id' => $this->getOperatorId(),
            ]);

            return ['success' => false, 'message' => '退款失败，请稍后重试'];
        }
    }


    /**
     * 统一应用充值订单状态筛选，兼容 status / pay_status 两套字段
     */
    protected function applyRechargeOrderStatusFilter($query, string $status): void
    {
        $columns = $this->getRechargeOrderColumns();
        $normalizedStatus = $this->normalizeRechargeOrderStatus((string) $status);
        $legacyPayStatus = $this->mapRechargeLegacyPayStatus($normalizedStatus);

        if (isset($columns['status']) && isset($columns['pay_status']) && $legacyPayStatus !== null) {
            $query->whereRaw(sprintf(
                "(`status` = '%s' OR ((`status` IS NULL OR `status` = '') AND `pay_status` = %d))",
                $normalizedStatus,
                $legacyPayStatus
            ));
            return;
        }

        if (isset($columns['status'])) {
            $query->where('status', $normalizedStatus !== '' ? $normalizedStatus : $status);
            return;
        }

        if (isset($columns['pay_status']) && $legacyPayStatus !== null) {
            $query->where('pay_status', $legacyPayStatus);
            return;
        }

        if ($normalizedStatus === RechargeOrder::STATUS_REFUNDED) {
            $query->where(function ($refundQuery) {
                $refundQuery->whereNotNull('refund_time')
                    ->whereOr('refund_no', '<>', '')
                    ->whereOr('refund_amount', '>', 0);
            });
            return;
        }

        if ($normalizedStatus === RechargeOrder::STATUS_PAID) {
            $query->where(function ($paidQuery) {
                $paidQuery->whereNotNull('pay_time')
                    ->whereOr('pay_order_no', '<>', '')
                    ->whereOr('transaction_id', '<>', '');
            });
            return;
        }

        if ($normalizedStatus === RechargeOrder::STATUS_PENDING) {
            $query->whereNull('pay_time');
        }
    }

    protected function buildRechargeOrderPayload(RechargeOrder $order, bool $includeUserPhone = false): array
    {
        $orderRow = $order->getData();
        $payload = [
            'id' => (int) ($orderRow['id'] ?? $order->id),
            'order_no' => (string) ($orderRow['order_no'] ?? $order->order_no),
            'pay_order_no' => $this->extractRechargeOrderPayOrderNo($orderRow),
            'refund_no' => (string) ($orderRow['refund_no'] ?? ''),
            'user_id' => (int) ($orderRow['user_id'] ?? $order->user_id),
            'user_nickname' => $order->user ? (string) $order->user->nickname : '未知用户',
            'amount' => $orderRow['amount'] ?? $order->amount,
            'points' => $orderRow['points'] ?? $order->points,
            'status' => $this->extractRechargeOrderStatus($orderRow),
            'payment_type' => $this->extractRechargeOrderPaymentType($orderRow),
            'pay_time' => $orderRow['pay_time'] ?? null,
            'refund_amount' => $orderRow['refund_amount'] ?? null,
            'refund_time' => $orderRow['refund_time'] ?? null,
            'created_at' => $orderRow['created_at'] ?? null,
            'updated_at' => $orderRow['updated_at'] ?? null,
        ];

        if ($includeUserPhone) {
            $payload['wechat_refund_id'] = (string) ($orderRow['wechat_refund_id'] ?? '');
            $payload['user_phone'] = $order->user ? (string) $order->user->phone : '';
            $payload['refund_reason'] = (string) ($orderRow['refund_reason'] ?? '');
            $payload['refund_response'] = $orderRow['refund_response'] ?? null;
            $payload['expire_time'] = $orderRow['expire_time'] ?? null;
            $payload['client_ip'] = (string) ($orderRow['client_ip'] ?? '');
        }

        return $payload;
    }

    protected function extractRechargeOrderStatus(array $orderRow): string
    {
        $status = $this->normalizeRechargeOrderStatus((string) ($orderRow['status'] ?? ''));
        if ($status !== '') {
            return $status;
        }

        $legacyStatus = $this->mapRechargeLegacyPayStatusToCurrent($orderRow['pay_status'] ?? null);
        if ($legacyStatus !== null) {
            return $legacyStatus;
        }

        if (!empty($orderRow['refund_time'])
            || trim((string) ($orderRow['refund_no'] ?? '')) !== ''
            || (float) ($orderRow['refund_amount'] ?? 0) > 0) {
            return RechargeOrder::STATUS_REFUNDED;
        }

        if (!empty($orderRow['pay_time'])
            || trim((string) ($orderRow['pay_order_no'] ?? '')) !== ''
            || trim((string) ($orderRow['transaction_id'] ?? '')) !== '') {
            return RechargeOrder::STATUS_PAID;
        }

        return RechargeOrder::STATUS_PENDING;
    }

    protected function extractRechargeOrderPaymentType(array $orderRow): string
    {
        $paymentType = $this->normalizeRechargePaymentType((string) ($orderRow['payment_type'] ?? ''));
        if ($paymentType !== '') {
            return $paymentType;
        }

        $legacyPaymentType = $this->normalizeRechargePaymentType((string) ($orderRow['pay_type'] ?? ''));
        if ($legacyPaymentType !== '') {
            return $legacyPaymentType;
        }

        return '';
    }

    protected function extractRechargeOrderPayOrderNo(array $orderRow): string
    {
        $payOrderNo = trim((string) ($orderRow['pay_order_no'] ?? ''));
        if ($payOrderNo !== '') {
            return $payOrderNo;
        }

        return trim((string) ($orderRow['transaction_id'] ?? ''));
    }

    protected function normalizeRechargeOrderStatus(string $status): string
    {
        $normalized = strtolower(trim($status));
        $allowedStatuses = array_merge(self::ORDER_STATUSES, [RechargeOrder::STATUS_PROCESSING]);
        return in_array($normalized, $allowedStatuses, true) ? $normalized : '';
    }

    protected function normalizeRechargePaymentType(string $paymentType): string
    {
        $normalized = strtolower(trim($paymentType));
        if ($normalized === '') {
            return '';
        }

        if ($normalized === 'wechat_jsapi') {
            return 'wechat_jsapi';
        }
        if ($normalized === 'alipay' || strpos($normalized, 'alipay') !== false) {
            return 'alipay';
        }
        if (in_array($normalized, ['wechat', 'wxpay', 'weixin'], true) || strpos($normalized, 'wechat') !== false) {
            return 'wechat';
        }

        return $normalized;
    }

    protected function mapRechargeLegacyPayStatus(string $status): ?int
    {
        return match ($status) {
            RechargeOrder::STATUS_PENDING => 0,
            RechargeOrder::STATUS_PAID => 1,
            RechargeOrder::STATUS_CANCELLED => 2,
            RechargeOrder::STATUS_REFUNDED => 3,
            default => null,
        };
    }

    protected function mapRechargeLegacyPayStatusToCurrent(mixed $status): ?string
    {
        if ($status === null || $status === '') {
            return null;
        }

        return match ((int) $status) {
            1 => RechargeOrder::STATUS_PAID,
            2 => RechargeOrder::STATUS_CANCELLED,
            3 => RechargeOrder::STATUS_REFUNDED,
            0 => RechargeOrder::STATUS_PENDING,
            default => null,
        };
    }


    protected function resolveRechargeOrderTable(): ?string
    {
        foreach (['tc_recharge_order', 'recharge_order'] as $table) {
            if (SchemaInspector::tableExists($table)) {
                return $table;
            }
        }

        return null;
    }

    protected function resolveUserTable(): ?string
    {
        foreach (['tc_user', 'user'] as $table) {
            if (SchemaInspector::tableExists($table)) {
                return $table;
            }
        }

        return null;
    }

    protected function resolvePointsRecordTable(): ?string
    {
        foreach (['tc_points_record', 'points_record'] as $table) {
            if (SchemaInspector::tableExists($table)) {
                return $table;
            }
        }

        return null;
    }

    protected function getRechargeOrderColumns(): array
    {
        $table = $this->resolveRechargeOrderTable();
        return $table === null ? [] : SchemaInspector::getTableColumns($table);
    }

    protected function getPointsRecordColumns(): array
    {
        $table = $this->resolvePointsRecordTable();
        return $table === null ? [] : SchemaInspector::getTableColumns($table);
    }

    protected function insertRefundPointsRecordCompat(
        int $userId,
        int $orderId,
        int $points,
        int $beforePoints,
        int $afterPoints,
        string $reason
    ): void {
        $pointsRecordTable = $this->resolvePointsRecordTable();
        if ($pointsRecordTable === null) {
            return;
        }

        $columns = $this->getPointsRecordColumns();
        $payload = [];
        if (isset($columns['user_id'])) {
            $payload['user_id'] = $userId;
        }
        if (isset($columns['action'])) {
            $payload['action'] = '充值退款';
        }
        if (isset($columns['points'])) {
            $payload['points'] = $points;
        }
        if (isset($columns['type'])) {
            $payload['type'] = 'refund';
        }
        if (isset($columns['related_id'])) {
            $payload['related_id'] = $orderId;
        }
        if (isset($columns['remark'])) {
            $payload['remark'] = $reason;
        }
        if (isset($columns['description'])) {
            $payload['description'] = $reason;
        }
        if (isset($columns['amount'])) {
            $payload['amount'] = abs($points);
        }
        if (isset($columns['balance'])) {
            $payload['balance'] = $afterPoints;
        }
        if (isset($columns['reason'])) {
            $payload['reason'] = $reason;
        }
        if (isset($columns['source_id'])) {
            $payload['source_id'] = $orderId;
        }
        if (isset($columns['source_type'])) {
            $payload['source_type'] = 'recharge_refund';
        }
        if (isset($columns['before_balance'])) {
            $payload['before_balance'] = $beforePoints;
        }
        if (isset($columns['after_balance'])) {
            $payload['after_balance'] = $afterPoints;
        }
        if (isset($columns['created_at'])) {
            $payload['created_at'] = date('Y-m-d H:i:s');
        }
        if (isset($columns['updated_at'])) {
            $payload['updated_at'] = date('Y-m-d H:i:s');
        }

        if (!empty($payload)) {
            Db::table($pointsRecordTable)->insert($payload);
        }
    }

    /**
     * 校验 YYYY-MM-DD 日期
     */
    protected function isDateOnly(string $value): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1;
    }
}
