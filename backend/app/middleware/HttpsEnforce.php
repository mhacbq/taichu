<?php
declare(strict_types=1);

namespace app\middleware;

use think\facade\Env;

/**
 * HTTPS强制中间件
 * 
 * 在非调试模式下强制使用HTTPS连接
 * 同时添加必要的安全响应头
 */
class HttpsEnforce
{
    /**
     * 需要跳过的路径（如健康检查接口）
     */
    protected $skipPaths = [
        '/health',
        '/api/health',
    ];
    
    public function handle($request, \Closure $next)
    {
        // 检查是否需要跳过
        $path = $request->pathinfo();
        if (in_array($path, $this->skipPaths)) {
            return $next($request);
        }
        
        // 只在生产环境强制HTTPS
        if (!Env::get('APP_DEBUG', false)) {
            // 检查是否使用HTTPS
            $isSecure = $this->isSecureConnection($request);
            
            if (!$isSecure) {
                // 获取HTTPS URL并重定向
                $httpsUrl = $this->getHttpsUrl($request);
                
                return redirect($httpsUrl, 301); // 永久重定向
            }
        }
        
        $response = $next($request);
        
        // 添加安全响应头
        $this->addSecurityHeaders($response);
        
        return $response;
    }
    
    /**
     * 检查是否使用安全连接
     */
    protected function isSecureConnection($request): bool
    {
        // 直接HTTPS检测
        if ($request->isSsl()) {
            return true;
        }
        
        // 检查转发头（负载均衡/代理场景）
        $forwardedProto = $request->header('X-Forwarded-Proto');
        if ($forwardedProto === 'https') {
            return true;
        }
        
        // 检查转发SSL标记
        $forwardedSsl = $request->header('X-Forwarded-Ssl');
        if ($forwardedSsl === 'on') {
            return true;
        }
        
        // 检查前端HTTPS标记
        $frontEndHttps = $request->header('Front-End-Https');
        if ($frontEndHttps === 'on') {
            return true;
        }
        
        return false;
    }
    
    /**
     * 获取HTTPS URL
     */
    protected function getHttpsUrl($request): string
    {
        $host = $request->host(true);
        $uri = $request->url();
        
        return 'https://' . $host . $uri;
    }
    
    /**
     * 添加安全响应头
     */
    protected function addSecurityHeaders($response): void
    {
        // HSTS - 强制使用HTTPS（6个月）
        $response->header(['Strict-Transport-Security' => 'max-age=15768000; includeSubDomains; preload']);
        
        // 防止点击劫持
        $response->header(['X-Frame-Options' => 'SAMEORIGIN']);
        
        // XSS保护
        $response->header(['X-XSS-Protection' => '1; mode=block']);
        
        // 内容类型嗅探保护
        $response->header(['X-Content-Type-Options' => 'nosniff']);
        
        // 引用来源策略
        $response->header(['Referrer-Policy' => 'strict-origin-when-cross-origin']);
        
        // 权限策略
        $response->header(['Permissions-Policy' => 
            'camera=(), microphone=(), geolocation=(self), payment=()']);
    }
}