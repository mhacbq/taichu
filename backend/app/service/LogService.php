<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Log;
use think\facade\Request;

/**
 * 日志服务
 * 
 * 统一处理应用日志记录，支持多种日志类型和级别
 */
class LogService
{
    // 日志级别
    const LEVEL_DEBUG = 'debug';
    const LEVEL_INFO = 'info';
    const LEVEL_NOTICE = 'notice';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_CRITICAL = 'critical';
    const LEVEL_ALERT = 'alert';
    const LEVEL_EMERGENCY = 'emergency';
    
    // 日志类型
    const TYPE_API = 'api';              // API请求日志
    const TYPE_BUSINESS = 'business';    // 业务日志
    const TYPE_ERROR = 'error';          // 错误日志
    const TYPE_SECURITY = 'security';    // 安全日志
    const TYPE_PERFORMANCE = 'perf';     // 性能日志
    const TYPE_POINTS = 'points';        // 积分变动日志
    const TYPE_PAYMENT = 'payment';      // 支付日志
    const TYPE_USER_ACTION = 'action';   // 用户行为日志
    
    /**
     * 记录API请求日志
     */
    public static function apiRequest(string $api, array $params = [], float $duration = 0, $response = null): void
    {
        $userId = self::getCurrentUserId();
        $ip = Request::ip();
        
        $logData = [
            'type' => self::TYPE_API,
            'api' => $api,
            'user_id' => $userId,
            'ip' => $ip,
            'params' => self::sanitizeParams($params),
            'duration_ms' => round($duration * 1000, 2),
            'timestamp' => date('Y-m-d H:i:s'),
        ];
        
        if ($duration > 1) {
            $logData['slow_request'] = true;
            Log::channel('api')->warning('Slow API request', $logData);
        } else {
            Log::channel('api')->info('API request', $logData);
        }
    }
    
    /**
     * 记录业务操作日志
     */
    public static function business(string $action, array $data = [], string $level = self::LEVEL_INFO): void
    {
        $userId = self::getCurrentUserId();
        
        $logData = [
            'type' => self::TYPE_BUSINESS,
            'action' => $action,
            'user_id' => $userId,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
        
        Log::channel('business')->log($level, $action, $logData);
    }
    
    /**
     * 记录错误日志
     */
    public static function error(\Throwable $exception, string $context = '', array $extraData = []): void
    {
        $userId = self::getCurrentUserId();
        $ip = Request::ip();
        
        $logData = [
            'type' => self::TYPE_ERROR,
            'context' => $context,
            'user_id' => $userId,
            'ip' => $ip,
            'error' => [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ],
            'extra' => $extraData,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
        
        Log::channel('error')->error($context ?: 'Application error', $logData);
    }
    
    /**
     * 记录安全相关日志
     */
    public static function security(string $event, array $data = [], string $level = self::LEVEL_WARNING): void
    {
        $userId = self::getCurrentUserId();
        $ip = Request::ip();
        $userAgent = Request::header('User-Agent');
        
        $logData = [
            'type' => self::TYPE_SECURITY,
            'event' => $event,
            'user_id' => $userId,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
        
        Log::channel('security')->log($level, $event, $logData);
    }
    
    /**
     * 记录性能日志
     */
    public static function performance(string $operation, float $duration, array $extraData = []): void
    {
        $logData = [
            'type' => self::TYPE_PERFORMANCE,
            'operation' => $operation,
            'duration_ms' => round($duration * 1000, 2),
            'extra' => $extraData,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
        
        // 超过阈值的记录为警告
        if ($duration > 3) {
            $logData['slow_operation'] = true;
            Log::channel('performance')->warning('Slow operation: ' . $operation, $logData);
        } else {
            Log::channel('performance')->info('Performance: ' . $operation, $logData);
        }
    }
    
    /**
     * 记录积分变动日志
     */
    public static function pointsChange(int $userId, int $change, int $balance, string $reason, array $extra = []): void
    {
        $logData = [
            'type' => self::TYPE_POINTS,
            'user_id' => $userId,
            'change' => $change,
            'balance' => $balance,
            'reason' => $reason,
            'extra' => $extra,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
        
        Log::channel('points')->info('Points change', $logData);
    }
    
    /**
     * 记录支付相关日志
     */
    public static function payment(string $action, array $data, string $level = self::LEVEL_INFO): void
    {
        $userId = self::getCurrentUserId();
        $ip = Request::ip();
        
        // 敏感信息脱敏
        $sanitizedData = self::sanitizeSensitiveData($data);
        
        $logData = [
            'type' => self::TYPE_PAYMENT,
            'action' => $action,
            'user_id' => $userId,
            'ip' => $ip,
            'data' => $sanitizedData,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
        
        Log::channel('payment')->log($level, $action, $logData);
    }
    
    /**
     * 记录用户行为日志
     */
    public static function userAction(string $action, array $data = []): void
    {
        $userId = self::getCurrentUserId();
        $ip = Request::ip();
        
        $logData = [
            'type' => self::TYPE_USER_ACTION,
            'action' => $action,
            'user_id' => $userId,
            'ip' => $ip,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
        
        Log::channel('action')->info('User action: ' . $action, $logData);
    }
    
    /**
     * 记录登录日志
     */
    public static function login(int $userId, string $type, bool $success, string $message = ''): void
    {
        $ip = Request::ip();
        $userAgent = Request::header('User-Agent');
        
        $logData = [
            'type' => self::TYPE_SECURITY,
            'event' => $success ? 'login_success' : 'login_failed',
            'user_id' => $userId,
            'login_type' => $type,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
        
        $level = $success ? self::LEVEL_INFO : self::LEVEL_WARNING;
        Log::channel('security')->log($level, 'Login ' . ($success ? 'success' : 'failed'), $logData);
    }
    
    /**
     * 获取当前用户ID
     */
    protected static function getCurrentUserId(): ?int
    {
        try {
            $user = Request::user ?? null;
            return $user['sub'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * 清理参数中的敏感信息
     */
    protected static function sanitizeParams(array $params): array
    {
        $sensitiveKeys = ['password', 'token', 'code', 'secret', 'key', 'credit_card'];
        
        $sanitized = [];
        foreach ($params as $key => $value) {
            $lowerKey = strtolower($key);
            $isSensitive = false;
            
            foreach ($sensitiveKeys as $sensitiveKey) {
                if (strpos($lowerKey, $sensitiveKey) !== false) {
                    $isSensitive = true;
                    break;
                }
            }
            
            if ($isSensitive) {
                $sanitized[$key] = '***';
            } elseif (is_array($value)) {
                $sanitized[$key] = self::sanitizeParams($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }
    
    /**
     * 敏感数据脱敏
     */
    protected static function sanitizeSensitiveData(array $data): array
    {
        $sensitiveFields = ['card_no', 'cvv', 'password', 'secret', 'token'];
        
        foreach ($data as $key => $value) {
            $lowerKey = strtolower($key);
            
            foreach ($sensitiveFields as $field) {
                if (strpos($lowerKey, $field) !== false && is_string($value)) {
                    $data[$key] = substr_replace($value, '****', 0, strlen($value) - 4);
                    break;
                }
            }
        }
        
        return $data;
    }
}