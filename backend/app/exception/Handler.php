<?php
declare(strict_types=1);

namespace app\exception;

use app\service\LogService;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\ValidateException;
use think\facade\Request as RequestFacade;
use think\Response;
use Throwable;


/**
 * 全局异常处理类
 */
class Handler extends Handle
{
    protected ?string $requestId = null;

    /**
     * 不需要记录信息（日志）的异常类列表
     */

    protected $ignoreReport = [
        HttpException::class,
        ValidateException::class,
    ];
    
    /**
     * 记录异常信息（包括日志或者其它方式记录）
     */
    public function report(Throwable $exception): void
    {
        // 使用LogService记录错误
        if (!$this->isIgnoreReport($exception)) {
            $context = '';
            
            if ($exception instanceof HttpException) {
                $context = 'HTTP Error ' . $exception->getStatusCode();
            } elseif ($exception instanceof ValidateException) {
                $context = 'Validation Error';
            } else {
                $context = 'Application Error';
            }
            
            LogService::error($exception, $context, [
                'request_id' => $this->resolveRequestId(),
            ]);

        }
        
        parent::report($exception);
    }
    
    /**
     * Render an exception into an HTTP response.
     *
     * @access public
     * @param \think\Request $request
     * @param Throwable $e
     * @return Response
     */
    public function render($request, Throwable $e): Response
    {
        $requestId = $this->resolveRequestId();

        // 添加自定义异常处理机制
        
        // 验证异常
        if ($e instanceof ValidateException) {
            return json([
                'code' => 400,
                'message' => $e->getError(),
                'data' => null,
            ], 400);
        }
        
        // HTTP异常
        if ($e instanceof HttpException) {
            $statusCode = $e->getStatusCode();
            
            return json([
                'code' => $statusCode,
                'message' => $this->getHttpErrorMessage($statusCode),
                'data' => null,
            ], $statusCode);
        }
        
        // 数据库异常
        if ($e instanceof \think\exception\PDOException) {
            return $this->renderSystemErrorResponse(500, '数据库操作失败，请稍后重试', $requestId);
        }
        
        // 业务逻辑异常
        if ($e instanceof \app\exception\BusinessException) {
            return json([
                'code' => $e->getCode() ?: 400,
                'message' => $e->getMessage(),
                'data' => $e->getData(),
            ], 400);
        }

        return $this->renderSystemErrorResponse(500, '服务器内部错误，请稍后重试', $requestId);
    }

    
    protected function renderSystemErrorResponse(int $statusCode, string $message, string $requestId): Response
    {
        return json([
            'code' => $statusCode,
            'message' => $message,
            'data' => [
                'request_id' => $requestId,
            ],
        ], $statusCode);
    }

    protected function resolveRequestId(): string
    {
        if ($this->requestId !== null) {
            return $this->requestId;
        }

        $headerRequestId = trim((string) RequestFacade::header('X-Request-Id', ''));
        if ($headerRequestId !== '') {
            return $this->requestId = substr($headerRequestId, 0, 64);
        }

        try {
            return $this->requestId = 'req_' . date('YmdHis') . '_' . bin2hex(random_bytes(4));
        } catch (Throwable $exception) {
            return $this->requestId = 'req_' . date('YmdHis') . '_' . mt_rand(100000, 999999);
        }
    }

    /**
     * 获取HTTP错误信息
     */
    protected function getHttpErrorMessage(int $statusCode): string
    {

        $messages = [
            400 => '请求参数错误',
            401 => '未授权，请先登录',
            403 => '禁止访问',
            404 => '请求的资源不存在',
            405 => '请求方法不允许',
            408 => '请求超时',
            429 => '请求过于频繁，请稍后再试',
            500 => '服务器内部错误',
            502 => '网关错误',
            503 => '服务暂时不可用',
            504 => '网关超时',
        ];
        
        return $messages[$statusCode] ?? '未知错误';
    }
    
    /**
     * 判断是否需要忽略报告
     */
    protected function isIgnoreReport(Throwable $exception): bool
    {
        foreach ($this->ignoreReport as $class) {
            if ($exception instanceof $class) {
                return true;
            }
        }
        
        return false;
    }
}