<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 用户反馈模型
 */
class Feedback extends Model
{
    protected $table = 'feedback';
    
    protected $autoWriteTimestamp = true;
    
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    
    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer'
    ];
    
    /**
     * 获取反馈列表
     */
    public static function getList($params = [])
    {
        $query = self::order('id', 'desc');
        
        if (!empty($params['type'])) {
            $query->where('type', $params['type']);
        }
        
        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }
        
        if (!empty($params['user_id'])) {
            $query->where('user_id', $params['user_id']);
        }
        
        if (!empty($params['keyword'])) {
            $query->where('content|contact', 'like', "%{$params['keyword']}%");
        }
        
        return $query;
    }
    
    /**
     * 获取用户的反馈列表
     */
    public static function getUserFeedback($userId, $params = [])
    {
        $query = self::where('user_id', $userId)
            ->order('id', 'desc');
        
        if (!empty($params['type'])) {
            $query->where('type', $params['type']);
        }
        
        if (!empty($params['status'])) {
            $query->where('status', $params['status']);
        }
        
        return $query;
    }
    
    /**
     * 创建反馈
     */
    public static function createFeedback($data)
    {
        return self::create([
            'user_id' => $data['user_id'] ?? 0,
            'type' => $data['type'] ?? 'other',
            'content' => $data['content'] ?? '',
            'contact' => $data['contact'] ?? '',
            'images' => $data['images'] ?? '',
            'status' => 'pending',
            'reply' => '',
            'replied_at' => null
        ]);
    }
    
    /**
     * 回复反馈
     */
    public function reply($reply, $status = 'resolved')
    {
        $this->reply = $reply;
        $this->status = $status;
        $this->replied_at = date('Y-m-d H:i:s');
        return $this->save();
    }
}
