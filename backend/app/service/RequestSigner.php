<?php
declare(strict_types=1);

namespace app\service;

/**
 * 请求签名服务
 * 用于前端生成请求签名
 */
class RequestSigner
{
    /**
     * 生成请求签名
     * 
     * @param string $method 请求方法
     * @param string $path 请求路径
     * @param string $body 请求体
     * @param string $token JWT token
     * @param string $timestamp 时间戳
     * @param string $nonce 随机字符串
     * @return string 签名
     */
    public static function generateSignature(
        string $method,
        string $path,
        string $body,
        string $token,
        string $timestamp,
        string $nonce
    ): string {
        // 使用token的前16位
        $tokenPart = substr($token, 0, 16);
        
        // 构建待签名字符串
        $signData = sprintf(
            "%s\n%s\n%s\n%s\n%s",
            strtoupper($method),
            $path,
            $timestamp,
            $nonce,
            md5($body)
        );
        
        // 获取签名密钥（需要与后端一致）
        $secret = self::getSignatureSecret($tokenPart);
        
        // 计算签名
        return hash_hmac('sha256', $signData, $secret);
    }
    
    /**
     * 获取签名密钥
     * 注意：实际项目中，前端不应该知道JWT密钥
     * 这里仅作为示例，实际应该使用其他签名方式
     */
    protected static function getSignatureSecret(string $tokenPart): string
    {
        // 实际项目中，可以使用一个独立的签名密钥
        // 或者使用用户特定的密钥
        $signKey = env('API_SIGN_KEY', 'default-sign-key-change-in-production');
        
        return hash_hmac('sha256', $tokenPart, $signKey);
    }
    
    /**
     * 生成随机nonce
     */
    public static function generateNonce(): string
    {
        return bin2hex(random_bytes(16));
    }
    
    /**
     * 获取当前时间戳
     */
    public static function getTimestamp(): int
    {
        return time();
    }
}
