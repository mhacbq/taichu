<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 页面回收站模型
 */
class PageRecycle extends Model
{
    protected $table = 'page_recycle';
    
    protected $autoWriteTimestamp = false;
    
    protected $createTime = false;
    protected $updateTime = false;
    
    protected $type = [
        'id' => 'integer',
        'version' => 'integer',
        'deleted_by' => 'integer'
    ];
    
    protected $json = ['content', 'settings'];
    protected $jsonAssoc = true;
    
    /**
     * 获取回收站列表
     */
    public static function getList($params = [])
    {
        $query = self::order('deleted_at', 'desc');
        
        if (!empty($params['keyword'])) {
            $query->where('title|page_id', 'like', "%{$params['keyword']}%");
        }
        
        return $query;
    }
    
    /**
     * 根据页面ID查找
     */
    public static function findByPageId($pageId)
    {
        return self::where('page_id', $pageId)->find();
    }
    
    /**
     * 恢复页面
     */
    public static function restore($id)
    {
        $recycle = self::find($id);
        if (!$recycle) {
            return false;
        }
        
        // 恢复页面到原表
        Page::updateOrCreate(
            ['page_id' => $recycle->page_id],
            [
                'title' => $recycle->title,
                'content' => $recycle->content,
                'settings' => $recycle->settings,
                'version' => $recycle->version
            ]
        );
        
        // 删除回收站记录
        $recycle->delete();
        
        return true;
    }
}
