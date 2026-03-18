<?php
declare(strict_types=1);

namespace app\middleware;

use app\service\SensitiveDataSanitizer;
use think\facade\Log;

/**
 * 审计日志中间件
 *
 * 记录敏感操作和安全事件。
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
        $startTime = microtime(true);

        $response = $next($request);

        $duration = round((microtime(true) - $startTime) * 1000, 2);

        if ($this->shouldLog($path, $method)) {
            $this->logAudit($request, $response, $duration);
        }

        if ($duration > 3000) {
            $this->logSlowRequest($request, $duration);
        }

        return $response;
    }

    protected function shouldLog(string $path, string $method): bool
    {
        if (!in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
            return false;
        }

        foreach ($this->sensitiveActions as $action => $desc) {
            if (strpos($path, $action) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function logAudit($request, $response, float $duration): void
    {
        $user = $request->user ?? null;

        Log::channel('audit')->info('Audit Log', [
            'type' => 'audit',
            'timestamp' => date('Y-m-d H:i:s'),
            'path' => (string) $request->pathinfo(),
            'method' => (string) $request->method(),
            'user_id' => $user['sub'] ?? null,
            'ip' => (string) $request->ip(),
            'user_agent' => (string) $request->header('User-Agent', ''),
            'params' => SensitiveDataSanitizer::getFilteredRequestParams($request),
            'response_code' => method_exists($response, 'getCode') ? $response->getCode() : null,
            'duration_ms' => $duration,
        ]);
    }

    protected function logSlowRequest($request, float $duration): void
    {
        Log::warning('Slow Request', [
            'path' => (string) $request->pathinfo(),
            'method' => (string) $request->method(),
            'duration_ms' => $duration,
            'user_id' => $request->user['sub'] ?? null,
            'ip' => (string) $request->ip(),
        ]);
    }
}
