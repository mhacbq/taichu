<?php
declare(strict_types=1);

namespace app\middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use think\facade\Config;

class OptionalAuth
{
    public function handle($request, \Closure $next)
    {
        $token = $this->getToken($request);

        if (!$token || !is_string($token) || strlen($token) < 10) {
            return $next($request);
        }

        try {
            $secret = Config::get('jwt.secret');
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            $request->user = (array) $decoded;
        } catch (\Throwable $e) {
            // 公开接口只做静默鉴权，令牌异常时按游客继续访问。
        }

        return $next($request);
    }

    protected function getToken($request): ?string
    {
        $header = $request->header('Authorization');

        if ($header && strpos($header, 'Bearer ') === 0) {
            return substr($header, 7);
        }

        return $request->param('token');
    }
}
