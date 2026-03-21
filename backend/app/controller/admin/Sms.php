<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\SmsConfig;
use app\service\SmsService;

/**
 * 后台短信管理控制器
 */
class Sms extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];
    
    /**
     * 当前管理员ID
     */
    protected int $adminId = 0;
    
    /**
     * 初始化
     */
    public function initialize()
    {
        parent::initialize();
        
        // 从JWT token中获取管理员信息
        $adminUser = $this->request->adminUser ?? [];
        $this->adminId = $adminUser['id'] ?? 0;
    }
    
    /**
     * 检查权限
     */
    protected function hasAdminPermission(string $permissionCode): bool
    {
        if ($this->adminId === 0) {
            return false;
        }
        
        // 如果是超级管理员，直接返回true
        $adminUser = $this->request->adminUser ?? [];
        $roles = $adminUser['roles'] ?? [];
        if (in_array('admin', $roles)) {
            return true;
        }
        
        // 这里可以扩展更复杂的权限检查逻辑
        // 暂时简化处理：如果有操作员角色，允许查看和操作
        if (in_array('operator', $roles)) {
            return in_array($permissionCode, ['sms_view', 'sms_manage']);
        }
        
        return false;
    }

    /**
     * 要求短信管理权限
     */
    protected function requireSmsManagePermission(string $message = '无权限')
    {
        if (!$this->hasAdminPermission('sms_manage')) {
            return $this->error($message, 403);
        }
        return null;
    }
    
    /**
     * 获取短信配置
     */
    public function getConfig()
    {
        $config = SmsConfig::getSafeConfig();
        
        if (!$config) {
            return $this->success([
                'provider' => 'tencent',
                'secret_id' => '',
                'secret_key' => '',
                'sdk_app_id' => '',
                'sign_name' => '',
                'template_code' => '',
                'template_register' => '',
                'template_reset' => '',
                'is_enabled' => false,
                'secret_id_masked' => false,
                'secret_key_masked' => false,
            ]);
        }
        
        return $this->success($config);
    }
    
    /**
     * 保存短信配置
     */
    public function saveConfig()
    {
        $data = $this->request->post();
        
        // 验证必要参数
        if (empty($data['secret_id'])) {
            return $this->error('SecretId不能为空');
        }
        if (empty($data['sdk_app_id'])) {
            return $this->error('SDK AppId不能为空');
        }
        if (empty($data['sign_name'])) {
            return $this->error('短信签名不能为空');
        }
        if (empty($data['template_code'])) {
            return $this->error('验证码模板ID不能为空');
        }
        
        $saveData = [
            'provider' => 'tencent',
            'secret_id' => $data['secret_id'],
            'sdk_app_id' => $data['sdk_app_id'],
            'sign_name' => $data['sign_name'],
            'template_code' => $data['template_code'],
            'template_register' => $data['template_register'] ?? '',
            'template_reset' => $data['template_reset'] ?? '',
            'is_enabled' => $data['is_enabled'] ?? true,
        ];
        
        // 只有提供了新密钥时才更新
        if (!empty($data['secret_key']) && strpos($data['secret_key'], '***') === false) {
            $saveData['secret_key'] = $data['secret_key'];
        }
        
        if (SmsConfig::saveConfig($saveData)) {
            return $this->success(null, '配置保存成功');
        }
        
        return $this->error('配置保存失败');
    }
    
    /**
     * 测试短信发送
     */
    public function testSend()
    {
        if (!$this->hasAdminPermission('sms_manage')) {
            return $this->error('无权限发送测试短信', 403);
        }

        $data = $this->request->post();
        
        if (empty($data['phone'])) {
            return $this->error('请输入测试手机号');
        }
        
        $phone = $data['phone'];
        
        // 验证手机号格式
        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
            return $this->error('手机号格式不正确');
        }
        
        $result = SmsService::sendVerifyCode($phone, 'register', $this->request->ip());
        
        if ($result['success']) {
            return $this->success(null, '测试短信已发送');
        }
        
        return $this->error($result['message']);
    }
    
    /**
     * 获取发送统计
     */
    public function getStats()
    {
        $params = $this->request->get();
        $startDate = $params['start_date'] ?? date('Y-m-01');
        $endDate = $params['end_date'] ?? date('Y-m-d');
        
        // 总发送次数
        $totalCount = \app\model\SmsCode::where('created_at', '>=', $startDate . ' 00:00:00')
            ->where('created_at', '<=', $endDate . ' 23:59:59')
            ->count();
        
        // 成功次数（5分钟内被使用的）
        $successCount = \app\model\SmsCode::where('created_at', '>=', $startDate . ' 00:00:00')
            ->where('created_at', '<=', $endDate . ' 23:59:59')
            ->where('is_used', 1)
            ->count();
        
        // 今日发送次数
        $todayCount = \app\model\SmsCode::where('created_at', '>=', date('Y-m-d 00:00:00'))->count();
        
        // 独立手机号数
        $uniquePhones = \app\model\SmsCode::where('created_at', '>=', $startDate . ' 00:00:00')
            ->where('created_at', '<=', $endDate . ' 23:59:59')
            ->distinct(false)
            ->column('phone')
            ->count();
        
        return $this->success([
            'total_count' => $totalCount,
            'success_count' => $successCount,
            'today_count' => $todayCount,
            'unique_phones' => $uniquePhones,
            'success_rate' => $totalCount > 0 ? round($successCount / $totalCount * 100, 2) : 0,
        ]);
    }
    
    /**
     * 获取发送记录
     */
    public function getRecords()
    {
        if (!$this->hasAdminPermission('sms_view')) {
            return $this->error('无权限查看短信记录', 403);
        }

        $params = $this->request->get();
        $page = (int) ($params['page'] ?? 1);
        $limit = (int) ($params['limit'] ?? 20);
        $phone = $params['phone'] ?? '';
        $type = $params['type'] ?? '';
        
        $query = \app\model\SmsCode::order('id', 'desc');
        
        if ($phone) {
            // 使用参数绑定防止SQL注入
            $phone = preg_replace('/[%_\\\\]/', '', $phone);
            $query->whereLike('phone', '%' . $phone . '%');
        }
        
        if ($type) {
            $query->where('type', $type);
        }
        
        $total = $query->count();
        $records = $query->page($page, $limit)->select();
        
        return $this->success([
            'list' => $records,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    }
}
