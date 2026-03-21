<?php

namespace app\model;

use think\Model;

class FeedbackLog extends Model
{
    protected $name = 'feedback_log';
    protected $pk = 'id';
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = false;
    
    /**
     * 关联反馈
     */
    public function feedback()
    {
        return $this->belongsTo(Feedback::class, 'feedback_id', 'id');
    }
    
    /**
     * 关联管理员
     */
    public function admin()
    {
        return $this->belongsTo(AdminUser::class, 'admin_id', 'id');
    }
    
    /**
     * 获取操作类型文本
     */
    public function getActionTextAttr($value, $data)
    {
        $map = [
            'assign' => '分配',
            'reply' => '回复',
            'status' => '状态更新',
            'note' => '备注',
        ];
        return $map[$data['action']] ?? $data['action'];
    }
}
