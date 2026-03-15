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
        
        // 防刷限制 - 使用缓存限制同一IP
        $ip = $this->request->ip();
        $ipKey = 'sms_ip_limit:' . $ip;
        $ipCount = Cache::get($ipKey, 0);
        if ($ipCount >= 20) {
            return $this->error('发送过于频繁，请稍后再试', 429);
        }
        
        // 发送验证码
        $result = SmsService::sendVerifyCode($phone, $type, $ip);
        
        if ($result['success']) {
            // 增加IP限制计数
            Cache::set($ipKey, $ipCount + 1, 3600);
            
            return $this->success([
                'expire' => $result['expire'] ?? 300,
                'wait' => $result['wait'] ?? 0,
            ], '验证码已发送');
        }
        
        return $this->error($result['message']);
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
