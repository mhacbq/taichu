<?php
declare(strict_types=1);

namespace app\middleware;

use think\facade\Cache;

class RateLimit
{
    /**
     * 限流配置
     */
    protected $config = [
        // 默认限制：每分钟60次
        'default' => [
            'max_requests' => 60,
            'window' => 60,
        ],
        // 登录接口：每分钟5次
        'auth/login' => [
            'max_requests' => 5,
            'window' => 60,
        ],
        // 手机号登录：每分钟3次
        'auth/phone-login' => [
            'max_requests' => 3,
            'window' => 60,
        ],
        // 排盘接口：每分钟10次
        'paipan/bazi' => [
            'max_requests' => 10,
            'window' => 60,
        ],
        // 塔罗抽牌：每分钟10次
        'tarot/draw' => [
            'max_requests' => 10,
            'window' => 60,
        ],
        // 签到接口：每分钟3次
        'daily/checkin' => [
            'max_requests' => 3,
            'window' => 60,
        ],
        // AI分析接口：每小时20次（更严格的限制）
        'ai/analysis' => [
            'max_requests' => 20,
            'window' => 3600, // 1小时
        ],
        // AI流式分析：每小时10次
        'ai/analysis-stream' => [
            'max_requests' => 10,
            'window' => 3600,
        ],
        // 支付下单：每分钟5次
        'payment/order' => [
            'max_requests' => 5,
            'window' => 60,
        ],
        // 短信验证码：每分钟1次，每小时5次（双重限制）
        'sms/send-code' => [
            'max_requests' => 1,
            'window' => 60,
        ],
    ];
    
    /**
     * 处理请求
     */
    public function handle($request, \Closure $next)
    {
        $path = $request->pathinfo();
        $clientId = $this->getClientId($request);
        
        // 获取限流配置
        $config = $this->config[$path] ?? $this->config['default'];
        
        // VIP用户放宽限制（2倍）
        $isVip = $this->isVipUser($request);
        if ($isVip) {
            $config['max_requests'] = (int)($config['max_requests'] * 2);
        }
        
        // 生成缓存key
        $key = "rate_limit:{$path}:{$clientId}";
        
        // 获取当前请求次数
        $requests = Cache::get($key, []);
        $now = time();
        
        // 清理过期的请求记录
        $requests = array_filter($requests, function($time) use ($now, $config) {
            return $time > ($now - $config['window']);
        });
        
        // 检查是否超过限制
        if (count($requests) >= $config['max_requests']) {
            $retryAfter = min($requests) + $config['window'] - $now;
            
            // 获取请求来源，添加CORS头
            $origin = $request->header('Origin') ?: '*';
            
            return response(json_encode([
                'code' => 429,
                'message' => '请求过于频繁，请稍后再试',
                'data' => [
                    'retry_after' => max(1, $retryAfter),
                ],
            ]), 429, [
                'Content-Type' => 'application/json',
                'Access-Control-Allow-Origin' => $origin,
                'Access-Control-Allow-Headers' => 'Authorization, Content-Type, X-Requested-With, X-Token, Accept, Origin',
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
                'Access-Control-Allow-Credentials' => 'true',
                'X-RateLimit-Limit' => $config['max_requests'],
                'X-RateLimit-Remaining' => 0,
                'X-RateLimit-Reset' => $now + $retryAfter,
            ]);
        }
        
        // 记录本次请求
        $requests[] = $now;
        Cache::set($key, $requests, $config['window']);
        
        // 添加响应头
        $response = $next($request);
        
        if (method_exists($response, 'header')) {
            $response->header([
                'X-RateLimit-Limit' => $config['max_requests'],
                'X-RateLimit-Remaining' => $config['max_requests'] - count($requests),
                'X-RateLimit-Reset' => $now + $config['window'],
                'X-RateLimit-VIP' => $isVip ? '1' : '0',
            ]);
        }
        
        return $response;
    }
    
    /**
     * 检查用户是否为VIP
     */
    protected function isVipUser($request): bool
    {
        // 检查用户是否已登录
        if (!$request->user || !isset($request->user['sub'])) {
            return false;
        }
        
        // 从用户信息中检查VIP状态
        // 优先使用缓存中的VIP状态
        $cacheKey = 'user_vip_status:' . $request->user['sub'];
        $vipStatus = Cache::get($cacheKey);
        
        if ($vipStatus !== null) {
            return (bool)$vipStatus;
        }
        
        // 从数据库查询VIP状态
        try {
            $user = \app\model\User::find($request->user['sub']);
            if ($user && isset($user->is_vip) && $user->is_vip) {
                // 缓存VIP状态5分钟
                Cache::set($cacheKey, 1, 300);
                return true;
            }
            // 缓存非VIP状态5分钟
            Cache::set($cacheKey, 0, 300);
        } catch (\Exception $e) {
            // 查询失败时默认非VIP
            return false;
        }
        
        return false;
    }
    
    /**
     * 获取客户端标识
     */
    protected function getClientId($request): string
    {
        // 优先使用用户ID（已登录用户）
        if ($request->user && isset($request->user['sub'])) {
            return 'user_' . $request->user['sub'];
        }
        
        // 使用IP地址（未登录用户）
        $ip = $request->ip();
        
        // 如果IP获取失败，使用一个默认值
        if (empty($ip)) {
            $ip = 'unknown';
        }
        
        return 'ip_' . md5($ip);
    }
}
