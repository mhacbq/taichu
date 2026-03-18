<?php
declare(strict_types=1);

namespace app\middleware;

use app\service\SensitiveDataSanitizer;

/**
 * 敏感数据过滤中间件
 *
 * 为日志记录预先注入脱敏后的请求参数，并在非后台接口中脱敏响应数据。
 */
class SensitiveDataFilter
{
    public function handle($request, \Closure $next)
    {
        $this->filterRequestForLog($request);

        $response = $next($request);

        if ($this->shouldFilterResponse($request)) {
            $this->filterResponse($response);
        }

        return $response;
    }

    protected function filterRequestForLog($request): void
    {
        $params = method_exists($request, 'param') ? $request->param() : [];
        $request->filteredParams = is_array($params)
            ? SensitiveDataSanitizer::sanitizeArray($params)
            : [];
    }

    protected function shouldFilterResponse($request): bool
    {
        $path = strtolower((string) (method_exists($request, 'pathinfo') ? $request->pathinfo() : ''));
        return !str_starts_with($path, 'api/admin');
    }

    protected function filterResponse($response): void
    {
        if (!is_object($response) || !method_exists($response, 'getContent') || !method_exists($response, 'content')) {
            return;
        }

        $content = $response->getContent();
        if (!is_string($content) || $content === '') {
            return;
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            return;
        }

        $maskedData = SensitiveDataSanitizer::maskResponseData($data);
        $encoded = json_encode($maskedData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) {
            return;
        }

        $response->content($encoded);
    }
}
