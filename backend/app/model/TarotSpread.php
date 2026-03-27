<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 塔罗牌阵模型
 */
class TarotSpread extends Model
{
    protected $table = 'tc_tarot_spreads';
    
    // 自动写入时间�?
    protected $autoWriteTimestamp = true;
    
    // 字段类型转换
    protected $type = [
        'card_count' => 'integer',
        'is_enabled' => 'integer',
        'is_free' => 'integer',
        'points_required' => 'integer',
    ];
    
    // 牌阵类型
    const TYPE_SINGLE = 'single';
    const TYPE_THREE = 'three';
    const TYPE_CELTIC = 'celtic';
    const TYPE_LOVE = 'love';
    const TYPE_CAREER = 'career';
    
    const SPREAD_TYPES = [
        self::TYPE_SINGLE => '单张�?,
        self::TYPE_THREE => '三张�?,
        self::TYPE_CELTIC => '凯尔特十�?,
        self::TYPE_LOVE => '爱情牌阵',
        self::TYPE_CAREER => '事业牌阵',
    ];
    
    /**
     * 获取启用的牌阵列�?
     */
    public static function getEnabledList(): array
    {
        return self::where('is_enabled', 1)
            ->order('sort_order', 'asc')
            ->select()
            ->toArray();
    }
    
    /**
     * 获取免费牌阵
     */
    public static function getFreeSpreads(): array
    {
        return self::where('is_enabled', 1)
            ->where('is_free', 1)
            ->order('sort_order', 'asc')
            ->select()
            ->toArray();
    }
    
    /**
     * 获取类型名称
     */
    public function getTypeNameAttr($value, $data): string
    {
        return self::SPREAD_TYPES[$data['type']] ?? '其他';
    }
    
    /**
     * 获取位置详情（JSON解码�?
     */
    public function getPositionsAttr($value): array
    {
        return $value ? json_decode($value, true) : [];
    }
}