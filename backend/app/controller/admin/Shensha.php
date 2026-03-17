<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\Shensha as ShenshaModel;
use think\Request;

/**
 * 神煞管理控制器
 */
class Shensha extends BaseController
{
    /**
     * 获取神煞列表
     */
    public function index()
    {
        try {
            $page = (int)$this->request->param('page', 1);
            $pageSize = (int)$this->request->param('pageSize', 20);
            $keyword = $this->request->param('keyword', '');
            $type = $this->request->param('type', '');
            $category = $this->request->param('category', '');

            $query = ShenshaModel::order('sort', 'asc')->order('id', 'asc');

            if ($keyword) {
                $query->where('name|description', 'like', "%{$keyword}%");
            }

            if ($type) {
                $query->where('type', $type);
            }

            if ($category) {
                $query->where('category', $category);
            }

            $total = $query->count();
            $list = $query->page($page, $pageSize)->select();

            return $this->success([
                'total' => $total,
                'list'  => $list,
            ]);
        } catch (\Exception $e) {
            return $this->error('获取神煞列表失败：' . $e->getMessage());
        }
    }

    /**
     * 保存/更新神煞
     */
    public function save()
    {
        try {
            $data = $this->request->post();
            
            if (empty($data['name'])) {
                return $this->error('神煞名称不能为空');
            }

            if (isset($data['id']) && $data['id']) {
                $model = ShenshaModel::find($data['id']);
                if (!$model) {
                    return $this->error('记录不存在');
                }
            } else {
                $model = new ShenshaModel();
            }

            // 处理 JSON 字段
            if (isset($data['gan_rules']) && is_string($data['gan_rules'])) {
                $data['gan_rules'] = json_decode($data['gan_rules'], true);
            }
            if (isset($data['zhi_rules']) && is_string($data['zhi_rules'])) {
                $data['zhi_rules'] = json_decode($data['zhi_rules'], true);
            }

            $model->save($data);
            return $this->success($model, '保存成功');
        } catch (\Exception $e) {
            return $this->error('保存失败：' . $e->getMessage());
        }
    }

    /**
     * 删除神煞
     */
    public function delete(int $id)
    {
        try {
            $model = ShenshaModel::find($id);
            if (!$model) {
                return $this->error('记录不存在');
            }
            $model->delete();
            return $this->success([], '删除成功');
        } catch (\Exception $e) {
            return $this->error('删除失败：' . $e->getMessage());
        }
    }

    /**
     * 更新状态
     */
    public function toggleStatus()
    {
        try {
            $id = (int)$this->request->post('id');
            $status = (int)$this->request->post('status');

            $model = ShenshaModel::find($id);
            if (!$model) {
                return $this->error('记录不存在');
            }

            $model->status = $status;
            $model->save();

            return $this->success([], '状态更新成功');
        } catch (\Exception $e) {
            return $this->error('更新状态失败：' . $e->getMessage());
        }
    }
}
