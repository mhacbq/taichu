<?php
namespace app\model;

use think\Model;

class AiPrompt extends Model
{
    protected $table = 'tc_ai_prompt';
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $json = ['variables'];
    protected $jsonAssoc = true;

    protected $type = [
        'is_deleted' => 'integer',
        'is_default' => 'integer',
        'variables' => 'json'
    ];

    /**
     * 搜索器：按类型搜索
     */
    public function searchTypeAttr($query, $value)
    {
        return $query->where('type', $value);
    }

    /**
     * 搜索器：按关键词搜索
     */
    public function searchKeywordAttr($query, $value)
    {
        return $query->whereLike('title|content', "%{$value}%");
    }

    /**
     * 获取默认提示词
     */
    public static function getDefaultPrompt($type)
    {
        return self::where('type', $type)
            ->where('is_default', 1)
            ->where('is_deleted', 0)
            ->find();
    }

    /**
     * 按类型获取提示词列表
     */
    public static function getPromptsByType($type)
    {
        return self::where('type', $type)
            ->where('is_deleted', 0)
            ->order('is_default', 'desc')
            ->order('id', 'desc')
            ->select();
    }
}
