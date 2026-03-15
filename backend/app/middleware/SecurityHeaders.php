<?php
declare(strict_types=1);

namespace app\middleware;

/**
 * 安全响应头中间件
 * 
 * 添加各种安全相关的HTTP响应头
 */
class SecurityHeaders
{
    /**
     * 安全响应头配置
     */
    protected $headers = [
        // 防止MIME类型嗅探
        'X-Content-Type-Options' => 'nosniff',
        
        // 防止点击劫持
        'X-Frame-Options' => 'SAMEORIGIN',
        
        // XSS保护（现代浏览器）
        'X-XSS-Protection' => '1; mode=block',
        
        // Referrer策略
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        
        // 内容安全策略
        'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self' https:; media-src 'self'; object-src 'none'; frame-ancestors 'self'; base-uri 'self'; form-action 'self';",
        
        // 权限策略（限制浏览器功能）
        'Permissions-Policy' => 'accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()',
        
        // HSTS（强制HTTPS）- 注意：仅在全站HTTPS时启用
        // 'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
    ];
    
    public function handle($request, \Closure $next)
    {
        $response = $next($request);
        
        // 添加安全响应头
        foreach ($this->headers as $name => $value) {
            $response->header($name, $value);
        }
        
        return $response;
    }
}