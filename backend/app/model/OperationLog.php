<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 操作日志模型
 */
class OperationLog extends Model
{
    protected $table = 'operation_logs';
    
    protected $autoWriteTimestamp = false;
    
    protected $createTime = 'created_at';
    protected $updateTime = false;
    
    protected $type = [
        'id' => 'integer',
        'admin_id' => 'integer'
    ];
    
    /**
     * 获取日志列表
     */
    public static function getList($params = [])
    {
        $query = self::order('created_at', 'desc');
        
        if (!empty($params['action'])) {
            $query->where('action', $params['action']);
        }
        
        if (!empty($params['admin_id'])) {
            $query->where('admin_id', $params['admin_id']);
        }
        
        if (!empty($params['keyword'])) {
            $query->where('description', 'like', "%{$params['keyword']}%");
        }
        
        if (!empty($params['start_time'])) {
            $query->where('created_at', '>=', $params['start_time']);
        }
        
        if (!empty($params['end_time'])) {
            $query->where('created_at', '<=', $params['end_time']);
        }
        
        return $query;
    }
    
    /**
     * 记录操作日志
     */
    public static function record($action, $description, $adminId = 0)
    {
        return self::create([
            'action' => $action,
            'description' => $description,
            'admin_id' => $adminId,
            'ip' => request()->ip(),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }
}
