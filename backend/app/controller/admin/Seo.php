<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\SeoConfig;
use think\Request;
use think\facade\Db;

class Seo extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

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
                'home' => ['name' => '首页', 'icon' => 'House'],
                'bazi' => ['name' => '八字测算', 'icon' => 'Calendar'],
                'tarot' => ['name' => '塔罗牌', 'icon' => 'MagicStick'],
                'daily' => ['name' => '每日运势', 'icon' => 'Sunny'],
                'almanac' => ['name' => '黄历', 'icon' => 'Calendar'],
                'hehun' => ['name' => '合婚测算', 'icon' => 'Connection'],
                'qiming' => ['name' => '起名', 'icon' => 'EditPen'],
                'about' => ['name' => '关于我们', 'icon' => 'InfoFilled'],
                'contact' => ['name' => '联系我们', 'icon' => 'Phone'],
                'privacy' => ['name' => '隐私政策', 'icon' => 'Lock'],
                'terms' => ['name' => '服务条款', 'icon' => 'Document']
            ];

            return $this->success($pageTypes);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_seo_page_types', $e, '获取页面类型失败');
        }
    }
}
