<?php
declare(strict_types=1);

namespace app\service;

use app\model\PaymentConfig;
use think\facade\Log;

/**
 * 微信支付服务类
 * 处理支付、查询、退款等核心逻辑
 */
class WechatPayService
{
    /**
     * 发起退款请求
     * 
     * @param string $orderNo 商户订单号
     * @param string $refundNo 商户退款单号
     * @param float $totalAmount 订单总金额（元）
     * @param float $refundAmount 退款金额（元）
     * @param string $reason 退款原因
     * @return array 退款结果
     */
    public static function refund(string $orderNo, string $refundNo, float $totalAmount, float $refundAmount, string $reason = ''): array
    {
        $config = PaymentConfig::getWechatConfig();
        if (!$config) {
            throw new \Exception('微信支付配置缺失或未启用');
        }

        $params = [
            'appid' => $config['app_id'],
            'mch_id' => $config['mch_id'],
            'nonce_str' => self::generateNonceStr(),
            'out_trade_no' => $orderNo,
            'out_refund_no' => $refundNo,
            'total_fee' => (int)($totalAmount * 100),
            'refund_fee' => (int)($refundAmount * 100),
            'refund_desc' => $reason,
        ];

        $params['sign'] = self::generateSign($params, $config['api_key']);
        $xml = self::arrayToXml($params);

        // 退款接口需要证书
        $cert = [];
        $tempFiles = [];
        if (!empty($config['api_cert']) && !empty($config['api_key_pem'])) {
            // 检查配置是路径还是内容
            $certContent = $config['api_cert'];
            $keyContent = $config['api_key_pem'];

            if (strpos($certContent, '-----BEGIN CERTIFICATE-----') !== false) {
                // 内容模式：写入临时文件
                $certPath = runtime_path('temp') . 'wechat_cert_' . md5($certContent) . '.pem';
                if (!file_exists($certPath)) {
                    file_put_contents($certPath, $certContent);
                    $tempFiles[] = $certPath;
                }
                $cert['cert'] = $certPath;
            } else {
                $cert['cert'] = $certContent;
            }

            if (strpos($keyContent, '-----BEGIN PRIVATE KEY-----') !== false || strpos($keyContent, '-----BEGIN RSA PRIVATE KEY-----') !== false) {
                // 内容模式：写入临时文件
                $keyPath = runtime_path('temp') . 'wechat_key_' . md5($keyContent) . '.pem';
                if (!file_exists($keyPath)) {
                    file_put_contents($keyPath, $keyContent);
                    $tempFiles[] = $keyPath;
                }
                $cert['key'] = $keyPath;
            } else {
                $cert['key'] = $keyContent;
            }
        }

        try {
            // 注意：退款接口必须使用双向证书
            $response = self::httpPost('https://api.mch.weixin.qq.com/secapi/pay/refund', $xml, $cert);
            $result = self::xmlToArray($response);

            if (($result['return_code'] ?? '') === 'SUCCESS' && ($result['result_code'] ?? '') === 'SUCCESS') {
                return [
                    'success' => true,
                    'refund_id' => $result['refund_id'] ?? '',
                    'amount' => (isset($result['refund_fee']) ? $result['refund_fee'] / 100 : $refundAmount),
                ];
            }

            return [
                'success' => false,
                'message' => $result['err_code_des'] ?? ($result['return_msg'] ?? '退款失败'),
            ];
        } catch (\Throwable $e) {
            Log::error('微信退款调用异常', [
                'order_no' => self::maskTradeNo($orderNo),
                'refund_no' => self::maskTradeNo($refundNo),
                'total_amount' => round($totalAmount, 2),
                'refund_amount' => round($refundAmount, 2),
                'has_reason' => $reason !== '',
                'cert_mode' => !empty($cert['cert']) && !empty($cert['key']) ? 'mutual_tls' : 'missing_cert',
                'error' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
            return [
                'success' => false,
                'message' => '微信退款处理失败，请稍后重试',
            ];
        } finally {
            self::cleanupTempFiles($tempFiles);
        }
    }

    /**
     * 生成随机字符串
     */
    public static function generateNonceStr(int $length = 32): string
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
    public static function generateSign(array $params, string $key): string
    {
        $params = array_filter($params, function($v) {
            return $v !== '' && $v !== null;
        });
        ksort($params);
        $string = '';
        foreach ($params as $k => $v) {
            $string .= "{$k}={$v}&";
        }
        $string .= "key={$key}";
        return strtoupper(md5($string));
    }

    /**
     * 数组转XML
     */
    public static function arrayToXml(array $data): string
    {
        $xml = '<xml>';
        foreach ($data as $key => $val) {
            $xml .= is_numeric($val) ? "<{$key}>{$val}</{$key}>" : "<{$key}><![CDATA[{$val}]]></{$key}>";
        }
        $xml .= '</xml>';
        return $xml;
    }

    /**
     * XML转数组
     */
    public static function xmlToArray(string $xml): array
    {
        libxml_use_internal_errors(true);
        $data = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($data === false) {
            return [];
        }
        return json_decode(json_encode($data), true);
    }

    /**
     * HTTP POST请求
     */
    private static function httpPost(string $url, string $data, array $cert = []): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        if (!empty($cert['cert']) && file_exists($cert['cert'])) {
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLCERT, $cert['cert']);
        }
        if (!empty($cert['key']) && file_exists($cert['key'])) {
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
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

    private static function cleanupTempFiles(array $tempFiles): void
    {
        foreach ($tempFiles as $tempFile) {
            if (!is_string($tempFile) || $tempFile === '' || !file_exists($tempFile)) {
                continue;
            }

            @unlink($tempFile);
        }
    }

    private static function maskTradeNo(string $value): string
    {
        $length = strlen($value);
        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return substr($value, 0, 4) . str_repeat('*', max(0, $length - 8)) . substr($value, -4);
    }
}
