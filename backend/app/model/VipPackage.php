<?php
namespace app\model;

use think\Model;

class VipPackage extends Model
{
    protected $table = 'tc_vip_package';
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [
        'price' => 'float',
        'original_price' => 'float',
        'points' => 'integer',
        'duration' => 'integer',
        'is_hot' => 'integer',
        'is_recommend' => 'integer',
        'status' => 'integer',
        'is_deleted' => 'integer',
        'sort' => 'integer'
    ];

    /**
     * 获取生效的套餐
     */
    public static function getActivePackages()
    {
        return self::where('status', 1)
            ->where('is_deleted', 0)
            ->order('sort', 'asc')
            ->order('id', 'desc')
            ->select();
    }

    /**
     * 获取热门套餐
     */
    public static function getHotPackages($limit = 3)
    {
        return self::where('status', 1)
            ->where('is_deleted', 0)
            ->where('is_hot', 1)
            ->order('sort', 'asc')
            ->limit($limit)
            ->select();
    }

    /**
     * 获取推荐套餐
     */
    public static function getRecommendedPackage()
    {
        return self::where('status', 1)
            ->where('is_deleted', 0)
            ->where('is_recommend', 1)
            ->find();
    }
}
