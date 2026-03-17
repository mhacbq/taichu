<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\service\SmsService;
use think\facade\Cache;

/**
 * 短信控制器
 */
class Sms extends BaseController
{
    /**
     * 发送验证码
     */
    public function sendCode()
    {
        $data = $this->request->post();
        
        // 验证参数
        if (empty($data['phone'])) {
            return $this->error('请输入手机号');
        }
        
        $phone = $data['phone'];
        $type = $data['type'] ?? 'register';
        
        // 验证手机号格式
        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
            return $this->error('手机号格式不正确');
        }
        
        // 验证类型
        $validTypes = ['register', 'login', 'reset'];
        if (!in_array($type, $validTypes)) {
            return $this->error('无效的验证码类型');
        }
        
        $ip = $this->request->ip();
        
        // 防刷限制 - IP级别（每小时20次）
        $ipKey = 'sms_ip_limit:' . md5($ip);
        $ipCount = Cache::get($ipKey, 0);
        if ($ipCount >= 20) {
            return $this->error('发送过于频繁，请稍后再试', 429);
        }
        
        // 防刷限制 - 手机号级别（每小时5次）
        $phoneKey = 'sms_phone_limit:' . md5($phone);
        $phoneCount = Cache::get($phoneKey, 0);
        if ($phoneCount >= 5) {
            return $this->error('该手机号发送过于频繁，请1小时后再试', 429);
        }
        
        // 防刷限制 - 图形验证码验证（连续3次后需要）
        $captchaKey = 'sms_captcha_required:' . md5($phone);
        $failCount = Cache::get('sms_send_fail:' . md5($phone), 0);
        if ($failCount >= 3) {
            if (empty($data['captcha']) || empty($data['captcha_key'])) {
                return $this->error('请完成图形验证码', 403, ['need_captcha' => true]);
            }
            // 验证图形验证码
            $captchaValid = $this->verifyCaptcha($data['captcha_key'], $data['captcha']);
            if (!$captchaValid) {
                return $this->error('图形验证码错误');
            }
        }
        
        // 发送验证码
        $result = SmsService::sendVerifyCode($phone, $type, $ip);
        
        if ($result['success']) {
            // 增加限制计数
            Cache::set($ipKey, $ipCount + 1, 3600);
            Cache::set($phoneKey, $phoneCount + 1, 3600);
            
            return $this->success([
                'expire' => $result['expire'] ?? 300,
                'wait' => $result['wait'] ?? 60,
                'test_mode' => (bool) ($result['test_mode'] ?? false),
                'test_code' => $result['test_code'] ?? null,
            ], '验证码已发送');

        }
        
        // 记录失败次数
        Cache::set('sms_send_fail:' . md5($phone), $failCount + 1, 3600);
        
        return $this->error($result['message']);
    }
    
    /**
     * 验证图形验证码
     */
    protected function verifyCaptcha(string $key, string $code): bool
    {
        // 调用验证码服务进行验证
        return \app\service\CaptchaService::verify($key, $code);
    }
    
    /**
     * 验证验证码（用于前端检查）
     */
    public function verifyCode()
    {
        $data = $this->request->post();
        
        if (empty($data['phone']) || empty($data['code'])) {
            return $this->error('请输入手机号和验证码');
        }
        
        $type = $data['type'] ?? 'register';
        $valid = SmsService::verifyCode($data['phone'], $data['code'], $type);
        
        if ($valid) {
            return $this->success(['valid' => true], '验证通过');
        }
        
        return $this->error('验证码错误或已过期');
    }
}
