<?php
declare(strict_types=1);

namespace app\middleware;

use think\Request;
use think\Response;

/**
 * 安全响应头中间件
 *
 * 添加各种安全相关的HTTP响应头。
 * 管理后台路由（/api/maodou/*）使用更严格的策略并注入 X-Robots-Tag: noindex。
 */
class SecurityHeaders
{
    /**
     * 前台通用安全头
     */
    protected array $defaultHeaders = [
        'X-Content-Type-Options'  => 'nosniff',
        'X-Frame-Options'         => 'SAMEORIGIN',
        'X-XSS-Protection'        => '1; mode=block',
        'Referrer-Policy'         => 'strict-origin-when-cross-origin',
        'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' data:; connect-src 'self' https:; media-src 'self'; object-src 'none'; frame-ancestors 'self'; base-uri 'self'; form-action 'self';",
        'Permissions-Policy'      => 'accelerometer=(), camera=(), geolocation=(), gyroscope=(), magnetometer=(), microphone=(), payment=(), usb=()',
    ];

    /**
     * 管理后台专属覆盖头（更严格）
     */
    protected array $adminHeaders = [
        // 禁止所有搜索引擎索引和跟踪
        'X-Robots-Tag'            => 'noindex, nofollow, noarchive, nosnippet',
        // 完全禁止内嵌（防止管理后台被 iframe 嵌入攻击）
        'X-Frame-Options'         => 'DENY',
        // 更严格的 CSP：不允许 unsafe-eval
        'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self' data:; connect-src 'self'; object-src 'none'; frame-ancestors 'none'; base-uri 'self'; form-action 'self';",
        // 缓存禁止（防止浏览器/代理缓存管理端响应）
        'Cache-Control'           => 'no-store, no-cache, must-revalidate, private',
        'Pragma'                  => 'no-cache',
    ];

    public function handle(Request $request, \Closure $next): Response
    {
        $response = $next($request);

        $isAdmin = str_starts_with((string) $request->pathinfo(), 'api/maodou');

        $headers = $this->defaultHeaders;
        if ($isAdmin) {
            $headers = array_merge($headers, $this->adminHeaders);
        }

        $response->header($headers);

        return $response;
    }
}
