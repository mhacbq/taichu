<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 管理员操作日志模型
 */
class AdminLog extends Model
{
    // 表名
    protected $name = 'tc_admin_log';
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    
    // 创建时间字段
    protected $createTime = 'created_at';
    
    // 更新时间字段
    protected $updateTime = false;
    
    // JSON字段
    protected $json = ['before_data', 'after_data'];
    
    // JSON数据返回数组
    protected $jsonAssoc = true;
    
    /**
     * 记录操作日志
     */
    public static function record(array $data): self
    {
        return self::create([
            'admin_id' => $data['admin_id'] ?? 0,
            'admin_name' => $data['admin_name'] ?? '',
            'action' => $data['action'] ?? '',
            'module' => $data['module'] ?? '',
            'target_id' => $data['target_id'] ?? 0,
            'target_type' => $data['target_type'] ?? '',
            'detail' => $data['detail'] ?? '',
            'before_data' => $data['before_data'] ?? null,
            'after_data' => $data['after_data'] ?? null,
            'ip' => $data['ip'] ?? '',
            'user_agent' => $data['user_agent'] ?? '',
            'request_url' => $data['request_url'] ?? '',
            'request_method' => $data['request_method'] ?? '',
            'status' => $data['status'] ?? 1,
            'error_msg' => $data['error_msg'] ?? '',
        ]);
    }
    
    /**
     * 获取操作类型列表
     */
    public static function getActionTypes(): array
    {
        return [
            'login' => '登录',
            'logout' => '登出',
            'create' => '创建',
            'update' => '更新',
            'delete' => '删除',
            'view' => '查看',
            'export' => '导出',
            'import' => '导入',
            'adjust_points' => '调整积分',
            'update_config' => '更新配置',
            'other' => '其他',
        ];
    }
    
    /**
     * 获取日志列表（带筛选）
     */
    public static function getLogList(array $params = [], int $page = 1, int $perPage = 20): array
    {
        $query = self::order('id', 'desc');
        
        // 管理员筛选
        if (!empty($params['admin_id'])) {
            $query->where('admin_id', $params['admin_id']);
        }
        
        // 操作类型筛选
        if (!empty($params['action'])) {
            $query->where('action', $params['action']);
        }
        
        // 模块筛选
        if (!empty($params['module'])) {
            $query->where('module', $params['module']);
        }
        
        // 状态筛选
        if (isset($params['status']) && $params['status'] !== '') {
            $query->where('status', $params['status']);
        }
        
        // 时间范围筛选
        if (!empty($params['start_time'])) {
            $query->where('created_at', '>=', $params['start_time']);
        }
        if (!empty($params['end_time'])) {
            $query->where('created_at', '<=', $params['end_time']);
        }
        
        // 关键词搜索
        if (!empty($params['keyword'])) {
            $keyword = $params['keyword'];
            $query->where(function($q) use ($keyword) {
                $q->where('admin_name', 'like', "%{$keyword}%")
                  ->whereOr('detail', 'like', "%{$keyword}%");
            });
        }
        
        $total = $query->count();
        $list = $query->page($page, $perPage)->select();
        
        return [
            'total' => $total,
            'list' => $list,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage),
        ];
    }
    
    /**
     * 获取管理员操作统计
     */
    public static function getAdminStats(int $adminId, int $days = 30): array
    {
        $startTime = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        // 操作次数统计
        $totalCount = self::where('admin_id', $adminId)
            ->where('created_at', '>=', $startTime)
            ->count();
        
        // 成功/失败统计
        $successCount = self::where('admin_id', $adminId)
            ->where('status', 1)
            ->where('created_at', '>=', $startTime)
            ->count();
        
        // 操作类型分布
        $actionStats = self::where('admin_id', $adminId)
            ->where('created_at', '>=', $startTime)
            ->field('action, COUNT(*) as count')
            ->group('action')
            ->select();
        
        return [
            'total_count' => $totalCount,
            'success_count' => $successCount,
            'fail_count' => $totalCount - $successCount,
            'action_stats' => $actionStats,
        ];
    }
    
    /**
     * 清理旧日志
     */
    public static function cleanOldLogs(int $keepDays = 90): int
    {
        $cutoffTime = date('Y-m-d H:i:s', strtotime("-{$keepDays} days"));
        return self::where('created_at', '<', $cutoffTime)->delete();
    }
}
