<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 塔罗牌模型
 */
class TarotCard extends Model
{
    protected $table = 'tarot_cards';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    
    // 字段类型转换
    protected $type = [
        'is_major' => 'integer',
        'is_enabled' => 'integer',
    ];
    
    // 正位/逆位含义类型
    const TYPE_GENERAL = 'general';
    const TYPE_LOVE = 'love';
    const TYPE_CAREER = 'career';
    const TYPE_HEALTH = 'health';
    const TYPE_WEALTH = 'wealth';
    
    const MEANING_TYPES = [
        self::TYPE_GENERAL => '综合',
        self::TYPE_LOVE => '感情',
        self::TYPE_CAREER => '事业',
        self::TYPE_HEALTH => '健康',
        self::TYPE_WEALTH => '财运',
    ];
    
    /**
     * 获取大阿尔卡那牌
     */
    public static function getMajorArcana(): array
    {
        return self::where('is_major', 1)
            ->where('is_enabled', 1)
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }
    
    /**
     * 获取所有启用的牌
     */
    public static function getAllEnabled(): array
    {
        return self::where('is_enabled', 1)
            ->order('is_major', 'desc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }
    
    /**
     * 随机抽取一张牌
     */
    public static function drawRandom(): ?self
    {
        return self::where('is_enabled', 1)
            ->orderRaw('RAND()')
            ->find();
    }
}