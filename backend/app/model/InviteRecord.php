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
    protected $table = 'tc_invite_record';

    
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
        $totalPoints = self::where('inviter_id', $userId)->sum('reward_points');
        
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
        
        // 创建新邀请码（邀请码记录属于邀请人，invitee_id 留 NULL，由实际邀请时填写）
        $code = self::generateInviteCode($userId);
        
        self::create([
            'inviter_id'  => $userId,
            'invite_code' => $code,
            'invitee_id'  => null,
            'status'      => 1,
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
    
    /**
     * 获取邀请排行榜
     * 
     * @param int $limit 返回数量
     * @param string $period 周期：all(全部), month(本月), week(本周)
     * @return array 排行榜数据
     */
    public static function getLeaderboard(int $limit = 20, string $period = 'all'): array
    {
        // 严格验证period参数，防止SQL注入
        $allowedPeriods = ['all', 'month', 'week'];
        if (!in_array($period, $allowedPeriods, true)) {
            $period = 'all';
        }
        
        // 限制limit范围，防止数据泄露
        $limit = max(1, min($limit, 100));
        
        $query = self::alias('ir')
            ->field([
                'ir.inviter_id',
                'u.nickname',
                'u.avatar',
                'COUNT(ir.id) as invite_count',
                'SUM(ir.reward_points) as total_points',
                'MAX(ir.created_at) as last_invite_time'
            ])
            ->join('tc_user u', 'ir.inviter_id = u.id')
            ->where('ir.status', 1);
        
        // 根据周期筛选 - 使用安全的查询构建器方法
        switch ($period) {
            case 'month':
                $query->whereMonth('ir.created_at', date('m'));
                break;
            case 'week':
                // 使用安全的日期范围查询替代whereWeek
                $startOfWeek = date('Y-m-d 00:00:00', strtotime('monday this week'));
                $endOfWeek = date('Y-m-d 23:59:59', strtotime('sunday this week'));
                $query->whereBetween('ir.created_at', [$startOfWeek, $endOfWeek]);
                break;
            case 'all':
            default:
                // 不限制时间
                break;
        }
        
        $leaderboard = $query->group('ir.inviter_id')
            ->order('invite_count', 'desc')
            ->order('total_points', 'desc')
            ->limit($limit)
            ->select();
        
        $result = [];
        $rank = 1;
        
        foreach ($leaderboard as $item) {
            // 对昵称进行XSS过滤
            $nickname = $item['nickname'] ?? '神秘用户';
            $nickname = htmlspecialchars($nickname, ENT_QUOTES, 'UTF-8');
            
            $result[] = [
                'rank' => $rank,
                'user_id' => $item['inviter_id'],
                'nickname' => $nickname,
                'avatar' => $item['avatar'] ?? '',
                'invite_count' => (int)$item['invite_count'],
                'total_points' => (int)$item['total_points'],
                'last_invite_time' => $item['last_invite_time'],
            ];
            $rank++;
        }
        
        return $result;
    }
    
    /**
     * 获取用户的排名信息
     */
    public static function getUserRank(int $userId, string $period = 'all'): ?array
    {
        // 先获取用户的基本统计
        $userStats = self::getInviteStats($userId);
        
        if ($userStats['invite_count'] === 0) {
            return null;
        }
        
        // 计算排名
        $query = self::where('status', 1)
            ->group('inviter_id')
            ->having('COUNT(id) > ' . $userStats['invite_count']);
        
        switch ($period) {
            case 'month':
                $query->whereMonth('created_at', date('m'));
                break;
            case 'week':
                $query->whereWeek('created_at');
                break;
        }
        
        $rank = $query->count() + 1;
        
        return [
            'rank' => $rank,
            'invite_count' => $userStats['invite_count'],
            'total_points' => $userStats['total_points'],
        ];
    }
}
