<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

class BaziRecord extends Model
{
    protected $table = 'tc_bazi_record';
    protected $pk = 'id';
    
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = false;
    
    protected $schema = [
        'id' => 'int',
        'user_id' => 'int',
        'birth_date' => 'string',
        'gender' => 'string',
        'location' => 'string',
        'year_gan' => 'string',
        'year_zhi' => 'string',
        'month_gan' => 'string',
        'month_zhi' => 'string',
        'day_gan' => 'string',
        'day_zhi' => 'string',
        'hour_gan' => 'string',
        'hour_zhi' => 'string',
        'analysis' => 'string',
        'created_at' => 'datetime',
    ];
    
    /**
     * 获取用户的排盘历史
     */
    public static function getUserHistory(int $userId, int $limit = 20): array
    {
        return self::where('user_id', $userId)
            ->order('created_at', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }
}
