<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\SeoConfig;
use think\Request;
use think\facade\Db;

class Seo extends BaseController
{
    protected $middleware = [
        \app\middleware\AdminAuth::class => ['except' => ['getActiveSeoConfigs']]
    ];

    /**
     * 获取SEO配置列表
     */
    public function getList()
    {
        if (!$this->hasAdminPermission('content_manage')) {
            return $this->error('无权限查看SEO配置', 403);
        }

        $params = $this->request->get();
        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['page_size'] ?? 20);
        $pageType = $params['page_type'] ?? '';

        try {
            $query = SeoConfig::where('is_deleted', 0);

            if ($pageType) {
                $query->where('page_type', $pageType);
            }

            $total = $query->count();
            $list = $query->order('id', 'desc')
                ->limit($pageSize)
                ->page($page)
                ->select()
                ->toArray();

            return $this->success([
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize
            ]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_seo_list', $e, '获取SEO配置失败');
        }
    }

    /**
     * 获取SEO配置详情
     */
    public function getDetail($id)
    {
        if (!$this->hasAdminPermission('content_manage')) {
            return $this->error('无权限查看SEO配置', 403);
        }

        try {
            $config = SeoConfig::find($id);
            if (!$config) {
                return $this->error('SEO配置不存在', 404);
            }

            return $this->success($config);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_seo_detail', $e, '获取SEO配置详情失败');
        }
    }

    /**
     * 保存SEO配置
     */
    public function save()
    {
        if (!$this->hasAdminPermission('content_manage')) {
            return $this->error('无权限编辑SEO配置', 403);
        }

        $data = $this->request->post();
        $id = $data['id'] ?? 0;

        try {
            if ($id) {
                $config = SeoConfig::find($id);
                if (!$config) {
                    return $this->error('SEO配置不存在', 404);
                }
                $config->save($data);
            } else {
                $config = SeoConfig::create($data);
            }

            $this->logOperation('save_seo_config', 'seo', [
                'config_id' => $config->id,
                'page_type' => $config->page_type
            ]);

            return $this->success($config, '保存成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_seo_save', $e, '保存SEO配置失败');
        }
    }

    /**
     * 删除SEO配置
     */
    public function delete($id)
    {
        if (!$this->hasAdminPermission('content_manage')) {
            return $this->error('无权限删除SEO配置', 403);
        }

        try {
            $config = SeoConfig::find($id);
            if (!$config) {
                return $this->error('SEO配置不存在', 404);
            }

            $config->is_deleted = 1;
            $config->save();

            $this->logOperation('delete_seo_config', 'seo', [
                'config_id' => $id,
                'page_type' => $config->page_type
            ]);

            return $this->success([], '删除成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_seo_delete', $e, '删除SEO配置失败');
        }
    }

    /**
     * 批量更新状态
     */
    public function batchUpdateStatus()
    {
        if (!$this->hasAdminPermission('content_manage')) {
            return $this->error('无权限编辑SEO配置', 403);
        }

        $data = $this->request->post();
        $ids = $data['ids'] ?? [];
        $status = $data['status'] ?? 0;

        if (empty($ids) || !is_array($ids)) {
            return $this->error('请选择配置');
        }

        try {
            $count = SeoConfig::whereIn('id', $ids)->update(['status' => $status]);

            $this->logOperation('batch_update_seo_status', 'seo', [
                'ids' => $ids,
                'status' => $status,
                'count' => $count
            ]);

            return $this->success(['count' => $count], "更新{$count}个配置状态");
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_seo_batch_status', $e, '批量更新状态失败');
        }
    }

    /**
     * 获取页面类型列表
     */
    public function getPageTypes()
    {
        try {
            $pageTypes = [
                'home' => ['name' => '首页', 'icon' => 'House', 'route' => '/'],
                'bazi' => ['name' => '八字测算', 'icon' => 'Calendar', 'route' => '/bazi'],
                'tarot' => ['name' => '塔罗牌', 'icon' => 'MagicStick', 'route' => '/tarot'],
                'daily' => ['name' => '每日运势', 'icon' => 'Sunny', 'route' => '/daily'],
                'almanac' => ['name' => '黄历', 'icon' => 'Calendar', 'route' => '/almanac'],
                'hehun' => ['name' => '合婚测算', 'icon' => 'Connection', 'route' => '/hehun'],
                'qiming' => ['name' => '起名', 'icon' => 'EditPen', 'route' => '/qiming'],
                'liuyao' => ['name' => '六爻占卜', 'icon' => 'Compass', 'route' => '/liuyao'],
                'yearly-fortune' => ['name' => '流年运势', 'icon' => 'TrendCharts', 'route' => '/yearly-fortune'],
                'help' => ['name' => '帮助中心', 'icon' => 'QuestionFilled', 'route' => '/help'],
                'recharge' => ['name' => '积分充值', 'icon' => 'Wallet', 'route' => '/recharge'],
                'vip' => ['name' => 'VIP会员', 'icon' => 'Trophy', 'route' => '/vip'],
                'agreement' => ['name' => '用户协议', 'icon' => 'Document', 'route' => '/legal/agreement'],
                'privacy' => ['name' => '隐私政策', 'icon' => 'Lock', 'route' => '/legal/privacy'],
            ];

            return $this->success($pageTypes);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_seo_page_types', $e, '获取页面类型失败');
        }
    }

    /**
     * SEO配置列表（兼容 seo/configs 路由）
     */
    public function seoConfigList()
    {
        if (!$this->hasAdminPermission('content_manage')) {
            return $this->error('无权限查看SEO配置', 403);
        }

        try {
            $list = SeoConfig::where('is_deleted', 0)
                ->order('sort_order', 'asc')
                ->order('id', 'asc')
                ->select()
                ->toArray();

            return $this->success([
                'list' => $list,
                'total' => count($list)
            ]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_seo_config_list', $e, '获取SEO配置列表失败');
        }
    }

    /**
     * 保存SEO配置（兼容 seo/save 路由）
     */
    public function saveSeoConfig()
    {
        if (!$this->hasAdminPermission('content_manage')) {
            return $this->error('无权限编辑SEO配置', 403);
        }

        $data = $this->request->post();
        $id = $data['id'] ?? 0;
        $pageType = $data['page_type'] ?? '';

        if (empty($pageType) && empty($id)) {
            return $this->error('页面类型不能为空');
        }

        try {
            // 检查page_type是否重复（排除当前记录）
            if ($pageType) {
                $exists = SeoConfig::where('page_type', $pageType)
                    ->where('is_deleted', 0);
                if ($id) {
                    $exists->where('id', '<>', $id);
                }
                if ($exists->find()) {
                    return $this->error('该页面类型的SEO配置已存在');
                }
            }

            $saveData = [
                'page_type' => $data['page_type'] ?? '',
                'route_path' => $data['route_path'] ?? '',
                'title' => $data['title'] ?? '',
                'keywords' => $data['keywords'] ?? '',
                'description' => $data['description'] ?? '',
                'og_image' => $data['og_image'] ?? '',
                'robots' => $data['robots'] ?? 'index,follow',
                'structured_data' => $data['structured_data'] ?? null,
                'status' => (int)($data['status'] ?? 1),
                'sort_order' => (int)($data['sort_order'] ?? 0),
            ];

            if ($id) {
                $config = SeoConfig::find($id);
                if (!$config) {
                    return $this->error('SEO配置不存在', 404);
                }
                $config->save($saveData);
            } else {
                $config = SeoConfig::create($saveData);
            }

            $this->logOperation('save_seo_config', 'seo', [
                'config_id' => $config->id,
                'page_type' => $config->page_type
            ]);

            return $this->success($config, '保存成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_seo_save_config', $e, '保存SEO配置失败');
        }
    }

    /**
     * 删除SEO配置（通过ID，兼容 DELETE seo/configs/:id 路由）
     */
    public function deleteSeoConfig($id = 0)
    {
        if (!$this->hasAdminPermission('content_manage')) {
            return $this->error('无权限删除SEO配置', 403);
        }

        try {
            $config = SeoConfig::find($id);
            if (!$config) {
                return $this->error('SEO配置不存在', 404);
            }

            $config->is_deleted = 1;
            $config->save();

            $this->logOperation('delete_seo_config', 'seo', [
                'config_id' => $id,
                'page_type' => $config->page_type
            ]);

            return $this->success([], '删除成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_seo_delete_config', $e, '删除SEO配置失败');
        }
    }

    /**
     * 通过路由路径删除SEO配置（兼容 seo/delete POST 路由）
     */
    public function deleteSeoConfigByRoute()
    {
        if (!$this->hasAdminPermission('content_manage')) {
            return $this->error('无权限删除SEO配置', 403);
        }

        $route = $this->request->post('route', '');
        if (empty($route)) {
            return $this->error('路由路径不能为空');
        }

        try {
            $config = SeoConfig::where('route_path', $route)
                ->where('is_deleted', 0)
                ->find();
            if (!$config) {
                return $this->error('SEO配置不存在', 404);
            }

            $config->is_deleted = 1;
            $config->save();

            $this->logOperation('delete_seo_config_by_route', 'seo', [
                'route' => $route,
                'page_type' => $config->page_type
            ]);

            return $this->success([], '删除成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_seo_delete_by_route', $e, '删除SEO配置失败');
        }
    }

    /**
     * SEO统计数据
     */
    public function seoStats()
    {
        if (!$this->hasAdminPermission('content_manage')) {
            return $this->error('无权限查看SEO数据', 403);
        }

        try {
            $total = SeoConfig::where('is_deleted', 0)->count();
            $active = SeoConfig::where('is_deleted', 0)->where('status', 1)->count();
            $inactive = SeoConfig::where('is_deleted', 0)->where('status', 0)->count();

            // 检查哪些页面还没有配置SEO
            $configuredTypes = SeoConfig::where('is_deleted', 0)
                ->column('page_type');

            $allTypes = ['home', 'bazi', 'tarot', 'daily', 'hehun', 'liuyao', 'qiming', 'yearly-fortune', 'help', 'recharge', 'vip', 'agreement', 'privacy'];
            $unconfigured = array_values(array_diff($allTypes, $configuredTypes));

            return $this->success([
                'total' => $total,
                'active' => $active,
                'inactive' => $inactive,
                'unconfigured' => $unconfigured,
                'unconfigured_count' => count($unconfigured),
                'coverage' => $total > 0 ? round(count($configuredTypes) / count($allTypes) * 100, 1) : 0
            ]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_seo_stats', $e, '获取SEO统计失败');
        }
    }

    /**
     * 获取robots.txt配置
     */
    public function seoRobots()
    {
        if (!$this->hasAdminPermission('content_manage')) {
            return $this->error('无权限查看robots配置', 403);
        }

        try {
            $robotsPath = public_path() . 'robots.txt';
            $content = '';
            if (file_exists($robotsPath)) {
                $content = file_get_contents($robotsPath);
            } else {
                // 默认robots.txt内容
                $content = "User-agent: *\nAllow: /\nDisallow: /maodou/\nDisallow: /api/\n\nSitemap: https://taichu.chat/sitemap.xml";
            }

            return $this->success(['content' => $content]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_seo_robots', $e, '获取robots配置失败');
        }
    }

    /**
     * 保存robots.txt配置
     */
    public function saveSeoRobots()
    {
        if (!$this->hasAdminPermission('content_manage')) {
            return $this->error('无权限编辑robots配置', 403);
        }

        $content = $this->request->post('content', '');

        try {
            $robotsPath = public_path() . 'robots.txt';
            file_put_contents($robotsPath, $content);

            $this->logOperation('save_robots_txt', 'seo', [
                'content_length' => strlen($content)
            ]);

            return $this->success([], 'robots.txt已保存');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_seo_save_robots', $e, '保存robots配置失败');
        }
    }

    /**
     * 生成sitemap.xml
     */
    public function generateSitemap()
    {
        if (!$this->hasAdminPermission('content_manage')) {
            return $this->error('无权限生成Sitemap', 403);
        }

        try {
            $baseUrl = 'https://taichu.chat';
            $configs = SeoConfig::where('is_deleted', 0)
                ->where('status', 1)
                ->order('sort_order', 'asc')
                ->select();

            $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

            foreach ($configs as $config) {
                $routePath = $config->route_path ?: '/';
                $priority = $config->page_type === 'home' ? '1.0' : '0.8';
                $changefreq = in_array($config->page_type, ['daily', 'home']) ? 'daily' : 'weekly';
                
                // 跳过noindex的页面
                if (stripos($config->robots ?: '', 'noindex') !== false) {
                    continue;
                }

                $xml .= "  <url>\n";
                $xml .= "    <loc>{$baseUrl}{$routePath}</loc>\n";
                $xml .= "    <lastmod>" . date('Y-m-d') . "</lastmod>\n";
                $xml .= "    <changefreq>{$changefreq}</changefreq>\n";
                $xml .= "    <priority>{$priority}</priority>\n";
                $xml .= "  </url>\n";
            }

            $xml .= '</urlset>';

            $sitemapPath = public_path() . 'sitemap.xml';
            file_put_contents($sitemapPath, $xml);

            $this->logOperation('generate_sitemap', 'seo', [
                'url_count' => count($configs),
                'file_path' => $sitemapPath
            ]);

            return $this->success([
                'url_count' => count($configs),
                'path' => '/sitemap.xml'
            ], 'Sitemap已生成');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_seo_generate_sitemap', $e, '生成Sitemap失败');
        }
    }

    /**
     * 提交URL到搜索引擎（预留接口）
     */
    public function seoSubmit()
    {
        if (!$this->hasAdminPermission('content_manage')) {
            return $this->error('无权限提交URL', 403);
        }

        try {
            // 预留接口，后续可对接百度推送等
            return $this->success([], '提交功能开发中');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_seo_submit', $e, '提交URL失败');
        }
    }

    /**
     * 【公共接口】获取所有启用的SEO配置（供前端路由守卫调用，无需登录）
     */
    public function getActiveSeoConfigs()
    {
        try {
            $configs = SeoConfig::where('is_deleted', 0)
                ->where('status', 1)
                ->field('page_type,route_path,title,keywords,description,og_image,robots,structured_data')
                ->order('sort_order', 'asc')
                ->select()
                ->toArray();

            // 转换为以route_path为key的映射，方便前端快速查找
            $map = [];
            foreach ($configs as $config) {
                $key = $config['route_path'] ?: $config['page_type'];
                $map[$key] = $config;
            }

            return $this->success($map);
        } catch (\Throwable $e) {
            return $this->respondSystemException('public_seo_configs', $e, '获取SEO配置失败');
        }
    }
}
