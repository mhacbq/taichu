<?php
declare(strict_types=1);

namespace app\middleware;

class Cors
{
    public function handle($request, \Closure $next)
    {
        // 获取请求来源
        $origin = $request->header('Origin') ?: '*';
        
        // 如果是 OPTIONS 预检请求，直接返回
        if ($request->method() === 'OPTIONS') {
            return response('', 204)
                ->header('Access-Control-Allow-Origin', $origin)
                ->header('Access-Control-Allow-Headers', 'Authorization, Content-Type, X-Requested-With, X-Token, Accept, Origin')
                ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
                ->header('Access-Control-Allow-Credentials', 'true')
                ->header('Access-Control-Max-Age', '86400');
        }
        
        $response = $next($request);
        
        // 添加 CORS 头
        $response->header([
            'Access-Control-Allow-Origin' => $origin,
            'Access-Control-Allow-Headers' => 'Authorization, Content-Type, X-Requested-With, X-Token, Accept, Origin',
            'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age' => '86400',
        ]);
        
        return $response;
    }
}
