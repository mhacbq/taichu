<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 管理员角色关联模型
 */
class AdminUserRole extends Model
{
    // 表名
    protected $name = 'tc_admin_user_role';
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    
    // 创建时间字段
    protected $createTime = 'created_at';
    
    // 更新时间字段
    protected $updateTime = false;
}
