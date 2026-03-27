<?php

namespace app\middleware;

use think\Request;
use think\Response;

/**
 * API响应标准化中间件
 * 统一API响应格式，提升响应性能
 */
class ApiResponse
{
    /**
     * 处理请求
     */
    public function handle(Request $request, \Closure $next)
    {
        $startTime = microtime(true);

        // 执行请求
        $response = $next($request);

        // 计算执行时间
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);

        // 如果是JSON响应，进行标准化处理
        if ($response instanceof Response && $response->getHeader('Content-Type')[0] === 'application/json') {
            $response = $this->standardizeResponse($response, $executionTime);
        }

        return $response;
    }

    /**
     * 标准化响应格式
     */
    protected function standardizeResponse(Response $response, $executionTime)
    {
        $data = json_decode($response->getContent(), true);

        // 如果已经是标准格式，只添加性能信息
        if (isset($data['code'])) {
            $data['meta'] = [
                'execution_time' => $executionTime . 'ms',
                'timestamp' => time(),
            ];
            $response->content(json_encode($data, JSON_UNESCAPED_UNICODE));
            return $response;
        }

        // 转换为标准格式
        $standardData = [
            'code' => 0,
            'message' => 'success',
            'data' => $data,
            'meta' => [
                'execution_time' => $executionTime . 'ms',
                'timestamp' => time(),
            ],
        ];

        $response->content(json_encode($standardData, JSON_UNESCAPED_UNICODE));
        return $response;
    }
}
