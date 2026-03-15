<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\PaymentConfig;
use app\model\RechargeOrder;
use think\exception\HttpException;

/**
 * 微信支付控制器
 */
class Payment extends BaseController
{
    protected $middleware = [\app\middleware\Auth::class];
    
    /**
     * 充值金额配置（元 => 积分）
     */
    protected array $rechargeOptions = [
        10 => 100,
        30 => 330,
        50 => 580,
        100 => 1200,
        200 => 2500,
        500 => 6500,
    ];
    
    /**
     * 获取充值选项
     */
    public function getRechargeOptions()
    {
        $options = [];
        foreach ($this->rechargeOptions as $amount => $points) {
            $options[] = [
                'amount' => $amount,
                'points' => $points,
                'bonus' => $points - $amount * 10, // 赠送的积分
            ];
        }
        
        return $this->success([
            'options' => $options,
            'custom_enabled' => false, // 是否支持自定义金额
        ]);
    }
    
    /**
     * 创建充值订单（JSAPI统一下单）
     */
    public function createOrder()
    {
        $data = $this->request->post();
        $user = $this->request->user;
        
        // 参数验证
        if (empty($data['amount'])) {
            return $this->error('请选择充值金额');
        }
        
        $amount = (float) $data['amount'];
        
        // 验证金额是否在允许列表中
        if (!isset($this->rechargeOptions[$amount])) {
            return $this->error('充值金额不正确');
        }
        
        // 获取微信支付配置
        $config = PaymentConfig::getWechatConfig();
        if (!$config) {
            return $this->error('微信支付未配置', 503);
        }
        
        $points = $this->rechargeOptions[$amount];
        
        // 创建订单
        $order = RechargeOrder::createOrder(
            (int) $user['sub'],
            $amount,
            $points,
            $this->request->ip(),
            $this->request->header('User-Agent')
        );
        
        if (empty($order)) {
            return $this->error('订单创建失败');
        }
        
        // 调用微信支付统一下单
        try {
            $payParams = $this->unifiedOrder($order, $config, $user);
            
            return $this->success([
                'order_no' => $order['order_no'],
                'amount' => $order['amount'],
                'points' => $order['points'],
                'expire_time' => $order['expire_time'],
                'pay_params' => $payParams,
            ]);
        } catch (\Exception $e) {
            // 创建支付失败，标记订单为取消
            $orderModel = RechargeOrder::findByOrderNo($order['order_no']);
            if ($orderModel) {
                $orderModel->cancel();
            }
            
            return $this->error('创建支付订单失败：' . $e->getMessage());
        }
    }
    
    /**
     * 微信支付统一下单
     */
    protected function unifiedOrder(array $order, array $config, array $user): array
    {
        // 获取用户openid（需要用户表中有openid字段）
        $userModel = \app\model\User::find($user['sub']);
        if (!$userModel || empty($userModel->openid)) {
            throw new \Exception('用户未绑定微信');
        }
        
        $params = [
            'appid' => $config['app_id'],
            'mch_id' => $config['mch_id'],
            'nonce_str' => $this->generateNonceStr(),
            'body' => '积分充值-' . $order['points'] . '积分',
            'out_trade_no' => $order['order_no'],
            'total_fee' => (int) ($order['amount'] * 100), // 金额转换为分
            'spbill_create_ip' => $this->request->ip() ?: '127.0.0.1',
            'notify_url' => $config['notify_url'],
            'trade_type' => 'JSAPI',
            'openid' => $userModel->openid,
        ];
        
        // 生成签名
        $params['sign'] = $this->generateSign($params, $config['api_key']);
        
        // 转换为XML
        $xml = $this->arrayToXml($params);
        
        // 调用微信支付接口
        $response = $this->httpPost('https://api.mch.weixin.qq.com/pay/unifiedorder', $xml);
        
        // 解析XML响应
        $result = $this->xmlToArray($response);
        
        if ($result['return_code'] !== 'SUCCESS') {
            throw new \Exception($result['return_msg'] ?? '统一下单失败');
        }
        
        if ($result['result_code'] !== 'SUCCESS') {
            throw new \Exception($result['err_code_des'] ?? '业务失败');
        }
        
        // 生成前端调起支付所需参数
        $timeStamp = (string) time();
        $nonceStr = $this->generateNonceStr();
        $package = 'prepay_id=' . $result['prepay_id'];
        
        $payParams = [
            'appId' => $config['app_id'],
            'timeStamp' => $timeStamp,
            'nonceStr' => $nonceStr,
            'package' => $package,
            'signType' => 'MD5',
        ];
        
        // 生成支付签名
        $payParams['paySign'] = $this->generateSign($payParams, $config['api_key']);
        
        return $payParams;
    }
    
    /**
     * 查询订单状态
     */
    public function queryOrder()
    {
        $orderNo = $this->request->get('order_no');
        $user = $this->request->user;
        
        if (empty($orderNo)) {
            return $this->error('订单号不能为空');
        }
        
        $order = RechargeOrder::findByOrderNo($orderNo);
        
        if (!$order) {
            return $this->error('订单不存在', 404);
        }
        
        // 验证订单归属
        if ($order->user_id != $user['sub']) {
            return $this->error('无权查看该订单', 403);
        }
        
        // 如果订单待支付且已过期，更新状态
        if ($order->status === RechargeOrder::STATUS_PENDING && $order->isExpired()) {
            $order->cancel();
        }
        
        // 如果订单仍是待支付状态，主动查询微信支付状态
        if ($order->status === RechargeOrder::STATUS_PENDING) {
            $this->queryWechatOrder($order);
        }
        
        return $this->success([
            'order_no' => $order->order_no,
            'amount' => $order->amount,
            'points' => $order->points,
            'status' => $order->status,
            'pay_time' => $order->pay_time,
            'created_at' => $order->created_at,
        ]);
    }
    
    /**
     * 主动查询微信支付订单状态
     */
    protected function queryWechatOrder(RechargeOrder $order): void
    {
        $config = PaymentConfig::getWechatConfig();
        if (!$config) {
            return;
        }
        
        $params = [
            'appid' => $config['app_id'],
            'mch_id' => $config['mch_id'],
            'out_trade_no' => $order->order_no,
            'nonce_str' => $this->generateNonceStr(),
        ];
        
        $params['sign'] = $this->generateSign($params, $config['api_key']);
        $xml = $this->arrayToXml($params);
        
        try {
            $response = $this->httpPost('https://api.mch.weixin.qq.com/pay/orderquery', $xml);
            $result = $this->xmlToArray($response);
            
            if ($result['return_code'] === 'SUCCESS' && $result['result_code'] === 'SUCCESS') {
                if ($result['trade_state'] === 'SUCCESS') {
                    // 支付成功，更新订单
                    $order->markAsPaid($result['transaction_id'] ?? '', $result);
                } elseif (in_array($result['trade_state'], ['CLOSED', 'REVOKED'])) {
                    // 订单已关闭或撤销
                    $order->cancel();
                }
            }
        } catch (\Exception $e) {
            // 查询失败不影响返回，仅记录日志
            trace('查询微信支付订单失败：' . $e->getMessage(), 'error');
        }
    }
    
    /**
     * 微信支付回调通知处理
     * 注意：此接口不需要认证中间件
     */
    public function notify()
    {
        // 获取原始XML数据
        $xml = file_get_contents('php://input');
        
        if (empty($xml)) {
            return $this->xmlResponse('FAIL', '无效的数据');
        }
        
        // 解析XML
        $data = $this->xmlToArray($xml);
        
        if (empty($data)) {
            return $this->xmlResponse('FAIL', '解析失败');
        }
        
        // 验证返回状态
        if ($data['return_code'] !== 'SUCCESS') {
            return $this->xmlResponse('FAIL', $data['return_msg'] ?? '通信失败');
        }
        
        // 获取配置验证签名
        $config = PaymentConfig::getWechatConfig();
        if (!$config) {
            return $this->xmlResponse('FAIL', '配置错误');
        }
        
        // 验证签名
        $sign = $data['sign'] ?? '';
        unset($data['sign']);
        
        if ($this->generateSign($data, $config['api_key']) !== $sign) {
            return $this->xmlResponse('FAIL', '签名验证失败');
        }
        
        // 验证业务结果
        if ($data['result_code'] !== 'SUCCESS') {
            return $this->xmlResponse('SUCCESS'); // 业务失败也返回成功，避免微信重复通知
        }
        
        // 查找订单
        $orderNo = $data['out_trade_no'] ?? '';
        $order = RechargeOrder::findByOrderNo($orderNo);
        
        if (!$order) {
            return $this->xmlResponse('FAIL', '订单不存在');
        }
        
        // 如果订单已处理，直接返回成功
        if ($order->status === RechargeOrder::STATUS_PAID) {
            return $this->xmlResponse('SUCCESS');
        }
        
        // 验证订单金额
        $totalFee = (int) $data['total_fee'];
        $expectedFee = (int) ($order->amount * 100);
        
        if ($totalFee !== $expectedFee) {
            trace("订单金额不匹配：订单{$orderNo}，微信支付{$totalFee}分，系统记录{$expectedFee}分", 'error');
            return $this->xmlResponse('FAIL', '金额不匹配');
        }
        
        // 标记订单为已支付
        $payOrderNo = $data['transaction_id'] ?? '';
        if ($order->markAsPaid($payOrderNo, $data)) {
            return $this->xmlResponse('SUCCESS');
        }
        
        return $this->xmlResponse('FAIL', '处理失败');
    }
    
    /**
     * 获取用户的充值记录
     */
    public function getUserRechargeHistory()
    {
        $user = $this->request->user;
        $page = (int) $this->request->get('page', 1);
        $limit = (int) $this->request->get('limit', 10);
        
        $orders = RechargeOrder::getUserOrders((int) $user['sub'], $page, $limit);
        
        return $this->success($orders);
    }
    
    /**
     * 生成随机字符串
     */
    protected function generateNonceStr(int $length = 32): string
    {
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
    
    /**
     * 生成签名
     */
    protected function generateSign(array $params, string $key): string
    {
        // 过滤空值和sign
        $params = array_filter($params, function($v) {
            return $v !== '' && $v !== null;
        });
        
        // 按键名ASCII码升序排序
        ksort($params);
        
        // 拼接字符串
        $string = '';
        foreach ($params as $k => $v) {
            $string .= "{$k}={$v}&";
        }
        $string .= "key={$key}";
        
        // MD5加密并转大写
        return strtoupper(md5($string));
    }
    
    /**
     * 数组转XML
     */
    protected function arrayToXml(array $data): string
    {
        $xml = '<xml>';
        foreach ($data as $key => $val) {
            $xml .= is_numeric($val) ? "<{$key}>{$val}</{$key}>" : "<{$key}><![CDATA[{$val}]]></{$key}>";
        }
        $xml .= '</xml>';
        return $xml;
    }
    
    /**
     * XML转数组（安全版本，防止XXE攻击）
     */
    protected function xmlToArray(string $xml): array
    {
        // 限制XML大小，防止DoS攻击（最大1MB）
        if (strlen($xml) > 1024 * 1024) {
            throw new \Exception('XML data too large');
        }
        
        // 检查XML是否包含外部实体引用（简单检测）
        $lowerXml = strtolower($xml);
        $dangerousPatterns = [
            '<!entity',
            '<!doctype',
            'system(',
            'public(',
            'file://',
            'http://',
            'https://',
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            if (strpos($lowerXml, $pattern) !== false) {
                throw new \Exception('XML contains potentially dangerous content');
            }
        }
        
        // PHP 8.0+ 使用 LIBXML_NONET 代替废弃的 libxml_disable_entity_loader
        // LIBXML_NOCDATA - 将CDATA合并为文本节点
        // LIBXML_NONET - 禁止网络访问
        // LIBXML_DTDLOAD - 禁止加载外部DTD
        // LIBXML_DTDATTR - 默认DTD属性
        $prevValue = libxml_use_internal_errors(true);
        
        $data = simplexml_load_string(
            $xml,
            'SimpleXMLElement',
            LIBXML_NOCDATA | LIBXML_NONET | LIBXML_NOENT | LIBXML_DTDLOAD
        );
        
        libxml_use_internal_errors($prevValue);
        
        if ($data === false) {
            throw new \Exception('Failed to parse XML');
        }
        
        return json_decode(json_encode($data), true);
    }
    
    /**
     * HTTP POST请求
     */
    protected function httpPost(string $url, string $data, array $cert = []): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        // 如果需要证书
        if (!empty($cert['cert'])) {
            curl_setopt($ch, CURLOPT_SSLCERT, $cert['cert']);
        }
        if (!empty($cert['key'])) {
            curl_setopt($ch, CURLOPT_SSLKEY, $cert['key']);
        }
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \Exception('CURL Error: ' . $error);
        }
        
        return $response;
    }
    
    /**
     * XML响应
     */
    protected function xmlResponse(string $code, string $msg = ''): string
    {
        $xml = "<xml>\n";
        $xml .= "<return_code><![CDATA[{$code}]]></return_code>\n";
        if ($msg) {
            $xml .= "<return_msg><![CDATA[{$msg}]]></return_msg>\n";
        }
        $xml .= "</xml>";
        
        return response($xml, 200, ['Content-Type' => 'text/xml']);
    }
}
