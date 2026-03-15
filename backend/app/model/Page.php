<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 页面模型
 */
class Page extends Model
{
    protected $table = 'pages';
    
    protected $autoWriteTimestamp = true;
    
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    
    protected $type = [
        'id' => 'integer',
        'version' => 'integer',
        'updated_by' => 'integer'
    ];
    
    protected $json = ['content', 'settings'];
    protected $jsonAssoc = true;
    
    /**
     * 获取页面列表
     */
    public static function getList($params = [])
    {
        $query = self::order('updated_at', 'desc');
        
        if (!empty($params['keyword'])) {
            $query->where('title|page_id', 'like', "%{$params['keyword']}%");
        }
        
        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
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
     * 创建或更新
     */
    public static function updateOrCreate(array $conditions, array $data)
    {
        $model = self::where($conditions)->find();
        
        if ($model) {
            $model->save($data);
            return $model;
        }
        
        return self::create(array_merge($conditions, $data));
    }
}