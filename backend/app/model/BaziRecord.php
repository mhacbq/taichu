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
        'is_first' => 'int',
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
    
    /**
     * 获取用户的排盘历史（分页）
     */
    public static function getUserHistoryPaged(int $userId, int $page = 1, int $pageSize = 10): array
    {
        $query = self::where('user_id', $userId)
            ->order('created_at', 'desc');
        
        $total = $query->count();
        $list = $query->page($page, $pageSize)->select()->toArray();
        
        return [
            'list' => $list,
            'pagination' => [
                'page' => $page,
                'page_size' => $pageSize,
                'total' => $total,
                'total_pages' => (int)ceil($total / $pageSize),
            ],
        ];
    }
}
