<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 上传文件模型
 */
class UploadFile extends Model
{
    protected $table = 'upload_files';
    
    protected $autoWriteTimestamp = true;
    
    protected $createTime = 'created_at';
    protected $updateTime = false;
    
    protected $type = [
        'id' => 'integer',
        'file_size' => 'integer',
        'uploaded_by' => 'integer',
        'is_deleted' => 'boolean'
    ];
    
    /**
     * 获取文件列表
     */
    public static function getList($params = [])
    {
        $query = self::where('is_deleted', 0)
            ->order('created_at', 'desc');
        
        if (!empty($params['type'])) {
            $query->where('type', $params['type']);
        }
        
        if (!empty($params['keyword'])) {
            $query->where('original_name', 'like', "%{$params['keyword']}%");
        }
        
        if (!empty($params['uploaded_by'])) {
            $query->where('uploaded_by', $params['uploaded_by']);
        }
        
        return $query;
    }
    
    /**
     * 根据路径查找文件
     */
    public static function findByPath($path)
    {
        return self::where('file_path', $path)->find();
    }
    
    /**
     * 软删除
     */
    public function softDelete()
    {
        $this->is_deleted = 1;
        $this->deleted_at = date('Y-m-d H:i:s');
        return $this->save();
    }
}