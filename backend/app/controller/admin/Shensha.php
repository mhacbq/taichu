<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\Shensha as ShenshaModel;
use think\facade\Log;

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
            $page = max(1, (int) $this->request->param('page', 1));
            $pageSize = max(1, min(100, (int) $this->request->param('pageSize', 20)));
            $keyword = trim((string) $this->request->param('keyword', ''));
            $type = trim((string) $this->request->param('type', ''));
            $category = trim((string) $this->request->param('category', ''));

            $query = ShenshaModel::order('sort', 'asc')->order('id', 'asc');

            if ($keyword !== '') {
                $query->where('name|description', 'like', "%{$keyword}%");
            }

            if ($type !== '') {
                $query->where('type', $type);
            }

            if ($category !== '') {
                $query->where('category', $category);
            }

            $total = $query->count();
            $list = $query->page($page, $pageSize)->select();

            return $this->success([
                'total' => $total,
                'list' => $list,
            ]);
        } catch (\Throwable $e) {
            $this->logFailure('获取神煞列表', $e, [
                'page' => $this->request->param('page', 1),
                'page_size' => $this->request->param('pageSize', 20),
                'keyword' => trim((string) $this->request->param('keyword', '')),
                'type' => trim((string) $this->request->param('type', '')),
                'category' => trim((string) $this->request->param('category', '')),
            ]);

            return $this->error('获取神煞列表失败，请稍后重试');
        }
    }

    /**
     * 获取神煞筛选项
     */
    public function options()
    {
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
            $this->logFailure('获取神煞选项', $e);

            return $this->error('获取神煞筛选项失败，请稍后重试');
        }
    }

    /**
     * 保存/更新神煞
     */
    public function save()
    {
        try {
            $data = $this->request->post();
            $id = (int) ($data['id'] ?? $this->request->param('id', 0));
            $name = trim((string) ($data['name'] ?? ''));

            if ($name === '') {
                return $this->error('神煞名称不能为空');
            }

            $data['name'] = $name;
            $data['id'] = $id;

            if ($id > 0) {
                $model = ShenshaModel::find($id);
                if (!$model) {
                    return $this->error('记录不存在');
                }
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
            return $this->error($e->getMessage());
        } catch (\Throwable $e) {
            $this->logFailure('保存神煞', $e, [
                'id' => (int) $this->request->param('id', $this->request->post('id', 0)),
                'name' => trim((string) $this->request->post('name', '')),
                'type' => trim((string) $this->request->post('type', '')),
                'category' => trim((string) $this->request->post('category', '')),
            ]);

            return $this->error('保存神煞失败，请稍后重试');
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
        } catch (\Throwable $e) {
            $this->logFailure('删除神煞', $e, ['id' => $id]);

            return $this->error('删除神煞失败，请稍后重试');
        }
    }

    /**
     * 更新状态
     */
    public function toggleStatus()
    {
        try {
            $id = (int) $this->request->post('id');
            $status = (int) $this->request->post('status');

            if (!in_array($status, [0, 1], true)) {
                return $this->error('状态值无效');
            }

            $model = ShenshaModel::find($id);
            if (!$model) {
                return $this->error('记录不存在');
            }

            $model->status = $status;
            $model->save();

            return $this->success([], '状态更新成功');
        } catch (\Throwable $e) {
            $this->logFailure('更新神煞状态', $e, [
                'id' => (int) $this->request->post('id'),
                'status' => (int) $this->request->post('status'),
            ]);

            return $this->error('更新神煞状态失败，请稍后重试');
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

    /**
     * 统一记录异常日志
     */
    protected function logFailure(string $action, \Throwable $e, array $context = []): void
    {
        Log::error($action . '失败', [
            'admin_id' => method_exists($this, 'getAdminId') ? $this->getAdminId() : null,
            'message' => $e->getMessage(),
            'context' => $context,
        ]);
    }
}
