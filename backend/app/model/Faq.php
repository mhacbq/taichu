<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * FAQ常见问题模型
 */
class Faq extends Model
{
    protected $table = 'faqs';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    
    // 字段类型转换
    protected $type = [
        'sort_order' => 'integer',
        'is_enabled' => 'integer',
        'view_count' => 'integer',
    ];
    
    // 分类
    const CATEGORY_GENERAL = 'general';
    const CATEGORY_BAZI = 'bazi';
    const CATEGORY_TAROT = 'tarot';
    const CATEGORY_ACCOUNT = 'account';
    const CATEGORY_POINTS = 'points';
    
    const CATEGORIES = [
        self::CATEGORY_GENERAL => '常见问题',
        self::CATEGORY_BAZI => '八字分析',
        self::CATEGORY_TAROT => '塔罗测试',
        self::CATEGORY_ACCOUNT => '账号相关',
        self::CATEGORY_POINTS => '积分问题',
    ];
    
    /**
     * 获取启用的FAQ列表
     */
    public static function getEnabledList(string $category = null): array
    {
        $query = self::where('is_enabled', 1);
        
        if ($category) {
            $query->where('category', $category);
        }
        
        return $query->order('sort_order', 'asc')
            ->select()
            ->toArray();
    }
    
    /**
     * 获取分类名称
     */
    public function getCategoryNameAttr($value, $data): string
    {
        return self::CATEGORIES[$data['category']] ?? '其他';
    }
    
    /**
     * 增加浏览量
     */
    public function incrementViewCount(): void
    {
        $this->view_count++;
        $this->save();
    }
}