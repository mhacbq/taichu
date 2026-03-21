<?php
declare (strict_types = 1);

namespace app\model;

use app\service\SchemaInspector;
use think\Model;

/**
 * FAQ常见问题模型
 */
class Faq extends Model
{
    protected $table = 'faqs';
    protected static ?string $enabledColumn = null;

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

    public static function getEnabledColumn(): string
    {
        if (self::$enabledColumn !== null) {
            return self::$enabledColumn;
        }

        $columns = SchemaInspector::getTableColumns((new self())->getTable());
        if (isset($columns['is_enabled'])) {
            self::$enabledColumn = 'is_enabled';
            return self::$enabledColumn;
        }

        if (isset($columns['is_published'])) {
            self::$enabledColumn = 'is_published';
            return self::$enabledColumn;
        }

        self::$enabledColumn = isset($columns['is_active']) ? 'is_active' : 'is_enabled';

        return self::$enabledColumn;
    }

    public static function normalizeItem(array $item): array
    {
        if (!array_key_exists('is_enabled', $item)) {
            $item['is_enabled'] = (int) ($item['is_published'] ?? $item['is_active'] ?? 1);
        }

        if (!array_key_exists('view_count', $item)) {
            $item['view_count'] = 0;
        }

        return $item;
    }

    public static function normalizeList(array $items): array
    {
        return array_map(static fn (array $item): array => self::normalizeItem($item), $items);
    }

    public static function mapStatusPayload(array $data): array
    {
        $enabledColumn = self::getEnabledColumn();
        $status = array_key_exists('is_enabled', $data)
            ? (int) $data['is_enabled']
            : (int) ($data['is_published'] ?? 1);

        unset($data['is_enabled'], $data['is_published']);
        $data[$enabledColumn] = $status;

        return $data;
    }

    /**
     * 获取启用的FAQ列表
     */
    public static function getEnabledList(string $category = null): array
    {
        $query = self::where(self::getEnabledColumn(), 1);

        if ($category) {
            $query->where('category', $category);
        }
        
        return self::normalizeList($query->order('sort_order', 'asc')
            ->select()
            ->toArray());
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