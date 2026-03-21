<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\AiPrompt;
use think\Request;
use think\facade\Db;

class AiPrompt extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    /**
     * 获取提示词列表
     */
    public function getList()
    {
        if (!$this->hasAdminPermission('ai_view')) {
            return $this->error('无权限查看提示词', 403);
        }

        $params = $this->request->get();
        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['page_size'] ?? 20);
        $type = $params['type'] ?? '';
        $keyword = $params['keyword'] ?? '';

        try {
            $query = AiPrompt::where('is_deleted', 0);

            if ($type) {
                $query->where('type', $type);
            }

            if ($keyword) {
                $query->whereLike('title|content', "%{$keyword}%");
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
            return $this->respondSystemException('admin_ai_prompt_list', $e, '获取提示词列表失败');
        }
    }

    /**
     * 获取提示词详情
     */
    public function getDetail($id)
    {
        if (!$this->hasAdminPermission('ai_view')) {
            return $this->error('无权限查看提示词', 403);
        }

        try {
            $prompt = AiPrompt::find($id);
            if (!$prompt) {
                return $this->error('提示词不存在', 404);
            }

            return $this->success($prompt);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_ai_prompt_detail', $e, '获取提示词详情失败');
        }
    }

    /**
     * 保存提示词
     */
    public function save()
    {
        if (!$this->hasAdminPermission('ai_edit')) {
            return $this->error('无权限编辑提示词', 403);
        }

        $data = $this->request->post();
        $id = $data['id'] ?? 0;

        try {
            if ($id) {
                $prompt = AiPrompt::find($id);
                if (!$prompt) {
                    return $this->error('提示词不存在', 404);
                }
                $prompt->save($data);
            } else {
                $prompt = AiPrompt::create($data);
            }

            return $this->success($prompt, '保存成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_ai_prompt_save', $e, '保存提示词失败');
        }
    }

    /**
     * 删除提示词
     */
    public function delete($id)
    {
        if (!$this->hasAdminPermission('ai_edit')) {
            return $this->error('无权限删除提示词', 403);
        }

        try {
            $prompt = AiPrompt::find($id);
            if (!$prompt) {
                return $this->error('提示词不存在', 404);
            }

            $prompt->is_deleted = 1;
            $prompt->save();

            return $this->success([], '删除成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_ai_prompt_delete', $e, '删除提示词失败');
        }
    }

    /**
     * 设为默认
     */
    public function setDefault($id)
    {
        if (!$this->hasAdminPermission('ai_edit')) {
            return $this->error('无权限编辑提示词', 403);
        }

        try {
            $prompt = AiPrompt::find($id);
            if (!$prompt) {
                return $this->error('提示词不存在', 404);
            }

            AiPrompt::where('type', $prompt->type)->update(['is_default' => 0]);
            $prompt->is_default = 1;
            $prompt->save();

            return $this->success([], '设置成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_ai_prompt_set_default', $e, '设置默认提示词失败');
        }
    }

    /**
     * 预览提示词
     */
    public function preview($id)
    {
        if (!$this->hasAdminPermission('ai_view')) {
            return $this->error('无权限查看提示词', 403);
        }

        try {
            $prompt = AiPrompt::find($id);
            if (!$prompt) {
                return $this->error('提示词不存在', 404);
            }

            return $this->success([
                'id' => $prompt->id,
                'title' => $prompt->title,
                'content' => $prompt->content,
                'variables' => $prompt->variables ?? []
            ]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_ai_prompt_preview', $e, '预览提示词失败');
        }
    }

    /**
     * 复制提示词
     */
    public function duplicate($id)
    {
        if (!$this->hasAdminPermission('ai_edit')) {
            return $this->error('无权限编辑提示词', 403);
        }

        try {
            $prompt = AiPrompt::find($id);
            if (!$prompt) {
                return $this->error('提示词不存在', 404);
            }

            $newPrompt = $prompt->toArray();
            unset($newPrompt['id'], $newPrompt['created_at'], $newPrompt['updated_at']);
            $newPrompt['title'] = $newPrompt['title'] . ' (副本)';
            $newPrompt['is_default'] = 0;

            $created = AiPrompt::create($newPrompt);

            return $this->success($created, '复制成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_ai_prompt_duplicate', $e, '复制提示词失败');
        }
    }

    /**
     * 获取提示词类型
     */
    public function getTypes()
    {
        try {
            $types = AiPrompt::where('is_deleted', 0)
                ->field('type, COUNT(*) as count')
                ->group('type')
                ->select()
                ->toArray();

            return $this->success($types);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_ai_prompt_types', $e, '获取提示词类型失败');
        }
    }
}
