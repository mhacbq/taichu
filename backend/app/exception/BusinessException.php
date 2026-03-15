<?php
declare(strict_types=1);

namespace app\exception;

use Exception;

/**
 * 业务逻辑异常
 * 
 * 用于表示业务规则验证失败等可预期的异常情况
 */
class BusinessException extends Exception
{
    /**
     * 附加数据
     */
    protected $data;
    
    /**
     * 构造函数
     *
     * @param string $message 错误信息
     * @param int $code 错误码
     * @param array|null $data 附加数据
     */
    public function __construct(string $message = '', int $code = 400, ?array $data = null)
    {
        parent::__construct($message, $code);
        $this->data = $data;
    }
    
    /**
     * 获取附加数据
     *
     * @return array|null
     */
    public function getData(): ?array
    {
        return $this->data;
    }
    
    /**
     * 设置附加数据
     *
     * @param array $data
     * @return $this
     */
    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }
    
    /**
     * 积分不足异常
     */
    public static function insufficientPoints(int $need, int $current): self
    {
        return new self(
            '积分不足，无法完成操作',
            403,
            [
                'need_points' => $need,
                'current_points' => $current,
                'shortage' => $need - $current,
            ]
        );
    }
    
    /**
     * 资源不存在异常
     */
    public static function resourceNotFound(string $resource = '资源'): self
    {
        return new self(
            "{$resource}不存在",
            404
        );
    }
    
    /**
     * 参数验证失败异常
     */
    public static function invalidParameter(string $field, string $message = ''): self
    {
        $msg = $message ?: "参数{$field}不合法";
        return new self($msg, 400, ['field' => $field]);
    }
    
    /**
     * 操作过于频繁异常
     */
    public static function tooManyRequests(int $retryAfter = 60): self
    {
        return new self(
            '操作过于频繁，请稍后再试',
            429,
            ['retry_after' => $retryAfter]
        );
    }
    
    /**
     * 无权限操作异常
     */
    public static function unauthorized(string $message = '无权限执行此操作'): self
    {
        return new self($message, 403);
    }
}