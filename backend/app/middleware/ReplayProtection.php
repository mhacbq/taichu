<?php
declare(strict_types=1);

namespace app\middleware;

use think\facade\Cache;
use think\Request;
use think\Response;

/**
 * 防重放攻击中间件
 * 通过请求签名和时间戳防止重放攻击
 */
class ReplayProtection
{
    /**
     * 时间戳允许的误差范围（秒）
     */
    protected int $timeWindow = 300; // 5分钟
    
    /**
     * 签名有效期（秒）
     */
    protected int $signatureTTL = 60; // 1分钟
    
    /**
     * 是否需要验证（开发环境可关闭）
     */
    protected bool $enabled;
    
    public function __construct()
    {
        $this->enabled = env('REPLAY_PROTECTION_ENABLED', true);
    }

    /**
     * 处理请求
     */
    public function handle(Request $request, \Closure $next): Response
    {
        // 如果未启用，直接放行
        if (!$this->enabled) {
            return $next($request);
        }
        
        // 只对特定请求方法进行验证
        $method = strtoupper($request->method());
        if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            return $next($request);
        }
        
        // 跳过白名单路径
        if ($this->isWhitelisted($request)) {
            return $next($request);
        }

        // 获取请求头
        $timestamp = $request->header('X-Request-Timestamp');
        $nonce = $request->header('X-Request-Nonce');
        $signature = $request->header('X-Request-Signature');

        // 验证必要参数
        if (empty($timestamp) || empty($nonce) || empty($signature)) {
            return $this->reject('缺少请求签名参数');
        }

        // 验证时间戳
        if (!$this->validateTimestamp($timestamp)) {
            return $this->reject('请求已过期，请重新发送');
        }

        // 验证签名唯一性（防止重放）
        if (!$this->validateNonce($nonce)) {
            return $this->reject('重复的请求');
        }

        // 验证签名正确性
        if (!$this->validateSignature($request, $timestamp, $nonce, $signature)) {
            return $this->reject('请求签名无效');
        }

        // 存储nonce，防止重放
        $this->storeNonce($nonce);

        return $next($request);
    }

    /**
     * 检查是否在白名单中
     */
    protected function isWhitelisted(Request $request): bool
    {
        $whitelist = [
            '/api/payment/notify',  // 支付回调
            '/api/upload/image',    // 图片上传
            '/api/upload/file',     // 文件上传
        ];
        
        $path = $request->pathinfo();
        
        foreach ($whitelist as $pattern) {
            if (strpos($path, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * 验证时间戳
     */
    protected function validateTimestamp(string $timestamp): bool
    {
        $requestTime = (int) $timestamp;
        $currentTime = time();
        
        // 检查时间戳是否在允许范围内
        return abs($currentTime - $requestTime) <= $this->timeWindow;
    }

    /**
     * 验证nonce是否已使用
     */
    protected function validateNonce(string $nonce): bool
    {
        $cacheKey = 'replay_nonce:' . $nonce;
        return !Cache::has($cacheKey);
    }

    /**
     * 存储nonce
     */
    protected function storeNonce(string $nonce): void
    {
        $cacheKey = 'replay_nonce:' . $nonce;
        Cache::set($cacheKey, 1, $this->signatureTTL);
    }

    /**
     * 验证签名
     */
    protected function validateSignature(
        Request $request, 
        string $timestamp, 
        string $nonce, 
        string $signature
    ): bool {
        // 构建签名数据
        $method = strtoupper($request->method());
        $path = $request->pathinfo();
        $body = $request->getContent();
        
        // 获取用户token作为签名密钥的一部分
        $token = $request->header('Authorization', '');
        $tokenPart = '';
        if (preg_match('/Bearer\s+(\S+)/', $token, $matches)) {
            // 使用token的前16位作为签名的一部分
            $tokenPart = substr($matches[1], 0, 16);
        }
        
        // 构建待签名字符串
        $signData = sprintf(
            "%s\n%s\n%s\n%s\n%s",
            $method,
            $path,
            $timestamp,
            $nonce,
            md5($body)
        );
        
        // 获取签名密钥
        $secret = $this->getSignatureSecret($tokenPart);
        
        // 计算签名
        $expectedSignature = hash_hmac('sha256', $signData, $secret);
        
        // 比较签名（使用hash_equals防止时序攻击）
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * 获取签名密钥
     */
    protected function getSignatureSecret(string $tokenPart): string
    {
        // 使用JWT密钥的一部分作为签名密钥
        $jwtSecret = env('JWT_SECRET', '');
        
        if (empty($jwtSecret)) {
            throw new \Exception('JWT密钥未配置');
        }
        
        return hash_hmac('sha256', $tokenPart, $jwtSecret);
    }

    /**
     * 拒绝请求
     */
    protected function reject(string $message): Response
    {
        return json([
            'code' => 403,
            'message' => $message,
        ], 403);
    }
}
