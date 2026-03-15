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
            
            return response(json_encode([
                'code' => 429,
                'message' => '请求过于频繁，请稍后再试',
                'data' => [
                    'retry_after' => max(1, $retryAfter),
                ],
            ]), 429, [
                'Content-Type' => 'application/json',
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
            ]);
        }
        
        return $response;
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
