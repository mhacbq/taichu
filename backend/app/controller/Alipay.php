<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\PaymentConfig;
use app\model\RechargeOrder;

/**
 * 支付宝支付控制器
 */
class Alipay extends BaseController
{
    protected $middleware = [\app\middleware\Auth::class];
    
    /**
     * 创建支付宝订单（PC网站支付）
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
        
        // 获取充值配置
        $rechargeOptions = [
            10 => 100,
            30 => 330,
            50 => 580,
            100 => 1200,
            200 => 2500,
            500 => 6500,
        ];
        
        // 验证金额是否在允许列表中
        if (!isset($rechargeOptions[$amount])) {
            return $this->error('充值金额不正确');
        }
        
        $points = $rechargeOptions[$amount];
        
        // 获取支付宝配置
        $config = PaymentConfig::getAlipayConfig();
        if (!$config) {
            return $this->error('支付宝支付未配置', 503);
        }
        
        // 创建订单
        $order = RechargeOrder::createOrder(
            (int) $user['sub'],
            $amount,
            $points,
            $this->request->ip(),
            $this->request->header('User-Agent'),
            'alipay'
        );
        
        if (empty($order)) {
            return $this->error('订单创建失败');
        }
        
        // 生成支付宝订单
        try {
            $payForm = $this->createAlipayForm($order, $config);
            
            return $this->success([
                'order_no' => $order['order_no'],
                'amount' => $order['amount'],
                'points' => $order['points'],
                'pay_form' => $payForm,
                'expire_time' => $order['expire_time'],
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
     * 创建支付宝手机网站支付
     */
    public function createMobileOrder()
    {
        $data = $this->request->post();
        $user = $this->request->user;
        
        // 参数验证
        if (empty($data['amount'])) {
            return $this->error('请选择充值金额');
        }
        
        $amount = (float) $data['amount'];
        
        // 获取充值配置
        $rechargeOptions = [
            10 => 100,
            30 => 330,
            50 => 580,
            100 => 1200,
            200 => 2500,
            500 => 6500,
        ];
        
        if (!isset($rechargeOptions[$amount])) {
            return $this->error('充值金额不正确');
        }
        
        $points = $rechargeOptions[$amount];
        
        // 获取支付宝配置
        $config = PaymentConfig::getAlipayConfig();
        if (!$config) {
            return $this->error('支付宝支付未配置', 503);
        }
        
        // 创建订单
        $order = RechargeOrder::createOrder(
            (int) $user['sub'],
            $amount,
            $points,
            $this->request->ip(),
            $this->request->header('User-Agent'),
            'alipay'
        );
        
        if (empty($order)) {
            return $this->error('订单创建失败');
        }
        
        // 生成支付宝手机网站支付参数
        try {
            $payParams = $this->createAlipayMobileParams($order, $config);
            
            return $this->success([
                'order_no' => $order['order_no'],
                'amount' => $order['amount'],
                'points' => $order['points'],
                'pay_url' => $payParams['pay_url'],
                'expire_time' => $order['expire_time'],
            ]);
        } catch (\Exception $e) {
            $orderModel = RechargeOrder::findByOrderNo($order['order_no']);
            if ($orderModel) {
                $orderModel->cancel();
            }
            
            return $this->error('创建支付订单失败：' . $e->getMessage());
        }
    }
    
    /**
     * 生成支付宝PC网站支付表单
     */
    protected function createAlipayForm(array $order, array $config): string
    {
        // 支付宝网关
        $gateway = 'https://openapi.alipay.com/gateway.do';
        
        // 构建请求参数
        $params = [
            'app_id' => $config['app_id'],
            'method' => 'alipay.trade.page.pay',
            'format' => 'JSON',
            'return_url' => $config['return_url'] ?? '',
            'notify_url' => $config['notify_url'] ?? '',
            'charset' => 'utf-8',
            'sign_type' => 'RSA2',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'biz_content' => json_encode([
                'out_trade_no' => $order['order_no'],
                'product_code' => 'FAST_INSTANT_TRADE_PAY',
                'total_amount' => $order['amount'],
                'subject' => '积分充值-' . $order['points'] . '积分',
                'body' => '太初命理积分充值',
                'timeout_express' => '30m',
            ]),
        ];
        
        // 生成签名
        $params['sign'] = $this->generateAlipaySign($params, $config['private_key']);
        
        // 构建表单
        $form = '<form id="alipayForm" action="' . $gateway . '" method="POST">';
        foreach ($params as $key => $value) {
            $form .= '<input type="hidden" name="' . $key . '" value="' . htmlspecialchars($value) . '">';
        }
        $form .= '</form>';
        $form .= '<script>document.getElementById("alipayForm").submit();<\/script>';
        
        return $form;
    }
    
    /**
     * 生成支付宝手机网站支付参数
     */
    protected function createAlipayMobileParams(array $order, array $config): array
    {
        // 支付宝网关
        $gateway = 'https://openapi.alipay.com/gateway.do';
        
        // 构建请求参数
        $params = [
            'app_id' => $config['app_id'],
            'method' => 'alipay.trade.wap.pay',
            'format' => 'JSON',
            'return_url' => $config['return_url'] ?? '',
            'notify_url' => $config['notify_url'] ?? '',
            'charset' => 'utf-8',
            'sign_type' => 'RSA2',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'biz_content' => json_encode([
                'out_trade_no' => $order['order_no'],
                'product_code' => 'QUICK_WAP_WAY',
                'total_amount' => $order['amount'],
                'subject' => '积分充值-' . $order['points'] . '积分',
                'body' => '太初命理积分充值',
                'timeout_express' => '30m',
            ]),
        ];
        
        // 生成签名
        $params['sign'] = $this->generateAlipaySign($params, $config['private_key']);
        
        // 构建支付URL
        $queryString = http_build_query($params);
        $payUrl = $gateway . '?' . $queryString;
        
        return [
            'pay_url' => $payUrl,
            'params' => $params,
        ];
    }
    
    /**
     * 生成支付宝签名
     */
    protected function generateAlipaySign(array $params, string $privateKey): string
    {
        // 过滤空值和sign
        $params = array_filter($params, function($v) {
            return $v !== '' && $v !== null;
        });
        
        // 移除sign参数
        unset($params['sign']);
        
        // 按键名ASCII码升序排序
        ksort($params);
        
        // 拼接字符串
        $string = '';
        foreach ($params as $k => $v) {
            $string .= $k . '=' . $v . '&';
        }
        $string = rtrim($string, '&');
        
        // RSA2签名
        $privateKey = "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($privateKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
        
        $res = openssl_get_privatekey($privateKey);
        openssl_sign($string, $sign, $res, OPENSSL_ALGO_SHA256);
        openssl_free_key($res);
        
        return base64_encode($sign);
    }
    
    /**
     * 支付宝异步通知处理
     * 注意：此接口不需要认证中间件
     */
    public function notify()
    {
        // 获取所有通知参数
        $data = $this->request->post();
        
        if (empty($data)) {
            return response('fail', 200);
        }
        
        // 获取支付宝配置验证签名
        $config = PaymentConfig::getAlipayConfig();
        if (!$config) {
            return response('fail', 200);
        }
        
        // 获取签名
        $sign = $data['sign'] ?? '';
        unset($data['sign'], $data['sign_type']);
        
        // 验证签名
        if (!$this->verifyAlipaySign($data, $sign, $config['alipay_public_key'])) {
            trace('支付宝签名验证失败', 'error');
            return response('fail', 200);
        }
        
        // 验证交易状态
        if ($data['trade_status'] !== 'TRADE_SUCCESS' && $data['trade_status'] !== 'TRADE_FINISHED') {
            return response('success', 200); // 非成功状态也返回success，避免重复通知
        }
        
        // 查找订单
        $orderNo = $data['out_trade_no'] ?? '';
        $order = RechargeOrder::findByOrderNo($orderNo);
        
        if (!$order) {
            trace("支付宝回调：订单不存在 - {$orderNo}", 'error');
            return response('fail', 200);
        }
        
        // 如果订单已处理，直接返回成功
        if ($order->status === RechargeOrder::STATUS_PAID) {
            return response('success', 200);
        }
        
        // 验证订单金额
        $totalAmount = (float) $data['total_amount'];
        if (abs($totalAmount - $order->amount) > 0.01) {
            trace("订单金额不匹配：订单{$orderNo}，支付宝支付{$totalAmount}元，系统记录{$order->amount}元", 'error');
            return response('fail', 200);
        }
        
        // 标记订单为已支付
        $tradeNo = $data['trade_no'] ?? '';
        if ($order->markAsPaid($tradeNo, $data)) {
            return response('success', 200);
        }
        
        return response('fail', 200);
    }
    
    /**
     * 验证支付宝签名
     */
    protected function verifyAlipaySign(array $params, string $sign, string $publicKey): bool
    {
        // 过滤空值
        $params = array_filter($params, function($v) {
            return $v !== '' && $v !== null;
        });
        
        // 按键名ASCII码升序排序
        ksort($params);
        
        // 拼接字符串
        $string = '';
        foreach ($params as $k => $v) {
            $string .= $k . '=' . $v . '&';
        }
        $string = rtrim($string, '&');
        
        // 格式化公钥
        $publicKey = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($publicKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";
        
        $res = openssl_get_publickey($publicKey);
        $result = openssl_verify($string, base64_decode($sign), $res, OPENSSL_ALGO_SHA256);
        openssl_free_key($res);
        
        return $result === 1;
    }
    
    /**
     * 支付宝同步回调
     * 注意：此接口不需要认证中间件
     */
    public function return()
    {
        $data = $this->request->get();
        
        // 验证签名
        $config = PaymentConfig::getAlipayConfig();
        if (!$config) {
            return redirect('/recharge?status=error&message=配置错误');
        }
        
        $sign = $data['sign'] ?? '';
        unset($data['sign'], $data['sign_type']);
        
        if (!$this->verifyAlipaySign($data, $sign, $config['alipay_public_key'])) {
            return redirect('/recharge?status=error&message=签名验证失败');
        }
        
        // 检查交易状态
        if ($data['trade_status'] === 'TRADE_SUCCESS' || $data['trade_status'] === 'TRADE_FINISHED') {
            return redirect('/recharge?status=success');
        } else {
            return redirect('/recharge?status=error&message=支付未完成');
        }
    }
    
    /**
     * 查询支付宝订单状态
     */
    protected function queryAlipayOrder(RechargeOrder $order, array $config): array
    {
        $params = [
            'app_id' => $config['app_id'],
            'method' => 'alipay.trade.query',
            'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => 'RSA2',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
            'biz_content' => json_encode([
                'out_trade_no' => $order->order_no,
            ]),
        ];
        
        $params['sign'] = $this->generateAlipaySign($params, $config['private_key']);
        
        // 发送查询请求
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://openapi.alipay.com/gateway.do');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        if ($response) {
            $result = json_decode($response, true);
            if (isset($result['alipay_trade_query_response'])) {
                return $result['alipay_trade_query_response'];
            }
        }
        
        return [];
    }
}
