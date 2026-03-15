<?php
declare(strict_types=1);

namespace app\middleware;

use think\facade\Log;

/**
 * 审计日志中间件
 * 
 * 记录敏感操作和安全事件
 */
class AuditLog
{
    /**
     * 需要记录的敏感操作
     */
    protected $sensitiveActions = [
        'admin/adjustPoints' => '积分调整',
        'admin/deleteBaziRecord' => '删除八字记录',
        'admin/updateUserStatus' => '更新用户状态',
        'admin/replyFeedback' => '回复反馈',
        'admin/saveSettings' => '修改系统设置',
        'points/consume' => '积分消耗',
        'payment/create-order' => '创建支付订单',
        'auth/login' => '用户登录',
        'auth/phoneLogin' => '手机号登录',
    ];
    
    public function handle($request, \Closure $next)
    {
        $path = $request->pathinfo();
        $method = $request->method();
        
        // 记录请求开始
        $startTime = microtime(true);
        
        $response = $next($request);
        
        // 计算执行时间
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        // 检查是否需要记录审计日志
        if ($this->shouldLog($path, $method)) {
            $this->logAudit($request, $response, $duration);
        }
        
        // 记录慢请求（超过3秒）
        if ($duration > 3000) {
            $this->logSlowRequest($request, $duration);
        }
        
        return $response;
    }
    
    /**
     * 判断是否需要记录审计日志
     */
    protected function shouldLog(string $path, string $method): bool
    {
        // 只记录写操作
        if (!in_array($method, ['POST', 'PUT', 'DELETE'])) {
            return false;
        }
        
        // 检查是否在敏感操作列表中
        foreach ($this->sensitiveActions as $action => $desc) {
            if (strpos($path, $action) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 记录审计日志
     */
    protected function logAudit($request, $response, float $duration): void
    {
        $user = $request->user ?? null;
        $path = $request->pathinfo();
        
        // 过滤敏感参数
        $params = $request->param();
        $filteredParams = $this->filterSensitiveParams($params);
        
        $logData = [
            'type' => 'audit',
            'timestamp' => date('Y-m-d H:i:s'),
            'path' => $path,
            'method' => $request->method(),
            'user_id' => $user['sub'] ?? null,
            'ip' => $request->ip(),
            'user_agent' => $request->header('User-Agent'),
            'params' => $filteredParams,
            'response_code' => $response->getCode(),
            'duration_ms' => $duration,
        ];
        
        Log::channel('audit')->info('Audit Log', $logData);
    }
    
    /**
     * 记录慢请求
     */
    protected function logSlowRequest($request, float $duration): void
    {
        Log::warning('Slow Request', [
            'path' => $request->pathinfo(),
            'method' => $request->method(),
            'duration_ms' => $duration,
            'user_id' => $request->user['sub'] ?? null,
            'ip' => $request->ip(),
        ]);
    }
    
    /**
     * 过滤敏感参数
     */
    protected function filterSensitiveParams(array $params): array
    {
        $sensitiveFields = [
            'password', 'pwd', 'passwd', 'secret', 'token',
            'access_token', 'refresh_token', 'api_key', 'apikey',
            'app_secret', 'private_key', 'cert', 'certificate',
        ];
        
        $filtered = [];
        foreach ($params as $key => $value) {
            $lowerKey = strtolower($key);
            $isSensitive = false;
            
            foreach ($sensitiveFields as $field) {
                if (strpos($lowerKey, $field) !== false) {
                    $isSensitive = true;
                    break;
                }
            }
            
            if ($isSensitive) {
                $filtered[$key] = '***REDACTED***';
            } elseif (is_array($value)) {
                $filtered[$key] = $this->filterSensitiveParams($value);
            } else {
                $filtered[$key] = $value;
            }
        }
        
        return $filtered;
    }
}