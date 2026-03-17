<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\PaymentConfig;
use app\model\RechargeOrder;
use app\model\User;
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
        $startDate = trim((string) $request->get('start_date', ''));
        $endDate = trim((string) $request->get('end_date', ''));
        
        $query = RechargeOrder::with('user');
        
        if ($status !== '') {
            if (!in_array($status, self::ORDER_STATUSES, true)) {
                return $this->error('订单状态参数无效');
            }
            $query->where('status', $status);
        }

        if ($keyword !== '') {
            $keyword = preg_replace('/[%_\\\\]/', '', $keyword) ?: '';
            $matchedUserIds = User::whereLike('nickname', '%' . $keyword . '%')->column('id');

            $query->where(function ($q) use ($keyword, $matchedUserIds) {
                $q->whereLike('order_no', '%' . $keyword . '%')
                    ->whereOrLike('pay_order_no', '%' . $keyword . '%');

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
            $list[] = [
                'id' => $order->id,
                'order_no' => $order->order_no,
                'pay_order_no' => $order->pay_order_no,
                'user_id' => $order->user_id,
                'user_nickname' => $order->user ? $order->user->nickname : '未知用户',
                'amount' => $order->amount,
                'points' => $order->points,
                'status' => $order->status,
                'payment_type' => $order->payment_type,
                'pay_time' => $order->pay_time,
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
            ];
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

        try {
            $query = RechargeOrder::with('user');

            if ($status !== '') {
                if (!in_array($status, self::ORDER_STATUSES, true)) {
                    return $this->error('订单状态参数无效');
                }
                $query->where('status', $status);
            }

            if ($keyword !== '') {
                $keyword = preg_replace('/[%_\\]/', '', $keyword) ?: '';
                $matchedUserIds = User::whereLike('nickname', '%' . $keyword . '%')->column('id');
                $query->where(function ($q) use ($keyword, $matchedUserIds) {
                    $q->whereLike('order_no', '%' . $keyword . '%')
                        ->whereOrLike('pay_order_no', '%' . $keyword . '%');

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
            $csv = "\xEF\xBB\xBF" . implode(',', ['订单号', '支付单号', '用户ID', '用户昵称', '金额', '积分', '状态', '支付方式', '支付时间', '创建时间']) . "\n";

            foreach ($orders as $order) {
                $row = [
                    $this->escapeCsv((string) $order->order_no),
                    $this->escapeCsv((string) ($order->pay_order_no ?? '')),
                    (string) $order->user_id,
                    $this->escapeCsv((string) ($order->user ? $order->user->nickname : '未知用户')),
                    (string) $order->amount,
                    (string) $order->points,
                    $this->escapeCsv((string) $order->status),
                    $this->escapeCsv((string) $order->payment_type),
                    (string) ($order->pay_time ?? ''),
                    (string) ($order->created_at ?? ''),
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
        
        return $this->success([
            'id' => $order->id,
            'order_no' => $order->order_no,
            'pay_order_no' => $order->pay_order_no,
            'user_id' => $order->user_id,
            'user_nickname' => $order->user ? $order->user->nickname : '未知用户',
            'user_phone' => $order->user ? $order->user->phone : '',
            'amount' => $order->amount,
            'points' => $order->points,
            'status' => $order->status,
            'payment_type' => $order->payment_type,
            'pay_time' => $order->pay_time,
            'expire_time' => $order->expire_time,
            'client_ip' => $order->client_ip,
            'created_at' => $order->created_at,
            'updated_at' => $order->updated_at,
        ]);
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
        
        if ($order->status !== RechargeOrder::STATUS_PENDING) {
            return $this->error('该订单状态不允许补单');
        }
        
        $remark = trim((string) $request->param('remark', '管理员手动补单')) ?: '管理员手动补单';
        $result = $order->markAsPaid('MANUAL_' . date('YmdHis'), [
            'remark' => $remark,
            'admin_id' => $this->getOperatorId(),
        ]);

        if (($result['success'] ?? false) !== true) {
            return $this->error($result['message'] ?? '补单失败');
        }

        $order->refresh();
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
        $identifier = trim($id !== '' ? $id : (string) $request->param('id', ''));
        if ($identifier === '') {
            return $this->error('订单标识不能为空');
        }

        $order = $this->findOrderByIdentifier($identifier);
        if (!$order) {
            return $this->error('订单不存在', 404);
        }
        
        if ($order->status !== RechargeOrder::STATUS_PENDING) {
            return $this->error('该订单状态不允许取消');
        }
        
        if ($order->cancel()) {
            return $this->success([
                'order_no' => $order->order_no,
                'status' => RechargeOrder::STATUS_CANCELLED,
            ], '订单已取消');
        }
        
        return $this->error('取消失败');
    }

    /**
     * 更新订单状态
     */
    public function updateOrderStatus(Request $request, string $id)
    {
        $identifier = trim($id !== '' ? $id : (string) $request->param('id', ''));
        $status = trim((string) $request->param('status', ''));

        if ($identifier === '') {
            return $this->error('订单标识不能为空');
        }
        if (!in_array($status, self::ORDER_STATUSES, true)) {
            return $this->error('订单状态参数无效');
        }

        $order = $this->findOrderByIdentifier($identifier);
        if (!$order) {
            return $this->error('订单不存在', 404);
        }

        if ($order->status === $status) {
            return $this->success([
                'order_no' => $order->order_no,
                'status' => $order->status,
            ], '订单状态未变化');
        }

        return match ($status) {
            RechargeOrder::STATUS_PAID => $this->manualComplete($request, $identifier),
            RechargeOrder::STATUS_CANCELLED => $this->cancelOrder($request, $identifier),
            RechargeOrder::STATUS_REFUNDED => $this->refundOrder($request, $identifier),
            RechargeOrder::STATUS_PENDING => $this->error('不支持手动改回待支付状态'),
            default => $this->error('订单状态参数无效'),
        };
    }

    /**
     * 订单退款
     */
    public function refundOrder(Request $request, string $id)
    {
        $identifier = trim($id !== '' ? $id : (string) $request->param('id', ''));
        if ($identifier === '') {
            return $this->error('订单标识不能为空');
        }

        $order = $this->findOrderByIdentifier($identifier);
        if (!$order) {
            return $this->error('订单不存在', 404);
        }

        $reason = trim((string) $request->param('reason', '管理员手动退款')) ?: '管理员手动退款';
        $result = $this->performRefund($order, $reason);
        if (($result['success'] ?? false) !== true) {
            return $this->error($result['message'] ?? '退款失败');
        }

        return $this->success([
            'order_no' => $order->order_no,
            'status' => $order->status,
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

        $startDate = $params['start_date'] ?? date('Y-m-01');
        $endDate = $params['end_date'] ?? date('Y-m-d');
        
        $totalAmount = RechargeOrder::where('status', RechargeOrder::STATUS_PAID)
            ->where('pay_time', '>=', $startDate . ' 00:00:00')
            ->where('pay_time', '<=', $endDate . ' 23:59:59')
            ->sum('amount');
        
        $totalPoints = RechargeOrder::where('status', RechargeOrder::STATUS_PAID)
            ->where('pay_time', '>=', $startDate . ' 00:00:00')
            ->where('pay_time', '<=', $endDate . ' 23:59:59')
            ->sum('points');
        
        $orderCount = RechargeOrder::where('status', RechargeOrder::STATUS_PAID)
            ->where('pay_time', '>=', $startDate . ' 00:00:00')
            ->where('pay_time', '<=', $endDate . ' 23:59:59')
            ->count();
        
        $userCount = RechargeOrder::where('status', RechargeOrder::STATUS_PAID)
            ->where('pay_time', '>=', $startDate . ' 00:00:00')
            ->where('pay_time', '<=', $endDate . ' 23:59:59')
            ->distinct('user_id')
            ->count();
        
        $pendingCount = RechargeOrder::where('status', RechargeOrder::STATUS_PENDING)
            ->where('created_at', '>=', $startDate . ' 00:00:00')
            ->where('created_at', '<=', $endDate . ' 23:59:59')
            ->count();
        
        return $this->success([
            'total_amount' => round($totalAmount, 2),
            'total_points' => (int) $totalPoints,
            'order_count' => $orderCount,
            'user_count' => $userCount,
            'pending_count' => $pendingCount,
            'avg_amount' => $orderCount > 0 ? round($totalAmount / $orderCount, 2) : 0,
        ]);
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
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            
            $amount = RechargeOrder::where('status', RechargeOrder::STATUS_PAID)
                ->where('pay_time', '>=', $date . ' 00:00:00')
                ->where('pay_time', '<=', $date . ' 23:59:59')
                ->sum('amount');
            
            $count = RechargeOrder::where('status', RechargeOrder::STATUS_PAID)
                ->where('pay_time', '>=', $date . ' 00:00:00')
                ->where('pay_time', '<=', $date . ' 23:59:59')
                ->count();
            
            $data[] = [
                'date' => $date,
                'amount' => round($amount, 2),
                'count' => $count,
            ];
        }
        
        return $this->success($data);
    }

    /**
     * 按订单ID或订单号查找订单
     */
    protected function findOrderByIdentifier(string $identifier): ?RechargeOrder
    {
        if ($identifier === '') {
            return null;
        }

        if (ctype_digit($identifier)) {
            $byId = RechargeOrder::with('user')->find((int) $identifier);
            if ($byId) {
                return $byId;
            }
        }

        return RechargeOrder::with('user')->where('order_no', $identifier)->find();
    }

    /**
     * 执行退款并回退积分
     */
    protected function performRefund(RechargeOrder $order, string $reason): array
    {
        if ($order->status === RechargeOrder::STATUS_REFUNDED) {
            return ['success' => true, 'message' => '订单已退款'];
        }

        if ($order->status !== RechargeOrder::STATUS_PAID) {
            return ['success' => false, 'message' => '仅已支付订单允许退款'];
        }

        Db::startTrans();
        try {
            $lockedOrder = Db::name('recharge_order')
                ->where('id', $order->id)
                ->lock(true)
                ->find();

            if (!$lockedOrder) {
                Db::rollback();
                return ['success' => false, 'message' => '订单不存在'];
            }

            if (($lockedOrder['status'] ?? '') === RechargeOrder::STATUS_REFUNDED) {
                Db::commit();
                $order->refresh();
                return ['success' => true, 'message' => '订单已退款'];
            }

            if (($lockedOrder['status'] ?? '') !== RechargeOrder::STATUS_PAID) {
                Db::rollback();
                return ['success' => false, 'message' => '当前订单状态不允许退款'];
            }

            $user = Db::name('user')
                ->where('id', $order->user_id)
                ->lock(true)
                ->find();

            if (!$user) {
                Db::rollback();
                return ['success' => false, 'message' => '用户不存在'];
            }

            $currentPoints = (int) ($user['points'] ?? 0);
            if ($currentPoints < (int) $order->points) {
                Db::rollback();
                return ['success' => false, 'message' => '用户当前积分不足，无法执行退款'];
            }

            Db::name('user')
                ->where('id', $order->user_id)
                ->dec('points', (int) $order->points)
                ->update();

            Db::name('points_record')->insert([
                'user_id' => $order->user_id,
                'action' => '充值退款',
                'points' => -((int) $order->points),
                'type' => 'refund',
                'related_id' => $order->id,
                'remark' => $reason,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            Db::name('recharge_order')
                ->where('id', $order->id)
                ->update([
                    'status' => RechargeOrder::STATUS_REFUNDED,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            Db::commit();
            $order->refresh();

            return ['success' => true, 'message' => '退款成功'];
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
     * 校验 YYYY-MM-DD 日期
     */
    protected function isDateOnly(string $value): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1;
    }
}
