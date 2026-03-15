<?php
declare(strict_types=1);

namespace app\service;

use app\model\AdminRole;
use app\model\AdminPermission;
use app\model\AdminUserRole;
use app\model\AdminRolePermission;
use think\facade\Cache;

/**
 * 管理员权限服务
 */
class AdminAuthService
{
    /**
     * 缓存键前缀
     */
    protected static string $cachePrefix = 'admin:permissions:';
    
    /**
     * 缓存时间（秒）
     */
    protected static int $cacheTTL = 3600; // 1小时
    
    /**
     * 检查管理员是否有指定权限
     */
    public static function checkPermission(int $adminId, string $permissionCode): bool
    {
        // 获取管理员的所有权限代码
        $permissions = self::getAdminPermissions($adminId);
        
        // 超级管理员拥有所有权限
        if (in_array('*', $permissions)) {
            return true;
        }
        
        return in_array($permissionCode, $permissions);
    }
    
    /**
     * 检查管理员是否拥有多个权限中的任意一个
     */
    public static function checkAnyPermission(int $adminId, array $permissionCodes): bool
    {
        foreach ($permissionCodes as $code) {
            if (self::checkPermission($adminId, $code)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * 检查管理员是否拥有所有指定权限
     */
    public static function checkAllPermissions(int $adminId, array $permissionCodes): bool
    {
        foreach ($permissionCodes as $code) {
            if (!self::checkPermission($adminId, $code)) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * 获取管理员的所有权限
     */
    public static function getAdminPermissions(int $adminId): array
    {
        $cacheKey = self::$cachePrefix . $adminId;
        
        // 尝试从缓存获取
        $permissions = Cache::get($cacheKey);
        if ($permissions !== null) {
            return $permissions;
        }
        
        // 获取管理员的所有角色
        $roleIds = AdminUserRole::where('admin_id', $adminId)->column('role_id');
        
        if (empty($roleIds)) {
            return [];
        }
        
        // 检查是否有超级管理员角色
        $hasSuperRole = AdminRole::whereIn('id', $roleIds)
            ->where('is_super', 1)
            ->find();
        
        if ($hasSuperRole) {
            // 超级管理员拥有所有权限
            $permissions = ['*'];
        } else {
            // 获取角色对应的所有权限
            $permissionIds = AdminRolePermission::whereIn('role_id', $roleIds)
                ->column('permission_id');
            
            if (empty($permissionIds)) {
                $permissions = [];
            } else {
                $permissions = AdminPermission::whereIn('id', $permissionIds)
                    ->column('code');
            }
        }
        
        // 写入缓存
        Cache::set($cacheKey, $permissions, self::$cacheTTL);
        
        return $permissions;
    }
    
    /**
     * 清除管理员权限缓存
     */
    public static function clearPermissionCache(int $adminId): void
    {
        $cacheKey = self::$cachePrefix . $adminId;
        Cache::delete($cacheKey);
    }
    
    /**
     * 获取所有权限列表
     */
    public static function getAllPermissions(): array
    {
        return AdminPermission::order('module', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }
    
    /**
     * 获取所有角色列表
     */
    public static function getAllRoles(): array
    {
        return AdminRole::order('id', 'asc')
            ->select()
            ->toArray();
    }
    
    /**
     * 获取角色详情（包含权限）
     */
    public static function getRoleDetail(int $roleId): ?array
    {
        $role = AdminRole::find($roleId);
        if (!$role) {
            return null;
        }
        
        $roleData = $role->toArray();
        
        // 获取角色权限
        $permissionIds = AdminRolePermission::where('role_id', $roleId)
            ->column('permission_id');
        
        $roleData['permission_ids'] = $permissionIds;
        
        if (!empty($permissionIds)) {
            $roleData['permissions'] = AdminPermission::whereIn('id', $permissionIds)
                ->column('code');
        } else {
            $roleData['permissions'] = [];
        }
        
        return $roleData;
    }
    
    /**
     * 为管理员分配角色
     */
    public static function assignRole(int $adminId, int $roleId): bool
    {
        try {
            // 检查是否已存在
            $exists = AdminUserRole::where('admin_id', $adminId)
                ->where('role_id', $roleId)
                ->find();
            
            if ($exists) {
                return true;
            }
            
            AdminUserRole::create([
                'admin_id' => $adminId,
                'role_id' => $roleId,
            ]);
            
            // 清除缓存
            self::clearPermissionCache($adminId);
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * 移除管理员角色
     */
    public static function removeRole(int $adminId, int $roleId): bool
    {
        try {
            AdminUserRole::where('admin_id', $adminId)
                ->where('role_id', $roleId)
                ->delete();
            
            // 清除缓存
            self::clearPermissionCache($adminId);
            
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
