<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\PaymentConfig;
use app\model\RechargeOrder;
use app\model\User;

/**
 * 后台支付管理控制器
 */
class AdminPayment extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];
    
    /**
     * 获取微信支付配置
     */
    public function getConfig()
    {
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
    public function saveConfig()
    {
        $data = $this->request->post();
        
        // 验证必要参数
        if (empty($data['mch_id'])) {
            return $this->error('商户号不能为空');
        }
        if (empty($data['app_id'])) {
            return $this->error('应用ID不能为空');
        }
        if (empty($data['notify_url'])) {
            return $this->error('回调地址不能为空');
        }
        
        // API密钥验证
        if (empty($data['api_key']) && empty($data['api_key_masked'])) {
            return $this->error('API密钥不能为空');
        }
        
        $saveData = [
            'type' => 'wechat_jsapi',
            'mch_id' => $data['mch_id'],
            'app_id' => $data['app_id'],
            'notify_url' => $data['notify_url'],
            'is_enabled' => $data['is_enabled'] ?? true,
            'api_key_masked' => !empty($data['api_key_masked']),
        ];
        
        // 只有提供了新密钥时才更新
        if (!empty($data['api_key'])) {
            $saveData['api_key'] = $data['api_key'];
            $saveData['api_key_masked'] = false;
        }
        
        // 处理证书文件
        if (!empty($data['api_cert'])) {
            $saveData['api_cert'] = $data['api_cert'];
        }
        if (!empty($data['api_key_pem'])) {
            $saveData['api_key_pem'] = $data['api_key_pem'];
        }
        
        if (PaymentConfig::saveConfig($saveData)) {
            return $this->success(null, '配置保存成功');
        }
        
        return $this->error('配置保存失败');
    }
    
    /**
     * 获取充值订单列表
     */
    public function getOrders()
    {
        $params = $this->request->get();
        
        $page = (int) ($params['page'] ?? 1);
        $limit = (int) ($params['limit'] ?? 20);
        $status = $params['status'] ?? '';
        $keyword = $params['keyword'] ?? '';
        $startDate = $params['start_date'] ?? '';
        $endDate = $params['end_date'] ?? '';
        
        $query = RechargeOrder::with('user');
        
        // 状态筛选
        if ($status && in_array($status, ['pending', 'paid', 'cancelled', 'refunded'])) {
            $query->where('status', $status);
        }
        
        // 关键词搜索（订单号、用户ID、用户昵称）
        if ($keyword) {
            $query->where(function($q) use ($keyword) {
                $q->where('order_no', 'like', "%{$keyword}%")
                  ->whereOr('pay_order_no', 'like', "%{$keyword}%")
                  ->whereOr('user_id', 'in', function($sq) use ($keyword) {
                      $sq->table('users')
                         ->where('nickname', 'like', "%{$keyword}%")
                         ->field('id');
                  });
            });
        }
        
        // 日期范围筛选
        if ($startDate) {
            $query->where('created_at', '>=', $startDate . ' 00:00:00');
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate . ' 23:59:59');
        }
        
        $total = $query->count();
        $orders = $query->order('created_at', 'desc')
            ->page($page, $limit)
            ->select();
        
        // 格式化数据
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
                'pay_time' => $order->pay_time,
                'created_at' => $order->created_at,
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
     * 获取订单详情
     */
    public function getOrderDetail()
    {
        $id = $this->request->get('id');
        
        if (!$id) {
            return $this->error('订单ID不能为空');
        }
        
        $order = RechargeOrder::with('user')->find($id);
        
        if (!$order) {
            return $this->error('订单不存在', 404);
        }
        
        $data = [
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
        ];
        
        return $this->success($data);
    }
    
    /**
     * 手动补单（管理员手动标记订单为已支付）
     */
    public function manualComplete()
    {
        $data = $this->request->post();
        
        if (empty($data['id'])) {
            return $this->error('订单ID不能为空');
        }
        
        $order = RechargeOrder::find($data['id']);
        
        if (!$order) {
            return $this->error('订单不存在', 404);
        }
        
        if ($order->status !== RechargeOrder::STATUS_PENDING) {
            return $this->error('该订单状态不允许补单');
        }
        
        $remark = $data['remark'] ?? '管理员手动补单';
        
        if ($order->markAsPaid('MANUAL_' . date('YmdHis'), ['remark' => $remark, 'admin_id' => $this->request->user['sub'] ?? 0])) {
            return $this->success(null, '补单成功');
        }
        
        return $this->error('补单失败');
    }
    
    /**
     * 取消订单
     */
    public function cancelOrder()
    {
        $id = $this->request->post('id');
        
        if (!$id) {
            return $this->error('订单ID不能为空');
        }
        
        $order = RechargeOrder::find($id);
        
        if (!$order) {
            return $this->error('订单不存在', 404);
        }
        
        if ($order->status !== RechargeOrder::STATUS_PENDING) {
            return $this->error('该订单状态不允许取消');
        }
        
        if ($order->cancel()) {
            return $this->success(null, '订单已取消');
        }
        
        return $this->error('取消失败');
    }
    
    /**
     * 获取充值统计
     */
    public function getStats()
    {
        $params = $this->request->get();
        $startDate = $params['start_date'] ?? date('Y-m-01');
        $endDate = $params['end_date'] ?? date('Y-m-d');
        
        // 总充值金额
        $totalAmount = RechargeOrder::where('status', RechargeOrder::STATUS_PAID)
            ->where('pay_time', '>=', $startDate . ' 00:00:00')
            ->where('pay_time', '<=', $endDate . ' 23:59:59')
            ->sum('amount');
        
        // 总充值积分
        $totalPoints = RechargeOrder::where('status', RechargeOrder::STATUS_PAID)
            ->where('pay_time', '>=', $startDate . ' 00:00:00')
            ->where('pay_time', '<=', $endDate . ' 23:59:59')
            ->sum('points');
        
        // 充值笔数
        $orderCount = RechargeOrder::where('status', RechargeOrder::STATUS_PAID)
            ->where('pay_time', '>=', $startDate . ' 00:00:00')
            ->where('pay_time', '<=', $endDate . ' 23:59:59')
            ->count();
        
        // 充值用户数
        $userCount = RechargeOrder::where('status', RechargeOrder::STATUS_PAID)
            ->where('pay_time', '>=', $startDate . ' 00:00:00')
            ->where('pay_time', '<=', $endDate . ' 23:59:59')
            ->distinct('user_id')
            ->count();
        
        // 待支付订单数
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
}
