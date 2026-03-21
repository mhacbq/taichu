<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\PaymentConfig;
use app\model\RechargeOrder;
use think\facade\Log;


/**
 * 支付宝支付控制器
 */
class Alipay extends BaseController
{
    protected $middleware = [\app\middleware\Auth::class];

    protected const RECHARGE_OPTIONS = [
        10 => 100,
        30 => 330,
        50 => 580,
        100 => 1200,
        200 => 2500,
        500 => 6500,
    ];
    
    /**
     * 创建支付宝订单（PC网站支付）
     */
    public function createOrder()
    {
        return $this->createRechargeOrderResponse('pc');
    }
    
    /**
     * 创建支付宝手机网站支付
     */
    public function createMobileOrder()
    {
        return $this->createRechargeOrderResponse('mobile');
    }

    protected function createRechargeOrderResponse(string $scene): \think\response\Json
    {
        $data = $this->request->post();
        $user = $this->request->user ?? [];
        $userId = (int) ($user['sub'] ?? 0);

        if (empty($data['amount'])) {
            return $this->error('请选择充值金额');
        }

        $amount = (float) $data['amount'];
        $points = $this->resolveRechargePoints($amount);
        if ($points === null) {
            return $this->error('充值金额不正确');
        }

        $config = PaymentConfig::getAlipayConfig();
        if (!$config) {
            Log::error('支付宝支付配置不存在', ['user_id' => $userId]);
            return $this->error('支付通道暂时不可用', 503);
        }

        // 检查支付宝支付通道健康状态
        if (!$this->checkAlipayPaymentHealth()) {
            Log::warning('支付宝支付通道异常', ['user_id' => $userId]);
            return $this->error('支付通道响应超时，请稍后重试', 504);
        }

        $order = RechargeOrder::createOrder(
            $userId,
            $amount,
            $points,
            $this->request->ip(),
            (string) $this->request->header('User-Agent'),
            'alipay'
        );

        if (empty($order)) {
            return $this->error('订单创建失败', 500);
        }

        try {
            if ($scene === 'mobile') {
                $payParams = $this->createAlipayMobileParams($order, $config);

                return $this->success([
                    'order_no' => $order['order_no'],
                    'amount' => $order['amount'],
                    'points' => $order['points'],
                    'pay_url' => $payParams['pay_url'],
                    'expire_time' => $order['expire_time'],
                ]);
            }

            $payForm = $this->createAlipayForm($order, $config);

            return $this->success([
                'order_no' => $order['order_no'],
                'amount' => $order['amount'],
                'points' => $order['points'],
                'pay_form' => $payForm,
                'expire_time' => $order['expire_time'],
            ]);
        } catch (\Throwable $e) {
            $this->cancelRechargeOrder((string) ($order['order_no'] ?? ''));

            // 使用细化后的异常处理
            return $this->handleAlipayPaymentException($e, $scene, [
                'user_id' => $userId,
                'order_no' => $order['order_no'] ?? '',
                'amount' => $amount,
                'points' => $points,
            ]);
        }
    }

    /**
     * 处理支付宝支付异常
     */
    protected function handleAlipayPaymentException(
        \Throwable $e,
        string $scene,
        array $context = []
    ): \think\response\Json {
        $message = $e->getMessage();

        // 网络异常或超时
        $networkErrors = [
            'CURL Error',
            'Connection timed out',
            'timeout',
            'network',
            '连接超时',
            '网络错误',
            'SSL',
        ];

        foreach ($networkErrors as $pattern) {
            if (stripos($message, $pattern) !== false) {
                Log::warning('支付宝支付网络异常', array_merge($context, ['error' => $message]));
                return $this->error('网络连接异常，请检查网络后重试', 504);
            }
        }

        // 配置错误
        $configErrors = [
            '配置不存在',
            'APPID',
            '密钥',
            '私钥',
            '公钥',
        ];

        foreach ($configErrors as $pattern) {
            if (stripos($message, $pattern) !== false) {
                Log::error('支付宝支付配置错误', array_merge($context, ['error' => $message]));
                return $this->error('支付通道配置错误', 500);
            }
        }

        // 签名相关错误
        $signErrors = [
            '签名',
            'sign',
            '签名验证',
        ];

        foreach ($signErrors as $pattern) {
            if (stripos($message, $pattern) !== false) {
                Log::error('支付宝签名错误', array_merge($context, ['error' => $message]));
                return $this->error('签名验证失败，请重新尝试', 400);
            }
        }

        // 默认返回通用错误
        Log::error('支付宝支付请求失败', array_merge($context, ['error' => $message]));
        return $this->error('支付请求失败，请稍后重试', 500);
    }

    /**
     * 检查支付宝支付通道健康状态
     */
    protected function checkAlipayPaymentHealth(): bool
    {
        try {
            // 简单的连接测试：访问支付宝网关
            $ch = curl_init('https://openapi.alipay.com/gateway.do');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

            curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            return $httpCode > 0;
        } catch (\Exception $e) {
            Log::error('支付宝支付通道检查失败', ['error' => $e->getMessage()]);
            return false;
        }
    }

    protected function logNotifyEvent(string $level, string $message, array $context = []): void
    {
        $payload = $this->sanitizeLogContext($context);

        switch (strtolower($level)) {
            case 'warning':
                Log::warning($message, $payload);
                break;
            case 'info':
                Log::info($message, $payload);
                break;
            default:
                Log::error($message, $payload);
                break;
        }
    }

    protected function buildNotifyLogContext(array $data, array $extra = []): array
    {
        return array_merge([
            'channel' => 'alipay',
            'order_no' => $this->maskTradeNo((string) ($data['out_trade_no'] ?? '')),
            'trade_no' => $this->maskTradeNo((string) ($data['trade_no'] ?? '')),
            'trade_status' => (string) ($data['trade_status'] ?? ''),
            'notify_key_count' => count($data),
        ], $extra);
    }

    protected function maskTradeNo(string $value): string
    {
        $normalized = trim($value);
        if ($normalized === '') {
            return '';
        }

        $length = strlen($normalized);
        if ($length <= 8) {
            return str_repeat('*', max($length - 2, 1)) . substr($normalized, -2);
        }

        return substr($normalized, 0, 4) . str_repeat('*', min($length - 8, 8)) . substr($normalized, -4);
    }

    protected function resolveRechargePoints(float $amount): ?int

    {
        $normalizedAmount = (int) $amount;
        if ((float) $normalizedAmount !== $amount) {
            return null;
        }

        return self::RECHARGE_OPTIONS[$normalizedAmount] ?? null;
    }


    protected function cancelRechargeOrder(string $orderNo): void
    {
        if ($orderNo === '') {
            return;
        }

        $orderModel = RechargeOrder::findByOrderNo($orderNo);
        if ($orderModel) {
            $orderModel->cancel();
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
            $this->logNotifyEvent('warning', '支付宝回调签名验证失败', $this->buildNotifyLogContext($data, [
                'notify_source' => 'async',
            ]));
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
            $this->logNotifyEvent('error', '支付宝回调订单不存在', $this->buildNotifyLogContext($data, [
                'notify_source' => 'async',
            ]));
            return response('fail', 200);
        }

        
        // 如果订单已处理，直接返回成功
        if ($order->status === RechargeOrder::STATUS_PAID) {
            return response('success', 200);
        }
        
        // 验证订单金额
        $totalAmount = (float) $data['total_amount'];
        if (abs($totalAmount - $order->amount) > 0.01) {
            $this->logNotifyEvent('error', '支付宝回调金额不匹配', $this->buildNotifyLogContext($data, [
                'notify_source' => 'async',
                'paid_amount' => $totalAmount,
                'expected_amount' => (float) $order->amount,
            ]));
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