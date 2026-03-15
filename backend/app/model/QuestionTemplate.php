<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 塔罗问题模板模型
 */
class QuestionTemplate extends Model
{
    protected $table = 'question_templates';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    
    // 字段类型转换
    protected $type = [
        'sort_order' => 'integer',
        'is_enabled' => 'integer',
        'use_count' => 'integer',
    ];
    
    // 分类
    const CATEGORY_LOVE = 'love';
    const CATEGORY_CAREER = 'career';
    const CATEGORY_STUDY = 'study';
    const CATEGORY_LIFE = 'life';
    const CATEGORY_CHOICE = 'choice';
    
    const CATEGORIES = [
        self::CATEGORY_LOVE => '感情',
        self::CATEGORY_CAREER => '事业',
        self::CATEGORY_STUDY => '学业',
        self::CATEGORY_LIFE => '生活',
        self::CATEGORY_CHOICE => '抉择',
    ];
    
    /**
     * 获取启用的模板列表
     */
    public static function getEnabledList(string $category = null): array
    {
        $query = self::where('is_enabled', 1);
        
        if ($category) {
            $query->where('category', $category);
        }
        
        return $query->order('sort_order', 'asc')
            ->order('use_count', 'desc')
            ->limit(20)
            ->column('question', 'id');
    }
    
    /**
     * 获取热门模板
     */
    public static function getHotTemplates(int $limit = 10): array
    {
        return self::where('is_enabled', 1)
            ->order('use_count', 'desc')
            ->limit($limit)
            ->column('question', 'id');
    }
    
    /**
     * 获取分类名称
     */
    public function getCategoryNameAttr($value, $data): string
    {
        return self::CATEGORIES[$data['category']] ?? '其他';
    }
    
    /**
     * 增加使用次数
     */
    public function incrementUseCount(): void
    {
        $this->use_count++;
        $this->save();
    }
}