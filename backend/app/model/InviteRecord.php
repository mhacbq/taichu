<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

class InviteRecord extends Model
{
    protected $table = 'tc_invite_record';
    protected $pk = 'id';
    
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = false;
    
    protected $schema = [
        'id' => 'int',
        'inviter_id' => 'int',
        'invitee_id' => 'int',
        'invite_code' => 'string',
        'points_reward' => 'int',
        'created_at' => 'datetime',
    ];
    
    /**
     * 记录邀请关系
     */
    public static function recordInvite(int $inviterId, int $inviteeId, string $inviteCode, int $pointsReward = 20): bool
    {
        // 检查是否已经被邀请过
        $exists = self::where('invitee_id', $inviteeId)->find();
        if ($exists) {
            return false;
        }
        
        self::create([
            'inviter_id' => $inviterId,
            'invitee_id' => $inviteeId,
            'invite_code' => $inviteCode,
            'points_reward' => $pointsReward,
        ]);
        
        return true;
    }
    
    /**
     * 获取用户的邀请统计
     */
    public static function getInviteStats(int $userId): array
    {
        $count = self::where('inviter_id', $userId)->count();
        $totalPoints = self::where('inviter_id', $userId)->sum('points_reward');
        
        return [
            'invite_count' => $count,
            'total_points' => $totalPoints ?: 0,
        ];
    }
    
    /**
     * 根据邀请码获取邀请人ID
     */
    public static function getInviterByCode(string $inviteCode): ?int
    {
        // 从邀请码解析用户ID (格式: TCXXXXXX)
        if (strpos($inviteCode, 'TC') === 0) {
            $userIdSuffix = substr($inviteCode, 2);
            // 查找用户ID以这个后缀结尾的用户
            $user = User::whereLike('id', '%' . $userIdSuffix)->find();
            if ($user) {
                return $user->id;
            }
        }
        return null;
    }
}
