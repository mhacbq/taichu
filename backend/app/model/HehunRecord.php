<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 八字合婚记录模型
 */
class HehunRecord extends Model
{
    protected $table = 'hehun_records';
    protected $pk = 'id';
    
    // 自动时间戳
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    
    // 字段类型
    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'score' => 'integer',
        'points_cost' => 'integer',
        'is_ai_analysis' => 'boolean',
        'male_bazi' => 'json',
        'female_bazi' => 'json',
        'result' => 'json',
        'ai_analysis' => 'json',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];
    
    // 隐藏字段
    protected $hidden = ['delete_time'];
    
    /**
     * 获取用户的合婚历史
     */
    public static function getUserHistory(int $userId, int $limit = 20): array
    {
        return self::where('user_id', $userId)
            ->order('create_time', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }
    
    /**
     * 获取用户的合婚次数
     */
    public static function getUserCount(int $userId): int
    {
        return self::where('user_id', $userId)->count();
    }
    
    /**
     * 获取今日合婚次数
     */
    public static function getTodayCount(int $userId): int
    {
        return self::where('user_id', $userId)
            ->whereDay('create_time')
            ->count();
    }
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    
    /**
     * 获取等级文本
     */
    public function getLevelTextAttr($value, $data)
    {
        $levels = [
            'excellent' => '天作之合',
            'good' => '佳偶天成',
            'medium' => '中等婚配',
            'fair' => '需加经营',
            'poor' => '谨慎考虑'
        ];
        
        $result = json_decode($data['result'] ?? '{}', true);
        return $levels[$result['level'] ?? ''] ?? '未知';
    }
    
    /**
     * 获取简要信息（用于列表展示）
     */
    public function getSummaryAttr($value, $data)
    {
        $result = json_decode($data['result'] ?? '{}', true);
        
        return [
            'id' => $data['id'],
            'male_name' => $data['male_name'],
            'female_name' => $data['female_name'],
            'score' => $data['score'],
            'level' => $result['level'] ?? '',
            'level_text' => $this->getLevelTextAttr($value, $data),
            'create_time' => $data['create_time'],
        ];
    }
}
