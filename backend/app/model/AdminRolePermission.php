<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 角色权限关联模型
 */
class AdminRolePermission extends Model
{
    // 表名
    protected $name = 'tc_admin_role_permission';
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    
    // 创建时间字段
    protected $createTime = 'created_at';
    
    // 更新时间字段
    protected $updateTime = false;
}
