<?php
declare(strict_types=1);

namespace app;

use app\model\AdminLog;
use app\service\AdminAuthService;
use think\App;
use think\exception\ValidateException;
use think\facade\Log;
use think\Validate;



/**
 * 控制器基础类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 构造方法
     * @access public
     * @param App $app 应用对象
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $this->app->request;

        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {
    }

    /**
     * 验证数据
     * @access protected
     * @param array $data 数据
     * @param string|array $validate 验证器名或者验证规则数组
     * @param array $message 错误提示信息
     * @param bool $batch 是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, string|array $validate, array $message = [], bool $batch = false): array|string|true
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                [$validate, $scene] = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }

    /**
     * 统一处理分页参数
     */
    protected function normalizePagination(
        mixed $page,
        mixed $pageSize,
        int $defaultPageSize = 20,
        int $maxPageSize = 100
    ): array {
        $page = filter_var($page, FILTER_VALIDATE_INT, ['options' => ['default' => 1]]);
        $pageSize = filter_var($pageSize, FILTER_VALIDATE_INT, ['options' => ['default' => $defaultPageSize]]);

        $page = max(1, (int) $page);
        $pageSize = max(1, min($maxPageSize, (int) $pageSize));

        return [
            'page' => $page,
            'pageSize' => $pageSize,
        ];
    }

    /**
     * 从当前请求中读取分页参数
     */
    protected function getPaginationParams(
        string $pageParam = 'page',
        string $pageSizeParam = 'pageSize',
        int $defaultPageSize = 20,
        int $maxPageSize = 100
    ): array {
        return $this->normalizePagination(
            $this->request->param($pageParam, 1),
            $this->request->param($pageSizeParam, $defaultPageSize),
            $defaultPageSize,
            $maxPageSize
        );
    }

    /**
     * 获取当前后台管理员ID
     */
    protected function getAdminId(): int
    {
        $adminUser = $this->request->adminUser ?? [];
        if (isset($adminUser['id']) && is_numeric($adminUser['id'])) {
            return (int) $adminUser['id'];
        }

        return 0;
    }

    /**
     * 判断当前后台管理员是否拥有指定权限
     */
    protected function hasAdminPermission(string $permissionCode): bool
    {
        return AdminAuthService::checkPermission($this->getAdminId(), $permissionCode);
    }

    /**
     * 兼容旧控制器的权限检查调用
     */
    protected function checkPermission(string $permissionCode): bool
    {
        return $this->hasAdminPermission($permissionCode);
    }

    /**
     * 判断当前后台管理员是否拥有任一权限
     */
    protected function hasAnyAdminPermission(array $permissionCodes): bool
    {
        return AdminAuthService::checkAnyPermission($this->getAdminId(), $permissionCodes);
    }

    /**
     * 判断当前后台管理员是否拥有指定角色
     */
    protected function hasAdminRole(string $roleCode): bool
    {
        $roleCode = strtolower(trim($roleCode));
        if ($roleCode === '') {
            return false;
        }

        $roles = $this->request->adminUser['roles'] ?? [];
        if (!is_array($roles)) {
            $roles = [$roles];
        }

        foreach ($roles as $role) {
            if (strtolower(trim((string) $role)) === $roleCode) {
                return true;
            }
        }

        return false;
    }

    /**
     * 判断当前后台管理员是否拥有任一角色
     */
    protected function hasAnyAdminRole(array $roleCodes): bool
    {
        foreach ($roleCodes as $roleCode) {
            if ($this->hasAdminRole((string) $roleCode)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取当前操作者ID，优先读取后台管理员信息
     */
    protected function getOperatorId(): int
    {
        $adminId = $this->getAdminId();
        if ($adminId > 0) {
            return $adminId;
        }

        return (int) ($this->request->userId ?? 0);
    }


    /**
     * 统一记录后台操作日志，并对上下文做脱敏处理
     */
    protected function logOperation(
        string $action,
        string $module = '',
        array $data = []
    ): void {
        try {
            $adminUser = $this->request->adminUser ?? [];
            AdminLog::record([
                'admin_id' => $this->getAdminId(),
                'admin_name' => (string) ($adminUser['username'] ?? ''),
                'action' => $action,
                'module' => $module,
                'target_id' => (int) ($data['target_id'] ?? 0),
                'target_type' => (string) ($data['target_type'] ?? ''),
                'detail' => (string) ($data['detail'] ?? ''),
                'before_data' => array_key_exists('before_data', $data)
                    ? $this->sanitizeLogContext($data['before_data'])
                    : null,
                'after_data' => array_key_exists('after_data', $data)
                    ? $this->sanitizeLogContext($data['after_data'])
                    : null,
                'ip' => $this->request->ip(),
                'user_agent' => (string) ($this->request->header('User-Agent') ?? ''),
                'request_url' => $this->request->url(true),
                'request_method' => $this->request->method(),
                'status' => (int) ($data['status'] ?? 1),
                'error_msg' => (string) $this->sanitizeLogContext($data['error_msg'] ?? ''),
            ]);
        } catch (\Throwable $e) {
            Log::warning('后台操作日志写入失败', [
                'action' => $action,
                'module' => $module,
                'admin_id' => $this->getAdminId(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 统一返回可安全展示的业务异常，并写入结构化告警日志
     */
    protected function respondBusinessException(
        \Throwable $e,
        string $action,
        string $fallbackMessage = '请求参数无效',
        int $code = 400,
        array $context = []
    ): \think\response\Json {
        $message = trim((string) $e->getMessage());
        if ($message === '') {
            $message = $fallbackMessage;
        }

        $this->logControllerException($action, $e, $context, 'warning');

        return $this->error($message, $code);
    }

    /**
     * 统一返回系统异常，前端只暴露通用文案，详细信息写入脱敏日志
     */
    protected function respondSystemException(
        string $action,
        \Throwable $e,
        string $userMessage = '操作失败，请稍后重试',
        array $context = [],
        int $code = 500
    ): \think\response\Json {
        $this->logControllerException($action, $e, $context, 'error');

        return $this->error($userMessage, $code);
    }

    /**
     * 统一记录控制器异常日志
     */
    protected function logControllerException(
        string $action,
        \Throwable $e,
        array $context = [],
        string $level = 'error'
    ): void {
        $payload = [
            'action' => $action,
            'admin_id' => $this->getAdminId(),
            'operator_id' => $this->getOperatorId(),
            'controller' => (string) $this->request->controller(),
            'route' => (string) $this->request->pathinfo(),
            'method' => (string) $this->request->method(),
            'context' => $this->sanitizeLogContext($context),
            'error' => $e->getMessage(),
            'exception' => get_class($e),
        ];

        switch (strtolower($level)) {
            case 'info':
                Log::info('控制器异常', $payload);
                break;
            case 'warning':
                Log::warning('控制器异常', $payload);
                break;
            default:
                Log::error('控制器异常', $payload);
                break;
        }
    }

    /**
     * 递归脱敏日志上下文
     */
    protected function sanitizeLogContext(mixed $value, ?string $field = null): mixed
    {
        if ($field !== null) {
            $fieldType = $this->getSensitiveFieldType($field);
            if ($fieldType === 'masked') {
                return '***';
            }
            if ($fieldType === 'phone' && is_scalar($value)) {
                return $this->maskPhoneNumber((string) $value);
            }
            if ($fieldType === 'email' && is_scalar($value)) {
                return $this->maskEmail((string) $value);
            }
        }

        if (is_array($value)) {
            $sanitized = [];
            foreach ($value as $key => $item) {
                $sanitized[$key] = $this->sanitizeLogContext($item, is_string($key) ? $key : null);
            }
            return $sanitized;
        }

        if (is_object($value)) {
            return $this->sanitizeLogContext(get_object_vars($value), $field);
        }

        if (is_string($value) && mb_strlen($value) > 500) {
            return mb_substr($value, 0, 500) . '...';
        }

        return $value;
    }

    /**
     * 根据字段名判断日志脱敏策略
     */
    protected function getSensitiveFieldType(string $field): string
    {
        $normalized = strtolower($field);
        $compact = preg_replace('/[^a-z0-9]/', '', $normalized) ?: '';

        if ($compact === '') {
            return 'none';
        }

        if (in_array($compact, ['phone', 'mobile', 'tel', 'telephone', 'phonenumber', 'mobilephone'], true)) {
            return 'phone';
        }

        if (str_contains($compact, 'email')) {
            return 'email';
        }

        if (in_array($compact, ['password', 'pwd', 'token', 'secret', 'authorization', 'credential'], true)) {
            return 'masked';
        }

        foreach (['secret', 'token', 'apikey', 'appkey', 'privatekey', 'publickey', 'accesskey', 'secretid', 'secretkey', 'cert', 'pem', 'signature'] as $keyword) {
            if (str_contains($compact, $keyword)) {
                return 'masked';
            }
        }

        return 'none';
    }

    /**
     * 脱敏手机号
     */
    protected function maskPhoneNumber(string $phone): string
    {
        $phone = trim($phone);
        if ($phone === '') {
            return '';
        }

        if (strlen($phone) < 7) {
            return str_repeat('*', strlen($phone));
        }

        return substr($phone, 0, 3) . '****' . substr($phone, -4);
    }

    /**
     * 脱敏邮箱
     */
    protected function maskEmail(string $email): string
    {
        $email = trim($email);
        if ($email === '' || !str_contains($email, '@')) {
            return $email;
        }

        [$localPart, $domain] = explode('@', $email, 2);
        $visibleLength = min(2, strlen($localPart));
        $maskedLength = max(strlen($localPart) - $visibleLength, 1);

        return substr($localPart, 0, $visibleLength) . str_repeat('*', $maskedLength) . '@' . $domain;
    }

    /**
     * 成功响应


     * @param mixed $data
     * @param string $message
     * @return \think\response\Json
     */
    protected function success(mixed $data = null, string $message = 'success'): \think\response\Json
    {
        return json([
            'code' => 0,
            'message' => $message,
            'data' => $data,
        ]);
    }

    /**
     * 失败响应
     * @param string $message
     * @param int $code
     * @param mixed $data
     * @return \think\response\Json
     */
    protected function error(string $message = 'error', int $code = 400, mixed $data = null): \think\response\Json
    {
        return json([
            'code' => $code,
            'message' => $message,
            'data' => $data,
        ]);
    }
}
