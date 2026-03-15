<?php
declare (strict_types = 1);

namespace app\model;

use think\Model;

/**
 * 用户评价模型
 */
class Testimonial extends Model
{
    protected $table = 'testimonials';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    
    // 字段类型转换
    protected $type = [
        'sort_order' => 'integer',
        'is_enabled' => 'integer',
    ];
    
    // 服务类型
    const SERVICE_BAZI = 'bazi';
    const SERVICE_TAROT = 'tarot';
    const SERVICE_DAILY = 'daily';
    
    const SERVICE_TYPES = [
        self::SERVICE_BAZI => '八字分析',
        self::SERVICE_TAROT => '塔罗测试',
        self::SERVICE_DAILY => '每日指南',
    ];
    
    /**
     * 获取启用的评价列表
     */
    public static function getEnabledList(int $limit = 6): array
    {
        return self::where('is_enabled', 1)
            ->order('sort_order', 'asc')
            ->order('created_at', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }
    
    /**
     * 获取服务类型名称
     */
    public function getServiceTypeNameAttr($value, $data): string
    {
        return self::SERVICE_TYPES[$data['service_type']] ?? '其他';
    }
}