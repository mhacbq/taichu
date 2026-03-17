<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\Shensha as ShenshaModel;

/**
 * 神煞管理控制器
 */
class Shensha extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    /**
     * 获取神煞列表
     */
    public function index()
    {
        if (!$this->hasAdminPermission('content_manage')) {
            return $this->error('无权限查看神煞', 403);
        }

        try {
            $page = max(1, (int) $this->request->param('page', 1));
            $pageSize = max(1, min(100, (int) $this->request->param('pageSize', 20)));
            $keyword = trim((string) $this->request->param('keyword', ''));
            $type = trim((string) $this->request->param('type', ''));
            $category = trim((string) $this->request->param('category', ''));
            $statusParam = $this->request->param('status', '');

            $query = ShenshaModel::order('sort', 'asc')->order('id', 'asc');

            if ($keyword !== '') {
                $query->whereLike('name|description|effect', "%{$keyword}%");
            }

            if ($type !== '') {
                $query->where('type', $type);
            }

            if ($category !== '') {
                $query->where('category', $category);
            }

            if ($statusParam !== '' && $statusParam !== null) {
                $status = (int) $statusParam;
                if (!in_array($status, [0, 1], true)) {
                    return $this->error('状态值无效', 400);
                }
                $query->where('status', $status);
            }

            $total = $query->count();
            $list = $query->page($page, $pageSize)->select();

            return $this->success([
                'total' => $total,
                'list' => $list,
            ]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('shensha_index', $e, '获取神煞列表失败，请稍后重试', [
                'page' => $this->request->param('page', 1),
                'page_size' => $this->request->param('pageSize', 20),
                'keyword' => trim((string) $this->request->param('keyword', '')),
                'type' => trim((string) $this->request->param('type', '')),
                'category' => trim((string) $this->request->param('category', '')),
                'status' => $this->request->param('status', ''),
            ]);
        }
    }

    /**
     * 获取神煞筛选项
     */
    public function options()
    {
        if (!$this->hasAdminPermission('content_manage')) {
            return $this->error('无权限查看神煞筛选项', 403);
        }

        try {
            $types = ShenshaModel::where('type', '<>', '')
                ->distinct(true)
                ->order('type', 'asc')
                ->column('type');
            $categories = ShenshaModel::where('category', '<>', '')
                ->distinct(true)
                ->order('category', 'asc')
                ->column('category');

            return $this->success([
                'types' => $this->normalizeOptionValues($types),
                'categories' => $this->normalizeOptionValues($categories),
                'statuses' => [
                    ['label' => '启用', 'value' => 1],
                    ['label' => '停用', 'value' => 0],
                ],
            ]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('shensha_options', $e, '获取神煞筛选项失败，请稍后重试');
        }
    }

    /**
     * 保存/更新神煞
     */
    public function save()
    {
        if (!$this->hasAdminPermission('content_manage')) {
            return $this->error('无权限编辑神煞', 403);
        }

        try {
            $data = $this->request->post();
            $id = (int) ($data['id'] ?? $this->request->param('id', 0));
            $isUpdate = $id > 0;
            $nameProvided = array_key_exists('name', $data);
            $name = trim((string) ($data['name'] ?? ''));

            if (!$isUpdate && $name === '') {
                return $this->error('神煞名称不能为空', 400);
            }

            if ($nameProvided) {
                if ($name === '') {
                    return $this->error('神煞名称不能为空', 400);
                }
                $data['name'] = $name;
            }

            if (array_key_exists('type', $data)) {
                $data['type'] = trim((string) $data['type']);
            }

            if (array_key_exists('category', $data)) {
                $data['category'] = trim((string) $data['category']);
            }

            if (array_key_exists('status', $data)) {
                $status = (int) $data['status'];
                if (!in_array($status, [0, 1], true)) {
                    return $this->error('状态值无效', 400);
                }
                $data['status'] = $status;
            }

            $data['id'] = $id;

            if ($isUpdate) {
                $model = ShenshaModel::find($id);
                if (!$model) {
                    return $this->error('记录不存在', 404);
                }
                unset($data['id']);
            } else {
                unset($data['id']);
                $model = new ShenshaModel();
            }

            foreach (['gan_rules', 'zhi_rules'] as $field) {
                if (!array_key_exists($field, $data)) {
                    continue;
                }

                $parsedValue = $this->decodeJsonField($data[$field], $field);
                if ($parsedValue === null) {
                    unset($data[$field]);
                    continue;
                }

                $data[$field] = $parsedValue;
            }

            $model->save($data);

            return $this->success($model, '保存成功');
        } catch (\InvalidArgumentException $e) {
            return $this->respondBusinessException($e, 'shensha_save_validation', '神煞数据格式无效', 400, [
                'id' => (int) $this->request->param('id', $this->request->post('id', 0)),
                'name' => trim((string) $this->request->post('name', '')),
                'type' => trim((string) $this->request->post('type', '')),
                'category' => trim((string) $this->request->post('category', '')),
            ]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('shensha_save', $e, '保存神煞失败，请稍后重试', [
                'id' => (int) $this->request->param('id', $this->request->post('id', 0)),
                'name' => trim((string) $this->request->post('name', '')),
                'type' => trim((string) $this->request->post('type', '')),
                'category' => trim((string) $this->request->post('category', '')),
            ]);
        }
    }

    /**
     * 删除神煞
     */
    public function delete(int $id)
    {
        if (!$this->hasAdminPermission('content_manage')) {
            return $this->error('无权限删除神煞', 403);
        }

        try {
            $model = ShenshaModel::find($id);
            if (!$model) {
                return $this->error('记录不存在', 404);
            }

            $model->delete();

            return $this->success([], '删除成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('shensha_delete', $e, '删除神煞失败，请稍后重试', [
                'id' => $id,
            ]);
        }
    }

    /**
     * 更新状态
     */
    public function toggleStatus()
    {
        if (!$this->hasAdminPermission('content_manage')) {
            return $this->error('无权限更新神煞状态', 403);
        }

        try {
            $id = (int) $this->request->post('id');
            $status = (int) $this->request->post('status');

            if (!in_array($status, [0, 1], true)) {
                return $this->error('状态值无效', 400);
            }

            $model = ShenshaModel::find($id);
            if (!$model) {
                return $this->error('记录不存在', 404);
            }

            $model->status = $status;
            $model->save();

            return $this->success([], '状态更新成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('shensha_toggle_status', $e, '更新神煞状态失败，请稍后重试', [
                'id' => (int) $this->request->post('id'),
                'status' => (int) $this->request->post('status'),
            ]);
        }
    }

    /**
     * 解析 JSON 字段
     */
    protected function decodeJsonField(mixed $value, string $field): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return $value;
        }

        $decoded = json_decode((string) $value, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            throw new \InvalidArgumentException(sprintf('%s 字段不是合法的 JSON 数组', $field));
        }

        return $decoded;
    }

    /**
     * 归一化下拉选项值
     */
    protected function normalizeOptionValues(array $values): array
    {
        $normalized = array_map(static fn($value) => trim((string) $value), $values);
        $normalized = array_filter($normalized, static fn(string $value) => $value !== '');

        return array_values(array_unique($normalized));
    }
}
