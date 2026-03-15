<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 页面草稿模型
 */
class PageDraft extends Model
{
    protected $table = 'page_drafts';
    
    protected $autoWriteTimestamp = true;
    
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    
    protected $type = [
        'id' => 'integer',
        'admin_id' => 'integer'
    ];
    
    protected $json = ['content', 'settings'];
    protected $jsonAssoc = true;
    
    /**
     * 获取用户的草稿
     */
    public static function getDraft($pageId, $adminId)
    {
        return self::where('page_id', $pageId)
            ->where('admin_id', $adminId)
            ->order('updated_at', 'desc')
            ->find();
    }
    
    /**
     * 保存草稿
     */
    public static function saveDraft($pageId, $adminId, $data)
    {
        $draft = self::where('page_id', $pageId)
            ->where('admin_id', $adminId)
            ->find();
        
        if ($draft) {
            $draft->save([
                'content' => $data['content'] ?? [],
                'settings' => $data['settings'] ?? [],
                'auto_save' => $data['auto_save'] ?? true
            ]);
            return $draft;
        }
        
        return self::create([
            'page_id' => $pageId,
            'admin_id' => $adminId,
            'content' => $data['content'] ?? [],
            'settings' => $data['settings'] ?? [],
            'auto_save' => $data['auto_save'] ?? true
        ]);
    }
}