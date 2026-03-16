<?php
declare(strict_types=1);

namespace app\middleware;

class Cors
{
    /**
     * 允许的域名列表
     * 生产环境应该限制为实际使用的域名
     */
    protected array $allowedOrigins = [
        'http://localhost:3000',
        'http://localhost:5173',
        'http://localhost:8080',
        'http://127.0.0.1:3000',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:8080',
    ];
    
    public function handle($request, \Closure $next)
    {
        // 获取请求来源并验证
        $origin = $request->header('Origin');
        $allowedOrigin = $this->getAllowedOrigin($origin);
        
        // 如果是 OPTIONS 预检请求，直接返回
        if ($request->method() === 'OPTIONS') {
            return response('', 204)
                ->header('Access-Control-Allow-Origin', $allowedOrigin)
                ->header('Access-Control-Allow-Headers', 'Authorization, Content-Type, X-Requested-With, X-Token, Accept, Origin')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Access-Control-Max-Age', '86400');
        }
        
        $response = $next($request);
        
        // 添加 CORS 头
        $response->header([
            'Access-Control-Allow-Origin' => $allowedOrigin,
            'Access-Control-Allow-Headers' => 'Authorization, Content-Type, X-Requested-With, X-Token, Accept, Origin',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age' => '86400',
        ]);
        
        return $response;
    }
    
    /**
     * 获取允许的Origin
     * 如果请求的Origin在允许列表中则返回，否则返回空字符串
     */
    protected function getAllowedOrigin(?string $origin): string
    {
        // 如果没有Origin头，返回空字符串（不允许跨域）
        if (empty($origin)) {
            return '';
        }
        
        // 检查是否在允许列表中
        if (in_array($origin, $this->allowedOrigins, true)) {
            return $origin;
        }
        
        // 生产环境可以添加通配符匹配，如 *.example.com
        foreach ($this->allowedOrigins as $allowed) {
            // 支持通配符匹配子域名
            if (str_starts_with($allowed, '*.')) {
                $domain = substr($allowed, 2);
                if (str_ends_with($origin, $domain)) {
                    return $origin;
                }
            }
        }
        
        // 不在允许列表中，返回空字符串
        return '';
    }
}
