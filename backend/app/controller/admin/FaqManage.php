<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\Faq;
use think\facade\Log;

/**
 * FAQ管理控制器
 */
class FaqManage extends BaseController
{
    protected $middleware = [
        [\app\middleware\AdminAuth::class, 'except' => ['publicList']],
    ];

    /**
     * 获取FAQ列表
     */
    public function index()
    {
        try {
            $params = $this->request->get();
            $result = Faq::getAdminList($params);
            return $this->success($result);
        } catch (\Throwable $e) {
            Log::error('获取FAQ列表失败: ' . $e->getMessage());
            return $this->error('获取列表失败');
        }
    }

    /**
     * 保存FAQ（新增或更新）
     */
    public function save()
    {
        try {
            $data = $this->request->post();

            if (empty($data['question'])) {
                return $this->error('问题不能为空');
            }
            if (empty($data['answer'])) {
                return $this->error('回答不能为空');
            }

            $allowedFields = ['category', 'question', 'answer', 'sort_order', 'is_enabled'];
            $saveData = array_intersect_key($data, array_flip($allowedFields));

            if (!empty($data['id'])) {
                $faq = Faq::find((int)$data['id']);
                if (!$faq) {
                    return $this->error('FAQ不存在', 404);
                }
                $faq->save($saveData);
                return $this->success($faq->toArray(), '更新成功');
            } else {
                $saveData['category']   = $saveData['category'] ?? 'general';
                $saveData['sort_order'] = $saveData['sort_order'] ?? 0;
                $saveData['is_enabled'] = $saveData['is_enabled'] ?? 1;
                $faq = Faq::create($saveData);
                return $this->success($faq->toArray(), '创建成功');
            }
        } catch (\Throwable $e) {
            Log::error('保存FAQ失败: ' . $e->getMessage());
            return $this->error('保存失败');
        }
    }

    /**
     * 删除FAQ
     */
    public function delete(int $id)
    {
        try {
            $faq = Faq::find($id);
            if (!$faq) {
                return $this->error('FAQ不存在', 404);
            }
            $faq->delete();
            return $this->success([], '删除成功');
        } catch (\Throwable $e) {
            Log::error('删除FAQ失败: ' . $e->getMessage());
            return $this->error('删除失败');
        }
    }

    /**
     * 前台公开接口：获取已启用的FAQ列表（无需鉴权）
     */
    public function publicList()
    {
        try {
            $category = $this->request->get('category', '');
            $query = Faq::where('is_enabled', 1)->order('sort_order', 'asc')->order('id', 'asc');
            if ($category !== '') {
                $query->where('category', $category);
            }
            $list = $query->select()->toArray();
            return $this->success(['list' => $list, 'total' => count($list)]);
        } catch (\Throwable $e) {
            Log::error('获取公开FAQ列表失败: ' . $e->getMessage());
            return $this->error('获取失败');
        }
    }
}
