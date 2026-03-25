<?php
declare(strict_types=1);

namespace app\middleware;

use app\service\SchemaInspector;
use app\service\SensitiveDataSanitizer;
use think\facade\Db;
use think\facade\Log;
use think\Request;
use think\Response;

/**
 * API 请求日志中间件
 *
 * 将前台 API 请求记录到 tc_api_log 表，供管理端查看。
 * 表不存在时静默跳过，不影响正常请求。
 */
class ApiLog
{
    /** 不记录日志的路径前缀 */
    protected array $skipPrefixes = [
        'api/maodou/auth/login',
        'api/maodou/dashboard',
    ];

    public function handle(Request $request, \Closure $next): Response
    {
        $startTime = microtime(true);
        $response  = $next($request);
        $duration  = (int) round((microtime(true) - $startTime) * 1000);

        $path = (string) $request->pathinfo();

        // 跳过不需要记录的路径
        foreach ($this->skipPrefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                return $response;
            }
        }

        $this->writeLog($request, $response, $duration);

        return $response;
    }

    protected function writeLog(Request $request, Response $response, int $duration): void
    {
        try {
            // 表不存在则跳过
            static $tableChecked = null;
            if ($tableChecked === null) {
                $tableChecked = SchemaInspector::tableExists('tc_api_log');
            }
            if (!$tableChecked) {
                return;
            }

            // 解析业务响应码
            $responseCode = 200;
            try {
                $body = $response->getContent();
                $decoded = json_decode((string) $body, true);
                if (is_array($decoded) && isset($decoded['code'])) {
                    $responseCode = (int) $decoded['code'];
                }
            } catch (\Throwable $ignored) {}

            // 获取当前用户ID（前台用户）
            $userId = 0;
            $user = $request->user ?? null;
            if (is_array($user) && isset($user['id'])) {
                $userId = (int) $user['id'];
            } elseif (is_array($user) && isset($user['sub'])) {
                $userId = (int) $user['sub'];
            }

            // 请求体脱敏
            $requestBody = null;
            if (in_array($request->method(), ['POST', 'PUT', 'PATCH'], true)) {
                $params = SensitiveDataSanitizer::getFilteredRequestParams($request);
                $encoded = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $requestBody = $encoded !== false ? $encoded : null;
            }

            Db::table('tc_api_log')->insert([
                'path'          => (string) $request->pathinfo(),
                'method'        => (string) $request->method(),
                'status'        => $response->getCode(),
                'user_id'       => $userId,
                'ip'            => (string) $request->ip(),
                'request_body'  => $requestBody,
                'response_code' => $responseCode,
                'duration_ms'   => $duration,
                'created_at'    => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            Log::error('API日志写入失败', [
                'path'  => (string) $request->pathinfo(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}
