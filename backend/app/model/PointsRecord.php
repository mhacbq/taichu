<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

class PointsRecord extends Model
{
    protected $table = 'tc_points_record';
    protected $pk = 'id';
    
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = false;
    
    protected $schema = [
        'id' => 'int',
        'user_id' => 'int',
        'action' => 'string',
        'points' => 'int',
        'type' => 'string',
        'related_id' => 'int',
        'remark' => 'string',
        'created_at' => 'datetime',
    ];
    
    /**
     * 获取用户积分记录
     */
    public static function getUserRecords(int $userId, int $limit = 50): array
    {
        return self::where('user_id', $userId)
            ->order('created_at', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }
    
    /**
     * 记录积分变动
     */
    public static function record(int $userId, string $action, int $points, string $type = '', int $relatedId = 0, string $remark = ''): self
    {
        return self::create([
            'user_id' => $userId,
            'action' => $action,
            'points' => $points,
            'type' => $type,
            'related_id' => $relatedId,
            'remark' => $remark,
        ]);
    }
}
