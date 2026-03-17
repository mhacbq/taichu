<?php
declare(strict_types=1);

namespace app\service;

use app\model\SmsConfig;
use app\model\SmsCode;
use app\model\InviteRecord;
use think\facade\Cache;
use think\facade\Log;

/**
 * 短信服务类
 */
class SmsService
{
    /**
     * 发送验证码短信
     */
    public static function sendVerifyCode(string $phone, string $type = 'register', string $ip = ''): array
    {
        // 检查发送频率限制
        $lastSendTime = SmsCode::getLastSendTime($phone, $type);
        if ($lastSendTime) {
            $lastTime = strtotime($lastSendTime);
            if (time() - $lastTime < 60) {
                return ['success' => false, 'message' => '发送过于频繁，请稍后再试', 'wait' => 60 - (time() - $lastTime)];
            }
        }
        
        // 检查每日发送限制
        $todayCount = SmsCode::getTodaySendCount($phone, $ip);
        if ($todayCount >= 10) {
            return ['success' => false, 'message' => '今日发送次数已达上限，请明天再试'];
        }

        $isLocalTestMode = env('APP_DEBUG', false) || env('SMS_TEST_MODE', false);
        $testCode = (string) env('SMS_TEST_CODE', '123456');
        if ($isLocalTestMode) {
            SmsCode::createCode($phone, $type, $ip, 5);
            Log::info('短信测试模式验证码已生成', [
                'phone' => self::maskPhone($phone),
                'type' => $type,
                'mode' => 'local_test',
            ]);

            return [
                'success' => true,
                'message' => '测试模式验证码已生成',
                'expire' => 300,
                'test_mode' => true,
                'test_code' => $testCode,
            ];
        }
        
        // 获取短信配置
        $config = SmsConfig::getTencentConfig();
        if (!$config) {
            return ['success' => false, 'message' => '短信服务未配置'];
        }
        
        // 生成验证码
        $code = SmsCode::createCode($phone, $type, $ip, 5);

        
        // 发送短信
        $result = self::sendTencentSms($phone, $code, $config, $type);
        
        if ($result['success']) {
            return ['success' => true, 'message' => '发送成功', 'expire' => 300];
        }
        
        return $result;
    }
    
    /**
     * 发送腾讯云短信
     */
    protected static function sendTencentSms(string $phone, string $code, array $config, string $type): array
    {
        try {
            // 准备请求参数
            $params = [
                'PhoneNumberSet' => ['+86' . $phone],
                'TemplateID' => $config['template_code'],
                'SignName' => $config['sign_name'],
                'TemplateParamSet' => [$code, '5'],
                'SmsSdkAppId' => $config['sdk_app_id'],
            ];
            
            // 使用腾讯云API 3.0
            $result = self::callTencentApi($params, $config);
            
            if ($result['Response']['SendStatusSet'][0]['Code'] === 'Ok') {
                return ['success' => true, 'message' => '发送成功'];
            }
            
            return ['success' => false, 'message' => $result['Response']['SendStatusSet'][0]['Message'] ?? '发送失败'];
            
        } catch (\Exception $e) {
            trace('腾讯云短信发送失败: ' . $e->getMessage(), 'error');
            return ['success' => false, 'message' => '短信服务异常'];
        }
    }
    
    /**
     * 调用腾讯云API
     */
    protected static function callTencentApi(array $params, array $config): array
    {
        $host = 'sms.tencentcloudapi.com';
        $service = 'sms';
        $version = '2021-01-11';
        $action = 'SendSms';
        $region = 'ap-guangzhou';
        
        $timestamp = time();
        $date = gmdate('Y-m-d', $timestamp);
        
        // 构建规范请求
        $httpRequestMethod = 'POST';
        $canonicalUri = '/';
        $canonicalQueryString = '';
        $contentType = 'application/json; charset=utf-8';
        
        $payload = json_encode($params);
        $payloadHash = hash('sha256', $payload);
        
        $canonicalHeaders = "content-type:{$contentType}\nhost:{$host}\nx-tc-action:" . strtolower($action) . "\n";
        $signedHeaders = 'content-type;host;x-tc-action';
        
        $canonicalRequest = $httpRequestMethod . "\n" 
            . $canonicalUri . "\n" 
            . $canonicalQueryString . "\n" 
            . $canonicalHeaders . "\n" 
            . $signedHeaders . "\n" 
            . $payloadHash;
        
        // 构建待签名字符串
        $algorithm = 'TC3-HMAC-SHA256';
        $credentialScope = $date . '/' . $service . '/tc3_request';
        $hashedCanonicalRequest = hash('sha256', $canonicalRequest);
        
        $stringToSign = $algorithm . "\n" 
            . $timestamp . "\n" 
            . $credentialScope . "\n" 
            . $hashedCanonicalRequest;
        
        // 计算签名
        $secretDate = hash_hmac('sha256', $date, 'TC3' . $config['secret_key'], true);
        $secretService = hash_hmac('sha256', $service, $secretDate, true);
        $secretSigning = hash_hmac('sha256', 'tc3_request', $secretService, true);
        $signature = hash_hmac('sha256', $stringToSign, $secretSigning);
        
        // 构建Authorization
        $authorization = $algorithm . ' ' 
            . 'Credential=' . $config['secret_id'] . '/' . $credentialScope . ', ' 
            . 'SignedHeaders=' . $signedHeaders . ', ' 
            . 'Signature=' . $signature;
        
        // 发送请求
        $headers = [
            'Host: ' . $host,
            'Content-Type: ' . $contentType,
            'X-TC-Action: ' . $action,
            'X-TC-Version: ' . $version,
            'X-TC-Timestamp: ' . $timestamp,
            'X-TC-Region: ' . $region,
            'Authorization: ' . $authorization,
        ];
        
        $ch = curl_init('https://' . $host);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        // 生产环境必须启用SSL验证，防止中间人攻击
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \Exception('CURL Error: ' . $error);
        }
        
        return json_decode($response, true);
    }
    
    /**
     * 验证短信验证码
     * 增加失败次数限制，防止暴力破解
     * 
     * 本地测试模式：使用验证码 "123456" 可以直接通过验证（仅用于开发环境）
     */
    public static function verifyCode(string $phone, string $code, string $type): bool
    {
        // 本地测试模式：特定测试验证码直接通过
        $isLocalTestMode = env('APP_DEBUG', false) || env('SMS_TEST_MODE', false);
        $testCode = env('SMS_TEST_CODE', '123456');
        
        if ($isLocalTestMode && $code === $testCode) {
            // 测试模式下使用固定验证码，直接返回成功
            Log::info('短信测试模式验证码校验通过', [
                'phone' => self::maskPhone($phone),
                'type' => $type,
                'mode' => 'local_test',
            ]);
            InviteRecord::clearVerifyFail($phone, $type);
            return true;
        }

        
        // 1. 检查失败次数限制
        if (!InviteRecord::checkVerifyFailLimit($phone, $type)) {
            throw new \Exception('验证失败次数过多，请重新获取验证码');
        }
        
        // 2. 执行验证码验证
        $result = SmsCode::verifyCode($phone, $code, $type);
        
        // 3. 记录失败或清除记录
        if (!$result) {
            InviteRecord::recordVerifyFail($phone, $type);
        } else {
            InviteRecord::clearVerifyFail($phone, $type);
        }
        
        return $result;
    }
    
    /**
     * 获取验证码验证剩余次数
     */
    public static function getVerifyRemainingAttempts(string $phone, string $type): int
    {
        $key = "sms_verify_fail:{$phone}:{$type}";
        $failCount = Cache::get($key, 0);
        return max(0, 5 - $failCount);
    }

    /**
     * 脱敏手机号
     */
    private static function maskPhone(string $phone): string
    {
        if (strlen($phone) < 7) {
            return str_repeat('*', strlen($phone));
        }

        return substr($phone, 0, 3) . '****' . substr($phone, -4);
    }
}
