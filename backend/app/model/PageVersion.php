<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 页面版本模型
 */
class PageVersion extends Model
{
    protected $table = 'tc_page_versions';
    
    protected $autoWriteTimestamp = true;
    
    protected $createTime = 'created_at';
    protected $updateTime = false;
    
    protected $type = [
        'id' => 'integer',
        'version' => 'integer',
        'author_id' => 'integer'
    ];
    
    protected $json = ['content', 'settings'];
    protected $jsonAssoc = true;
    
    /**
     * 获取页面的版本历�?
     */
    public static function getVersionsByPageId($pageId, $params = [])
    {
        $query = self::where('page_id', $pageId)
            ->order('created_at', 'desc');
        
        return $query;
    }
    
    /**
     * 创建新版�?
     */
    public static function createVersion($pageId, $data)
    {
        return self::create([
            'page_id' => $pageId,
            'content' => $data['content'] ?? [],
            'settings' => $data['settings'] ?? [],
            'version' => $data['version'] ?? 1,
            'author_id' => $data['author_id'] ?? 0,
            'author_name' => $data['author_name'] ?? '',
            'description' => $data['description'] ?? '',
            'auto_save' => $data['auto_save'] ?? false
        ]);
    }
}