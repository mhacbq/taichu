<?php
declare(strict_types=1);

namespace app\model;

use think\Model;
use think\facade\Cache;

/**
 * 邀请记录模型
 */
class InviteRecord extends Model
{
    // 表名
    protected $name = 'tc_invite_record';
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    
    // 创建时间字段
    protected $createTime = 'created_at';
    
    // 更新时间字段
    protected $updateTime = false;
    
    /**
     * 生成安全邀请码
     * 使用字母+数字组合，避免被暴力枚举
     */
    public static function generateInviteCode(int $userId): string
    {
        // 使用用户ID + 随机字符串 + 时间戳生成唯一邀请码
        $baseString = $userId . uniqid() . mt_rand(1000, 9999);
        
        // 生成8位邀请码（字母+数字，去除易混淆字符）
        $code = substr(strtoupper(base_convert(md5($baseString), 16, 36)), 0, 8);
        
        // 替换易混淆字符
        $code = strtr($code, '0oOiIl', '9qQzZx');
        
        // 如果生成重复，递归重新生成
        if (self::where('invite_code', $code)->find()) {
            return self::generateInviteCode($userId);
        }
        
        return $code;
    }
    
    /**
     * 根据邀请码获取邀请人ID
     */
    public static function getInviterByCode(string $inviteCode): ?int
    {
        // 验证邀请码格式（8位字母数字）
        if (!preg_match('/^[A-Z0-9]{8}$/', $inviteCode)) {
            return null;
        }
        
        $record = self::where('invite_code', $inviteCode)
            ->where('status', 1)
            ->find();
        
        return $record ? $record['inviter_id'] : null;
    }
    
    /**
     * 获取用户的邀请统计
     */
    public static function getInviteStats(int $userId): array
    {
        $inviteCount = self::where('inviter_id', $userId)->count();
        $totalPoints = self::where('inviter_id', $userId)->sum('points_reward');
        
        return [
            'invite_count' => (int)$inviteCount,
            'total_points' => (int)$totalPoints,
        ];
    }
    
    /**
     * 获取用户的邀请码
     * 如果没有则创建
     */
    public static function getOrCreateInviteCode(int $userId): string
    {
        $record = self::where('inviter_id', $userId)
            ->where('status', 1)
            ->find();
        
        if ($record) {
            return $record['invite_code'];
        }
        
        // 创建新邀请码
        $code = self::generateInviteCode($userId);
        
        self::create([
            'inviter_id' => $userId,
            'invite_code' => $code,
            'status' => 1,
        ]);
        
        return $code;
    }
    
    /**
     * 检查验证码失败次数
     * 防止暴力破解
     */
    public static function checkVerifyFailLimit(string $phone, string $type): bool
    {
        $key = "sms_verify_fail:{$phone}:{$type}";
        $failCount = Cache::get($key, 0);
        
        // 限制5次失败
        if ($failCount >= 5) {
            return false;
        }
        
        return true;
    }
    
    /**
     * 记录验证码验证失败
     */
    public static function recordVerifyFail(string $phone, string $type): void
    {
        $key = "sms_verify_fail:{$phone}:{$type}";
        $failCount = Cache::get($key, 0);
        
        Cache::set($key, $failCount + 1, 3600); // 1小时后重置
    }
    
    /**
     * 清除验证码失败记录
     */
    public static function clearVerifyFail(string $phone, string $type): void
    {
        $key = "sms_verify_fail:{$phone}:{$type}";
        Cache::delete($key);
    }
}
