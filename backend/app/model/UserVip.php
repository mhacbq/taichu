<?php
declare(strict_types=1);

namespace app\model;

use think\Model;
use think\facade\Db;

/**
 * 用户VIP会员模型
 */
class UserVip extends Model
{
    protected $name = 'tc_user_vip';
    
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    
    // VIP状态常量
    const STATUS_INACTIVE = 0;  // 未激活
    const STATUS_ACTIVE = 1;    // 生效中
    const STATUS_EXPIRED = 2;   // 已过期
    
    // VIP类型常量
    const TYPE_MONTH = 'month';       // 月卡
    const TYPE_QUARTER = 'quarter';   // 季卡
    const TYPE_YEAR = 'year';         // 年卡
    
    protected $schema = [
        'id' => 'int',
        'user_id' => 'int',
        'vip_type' => 'string',
        'status' => 'int',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    /**
     * 获取用户VIP信息
     */
    public static function getUserVip(int $userId): ?self
    {
        return self::where('user_id', $userId)
            ->where('status', self::STATUS_ACTIVE)
            ->where('end_time', '>', date('Y-m-d H:i:s'))
            ->order('end_time', 'desc')
            ->find();
    }
    
    /**
     * 检查用户是否为VIP
     */
    public static function isVip(int $userId): bool
    {
        return self::where('user_id', $userId)
            ->where('status', self::STATUS_ACTIVE)
            ->where('end_time', '>', date('Y-m-d H:i:s'))
            ->exists();
    }
    
    /**
     * 获取VIP到期时间
     */
    public static function getVipEndTime(int $userId): ?string
    {
        $vip = self::getUserVip($userId);
        return $vip ? $vip->end_time : null;
    }
    
    /**
     * 激活或续费VIP
     */
    public static function activateVip(int $userId, string $vipType, int $durationDays = 30): self
    {
        $existingVip = self::where('user_id', $userId)
            ->where('status', self::STATUS_ACTIVE)
            ->where('end_time', '>', date('Y-m-d H:i:s'))
            ->find();
        
        if ($existingVip) {
            // 续费：延长到期时间
            $existingVip->end_time = date('Y-m-d H:i:s', strtotime($existingVip->end_time . " +{$durationDays} days"));
            $existingVip->vip_type = $vipType;
            $existingVip->save();
            return $existingVip;
        }
        
        // 新开通VIP
        $vip = new self();
        $vip->user_id = $userId;
        $vip->vip_type = $vipType;
        $vip->status = self::STATUS_ACTIVE;
        $vip->start_time = date('Y-m-d H:i:s');
        $vip->end_time = date('Y-m-d H:i:s', strtotime("+{$durationDays} days"));
        $vip->save();
        
        return $vip;
    }
    
    /**
     * 检查并更新过期VIP状态
     */
    public static function checkAndUpdateExpired(): int
    {
        $count = self::where('status', self::STATUS_ACTIVE)
            ->where('end_time', '<=', date('Y-m-d H:i:s'))
            ->update(['status' => self::STATUS_EXPIRED]);
        
        return $count;
    }
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    
    /**
     * 获取VIP类型名称
     */
    public function getVipTypeName(): string
    {
        $names = [
            self::TYPE_MONTH => '月度会员',
            self::TYPE_QUARTER => '季度会员',
            self::TYPE_YEAR => '年度会员',
        ];
        
        return $names[$this->vip_type] ?? '未知类型';
    }
    
    /**
     * 获取剩余天数
     */
    public function getRemainingDays(): int
    {
        $endTime = strtotime($this->end_time);
        $now = time();
        
        if ($endTime <= $now) {
            return 0;
        }
        
        return ceil(($endTime - $now) / 86400);
    }
}
