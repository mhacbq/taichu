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
        'status' => 'integer',
        'is_deleted' => 'integer',
        'sort_order' => 'integer'
    ];

    // 允许批量赋值的字段
    protected $field = [
        'page_type', 'route_path', 'title', 'keywords', 'description',
        'og_image', 'robots', 'structured_data', 'status', 'is_deleted', 'sort_order'
    ];

    /**
     * 获取页面SEO配置
     */
    public static function getPageSeo($pageType)
    {
        return self::where('page_type', $pageType)
            ->where('status', 1)
            ->where('is_deleted', 0)
            ->find();
    }

    /**
     * 获取所有生效的SEO配置
     */
    public static function getActiveConfigs()
    {
        return self::where('status', 1)
            ->where('is_deleted', 0)
            ->select();
    }
}
