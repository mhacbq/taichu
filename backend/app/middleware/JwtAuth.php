<?php

namespace app\middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use think\Request;
use think\Response;

/**
 * JWT认证中间件 - 安全加固版
 */
class JwtAuth
{
    protected $secret;
    protected $algorithm = 'HS256';
    protected $tokenLeeway = 60; // 令牌宽容时间（秒）

    public function __construct()
    {
        $this->secret = env('JWT_SECRET', 'your-secret-key-change-in-production');
    }

    /**
     * 处理请求
     */
    public function handle(Request $request, \Closure $next)
    {
        $token = $this->getTokenFromRequest($request);

        if (!$token) {
            return $this->error('未提供认证令牌', 401);
        }

        try {
            $payload = $this->validateToken($token);

            // 将用户信息注入到请求中
            $request->user_id = $payload['uid'];
            $request->user = $this->getUserFromPayload($payload);

            // 续期令牌（距离过期时间小于30分钟时）
            if ($this->shouldRenewToken($payload)) {
                $newToken = $this->generateToken($payload['uid']);
                $response = $next($request);
                return $response->header('Authorization', 'Bearer ' . $newToken);
            }

            return $next($request);

        } catch (\Exception $e) {
            return $this->error('认证失败：' . $e->getMessage(), 401);
        }
    }

    /**
     * 从请求中获取令牌
     */
    protected function getTokenFromRequest(Request $request)
    {
        $header = $request->header('Authorization');

        if ($header && preg_match('/Bearer\s+(.*)$/i', $header, $matches)) {
            return $matches[1];
        }

        // 支持从cookie中获取
        return cookie('jwt_token') ?: $request->param('token');
    }

    /**
     * 验证令牌
     */
    protected function validateToken($token)
    {
        JWT::$leeway = $this->tokenLeeway;

        // 检查黑名单
        $jti = $this->getJtiFromToken($token);
        if ($this->isTokenBlacklisted($jti)) {
            throw new \Exception('令牌已失效');
        }

        $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
        return (array) $decoded;
    }

    /**
     * 从令牌中获取JTI
     */
    protected function getJtiFromToken($token)
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        $payload = json_decode(base64_decode($parts[1]), true);
        return $payload['jti'] ?? null;
    }

    /**
     * 检查令牌是否在黑名单中
     */
    protected function isTokenBlacklisted($jti)
    {
        if (!$jti) {
            return false;
        }

        return cache('jwt_blacklist:' . $jti) !== null;
    }

    /**
     * 从载荷中获取用户信息
     */
    protected function getUserFromPayload($payload)
    {
        // 简化版，实际应从数据库获取
        $userModel = new \app\Models\User();
        return $userModel->find($payload['uid']);
    }

    /**
     * 判断是否需要续期令牌
     */
    protected function shouldRenewToken($payload)
    {
        if (!isset($payload['exp'])) {
            return false;
        }

        $remainingTime = $payload['exp'] - time();
        return $remainingTime < 1800; // 小于30分钟
    }

    /**
     * 生成新令牌
     */
    protected function generateToken($uid, $expireHours = 24)
    {
        $payload = [
            'iss' => env('APP_URL', 'https://taichu.com'),
            'aud' => env('APP_URL', 'https://taichu.com'),
            'iat' => time(),
            'exp' => time() + ($expireHours * 3600),
            'uid' => $uid,
            'jti' => bin2hex(random_bytes(16)), // 唯一标识符
        ];

        return JWT::encode($payload, $this->secret, $this->algorithm);
    }

    /**
     * 返回错误响应
     */
    protected function error($message, $code = 400)
    {
        return Response::create([
            'code' => $code,
            'message' => $message,
            'data' => null,
        ], 'json', $code);
    }
}
