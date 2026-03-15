<?php
declare(strict_types=1);

namespace app\middleware;

class Cors
{
    public function handle($request, \Closure $next)
    {
        $response = $next($request);
        
        $response->header('Access-Control-Allow-Origin', '*');
        $response->header('Access-Control-Allow-Headers', 'Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-Requested-With');
        $response->header('Access-Control-Allow-Methods', 'GET, POST, PATCH, PUT, DELETE, OPTIONS');
        $response->header('Access-Control-Max-Age', '86400');
        
        return $response;
    }
}
