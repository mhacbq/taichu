<?php
namespace app\model;

use think\Model;

class SeoConfig extends Model
{
    protected $table = 'tc_seo_config';
    protected $pk = 'id';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [
        'is_active' => 'integer',
        'priority'  => 'float',
    ];

    // 允许批量赋值的字段
    protected $field = [
        'route', 'title', 'description', 'keywords', 'image',
        'robots', 'og_type', 'canonical', 'priority', 'changefreq', 'is_active',
    ];

    /**
     * 获取页面SEO配置（按路由查询）
     */
    public static function getPageSeo($route)
    {
        return self::where('route', $route)
            ->where('is_active', 1)
            ->find();
    }

    /**
     * 获取所有生效的SEO配置
     */
    public static function getActiveConfigs()
    {
        return self::where('is_active', 1)
            ->select();
    }
}
