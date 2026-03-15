<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 六爻占卜记录模型
 */
class LiuyaoRecord extends Model
{
    // 设置表名
    protected $name = 'liuyao_records';
    
    // 设置主键
    protected $pk = 'id';
    
    // 自动时间戳
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = false;
    
    // 字段类型
    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'consumed_points' => 'integer',
        'is_ai_analysis' => 'boolean',
    ];
    
    // 隐藏字段
    protected $hidden = [];
    
    /**
     * 获取用户占卜次数
     */
    public static function getUserCount(int $userId): int
    {
        return self::where('user_id', $userId)->count();
    }
    
    /**
     * 获取用户的最新记录
     */
    public static function getUserLatest(int $userId, int $limit = 10): array
    {
        return self::where('user_id', $userId)
            ->order('created_at', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }
}
