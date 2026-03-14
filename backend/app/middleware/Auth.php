<?php
declare(strict_types=1);

namespace app\middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use think\facade\Config;

class Auth
{
    public function handle($request, \Closure $next)
    {
        // 获取Token
        $token = $this->getToken($request);
        
        if (!$token) {
            return json([
                'code' => 401,
                'message' => '请先登录',
                'data' => null,
            ], 401);
        }
        
        try {
            $secret = Config::get('jwt.secret');
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            
            // 将用户信息存入请求
            $request->user = (array) $decoded;
            
            return $next($request);
        } catch (\Firebase\JWT\ExpiredException $e) {
            return json([
                'code' => 401,
                'message' => '登录已过期，请重新登录',
                'data' => null,
            ], 401);
        } catch (\Exception $e) {
            return json([
                'code' => 401,
                'message' => '登录信息无效',
                'data' => null,
            ], 401);
        }
    }
    
    /**
     * 获取Token
     */
    protected function getToken($request): ?string
    {
        $header = $request->header('Authorization');
        
        if ($header && strpos($header, 'Bearer ') === 0) {
            return substr($header, 7);
        }
        
        return $request->param('token');
    }
}
