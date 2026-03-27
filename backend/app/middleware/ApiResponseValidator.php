<?php
declare(strict_types=1);

namespace app\middleware;

use think\Request;
use think\Response;

/**
 * API响应验证中间件
 * 统一API响应格式，防止敏感信息泄露
 */
class ApiResponseValidator
{
    /**
     * 敏感字段列表
     */
    protected array $sensitiveFields = [
        'password',
        'passwd',
        'pwd',
        'secret',
        'token',
        'access_token',
        'refresh_token',
        'api_key',
        'app_secret',
        'private_key',
        'credit_card',
        'bank_account',
        'id_card',
        'openid',
        'unionid',
    ];
    
    /**
     * 需要脱敏的字段
     */
    protected array $maskFields = [
        'phone' => [3, 4],      // 保留前3后4
        'email' => [2, 3],      // 保留前2后3
        'id_number' => [4, 4],  // 保留前4后4
        'real_name' => [1, 0],  // 只保留第一个字
    ];

    /**
     * 处理响应
     */
    public function handle(Request $request, \Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);
        
        // 只处理JSON响应
        $contentType = $response->getHeader('Content-Type');
        if (is_array($contentType)) {
            $contentType = implode(';', $contentType);
        }
        
        if (!is_string($contentType) || strpos($contentType, 'application/json') === false) {
            return $response;
        }
        
        // 获取响应内容
        $content = $response->getContent();
        $data = json_decode($content, true);
        
        if (!is_array($data)) {
            return $response;
        }
        
        // 过滤敏感字段
        $data = $this->filterSensitiveData($data);
        
        // 统一响应格式
        $data = $this->normalizeResponse($data);
        
        // 设置响应
        $response->content(json_encode($data));
        
        return $response;
    }

    /**
     * 过滤敏感数据
     */
    protected function filterSensitiveData(array $data): array
    {
        array_walk_recursive($data, function (&$value, $key) {
            // 检查是否是敏感字段
            $lowerKey = strtolower($key);
            
            foreach ($this->sensitiveFields as $field) {
                if (strpos($lowerKey, $field) !== false) {
                    $value = '***REDACTED***';
                    return;
                }
            }
            
            // 脱敏处理
            if (isset($this->maskFields[$lowerKey])) {
                $value = $this->maskValue($value, $this->maskFields[$lowerKey]);
            }
        });
        
        return $data;
    }

    /**
     * 脱敏处理
     */
    protected function maskValue(?string $value, array $keep): string
    {
        if (empty($value)) {
            return '';
        }
        
        $length = mb_strlen($value);
        $keepStart = $keep[0] ?? 0;
        $keepEnd = $keep[1] ?? 0;
        
        if ($length <= $keepStart + $keepEnd) {
            return str_repeat('*', $length);
        }
        
        $start = mb_substr($value, 0, $keepStart);
        $end = mb_substr($value, -$keepEnd);
        $maskedLength = $length - $keepStart - $keepEnd;
        
        return $start . str_repeat('*', $maskedLength) . $end;
    }

    /**
     * 统一响应格式
     */
    protected function normalizeResponse(array $data): array
    {
        // 如果已经是标准格式，直接返回
        if (isset($data['code']) && (isset($data['data']) || isset($data['message']))) {
            return $data;
        }
        
        // 包装为标准格式
        return [
            'code' => 0,
            'data' => $data,
            'message' => 'success',
        ];
    }
}
