<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 每日运势模板模型
 */
class DailyFortuneTemplate extends Model
{
    protected $table = 'tc_daily_fortune_templates';
    
    // 自动写入时间�?
    protected $autoWriteTimestamp = true;
    
    // 字段类型转换
    protected $type = [
        'is_enabled' => 'integer',
    ];
    
    // 评分等级
    const LEVEL_EXCELLENT = 5;
    const LEVEL_GOOD = 4;
    const LEVEL_NORMAL = 3;
    const LEVEL_BAD = 2;
    const LEVEL_TERRIBLE = 1;
    
    const LEVEL_NAMES = [
        self::LEVEL_EXCELLENT => '极佳',
        self::LEVEL_GOOD => '良好',
        self::LEVEL_NORMAL => '一�?,
        self::LEVEL_BAD => '较差',
        self::LEVEL_TERRIBLE => '糟糕',
    ];
    
    const LEVEL_EMOJIS = [
        self::LEVEL_EXCELLENT => '⭐⭐⭐⭐�?,
        self::LEVEL_GOOD => '⭐⭐⭐⭐',
        self::LEVEL_NORMAL => '⭐⭐�?,
        self::LEVEL_BAD => '⭐⭐',
        self::LEVEL_TERRIBLE => '�?,
    ];
    
    // 类型
    const TYPE_OVERALL = 'overall';
    const TYPE_LOVE = 'love';
    const TYPE_CAREER = 'career';
    const TYPE_WEALTH = 'wealth';
    const TYPE_HEALTH = 'health';
    const TYPE_ADVICE = 'advice';
    const TYPE_LUCKY = 'lucky';
    
    const TYPES = [
        self::TYPE_OVERALL => '综合运势',
        self::TYPE_LOVE => '感情运势',
        self::TYPE_CAREER => '事业运势',
        self::TYPE_WEALTH => '财运',
        self::TYPE_HEALTH => '健康',
        self::TYPE_ADVICE => '建议',
        self::TYPE_LUCKY => '幸运�?,
    ];
    
    /**
     * 随机获取一个模�?
     */
    public static function getRandomTemplate(string $type, int $level = null): ?self
    {
        $query = self::where('type', $type)
            ->where('is_enabled', 1);
        
        if ($level !== null) {
            $query->where('level', $level);
        }
        
        // 使用更安全的随机查询方式，避免orderRaw潜在的SQL注入风险
        $count = $query->count();
        if ($count === 0) {
            return null;
        }
        $offset = mt_rand(0, $count - 1);
        return $query->limit($offset, 1)->find();
    }
    
    /**
     * 获取类型名称
     */
    public function getTypeNameAttr($value, $data): string
    {
        return self::TYPES[$data['type']] ?? '其他';
    }
    
    /**
     * 获取等级名称
     */
    public function getLevelNameAttr($value, $data): string
    {
        return self::LEVEL_NAMES[$data['level']] ?? '未知';
    }
    
    /**
     * 获取等级表情
     */
    public function getLevelEmojiAttr($value, $data): string
    {
        return self::LEVEL_EMOJIS[$data['level']] ?? '�?;
    }
}