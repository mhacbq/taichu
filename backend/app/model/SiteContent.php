<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 网站内容模型
 */
class SiteContent extends Model
{
    protected $table = 'site_contents';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    
    // 字段类型转换
    protected $type = [
        'sort_order' => 'integer',
    ];
    
    /**
     * 根据键名获取内容
     */
    public static function getByKey(string $key, string $page = 'home'): ?string
    {
        $content = self::where('key', $key)
            ->where('page', $page)
            ->where('is_enabled', 1)
            ->value('value');
        
        return $content;
    }
    
    /**
     * 获取整页内容
     */
    public static function getPageContent(string $page): array
    {
        $contents = self::where('page', $page)
            ->where('is_enabled', 1)
            ->order('sort_order', 'asc')
            ->column('value', 'key');
        
        return $contents;
    }
    
    /**
     * 批量更新内容
     */
    public static function batchUpdate(array $contents, string $page = 'home', int $userId = 0): bool
    {
        foreach ($contents as $key => $value) {
            $data = [
                'key' => $key,
                'page' => $page,
                'value' => $value,
                'updated_by' => $userId,
                'is_enabled' => 1,
            ];
            
            // 检查是否存在
            $exists = self::where('key', $key)->where('page', $page)->find();
            if ($exists) {
                $exists->save($data);
            } else {
                $data['created_by'] = $userId;
                self::create($data);
            }
        }
        
        return true;
    }
}