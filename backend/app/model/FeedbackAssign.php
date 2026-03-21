<?php

namespace app\model;

use think\Model;

class FeedbackAssign extends Model
{
    protected $name = 'feedback_assign';
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
     * 关联分配人
     */
    public function assignedBy()
    {
        return $this->belongsTo(AdminUser::class, 'assigned_by', 'id');
    }
    
    /**
     * 关联被分配人
     */
    public function assignedTo()
    {
        return $this->belongsTo(AdminUser::class, 'assigned_to', 'id');
    }
}
