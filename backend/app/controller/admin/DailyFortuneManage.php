<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\DailyFortune;
use think\Request;

/**
 * 每日运势内容管理控制器
 */
class DailyFortuneManage extends BaseController
{
    /**
     * 获取每日运势列表
     */
    public function list(Request $request)
    {
        if (!$this->checkPermission('content_manage')) {
            return $this->error('无权限查看内容', 403);
        }

        try {
            $pagination = $this->normalizePagination(
                $request->get('page', 1),
                $request->get('pageSize', 20)
            );
            $date = $request->get('date', '');

            $query = DailyFortune::order('date', 'desc');
            if ($date) {
                $query->where('date', $date);
            }

            $total = $query->count();
            $list = $query->page($pagination['page'], $pagination['pageSize'])->select();

            return $this->success([
                'list'  => $list,
                'total' => $total,
            ]);
        } catch (\Exception $e) {
            return $this->error('获取运势列表失败', 500);
        }
    }

    /**
     * 创建每日运势
     */
    public function create(Request $request)
    {
        if (!$this->checkPermission('content_manage')) {
            return $this->error('无权限编辑内容', 403);
        }

        $data = [];
        try {
            $data = $request->post();
            $fortune = DailyFortune::create($data);

            $this->logOperation('create', 'content', [
                'target_id'   => $fortune->id,
                'target_type' => 'daily_fortune',
                'detail'      => '新增每日运势: ' . ($data['date'] ?? ''),
                'after_data'  => $data,
            ]);

            return $this->success($fortune, '创建成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_create_daily_fortune', $e, '创建失败，请稍后重试', [
                'date' => $data['date'] ?? '',
            ]);
        }
    }

    /**
     * 更新每日运势
     */
    public function update(Request $request, $id)
    {
        if (!$this->checkPermission('content_manage')) {
            return $this->error('无权限编辑内容', 403);
        }

        try {
            $data = $request->put();
            $fortune = DailyFortune::find($id);
            if (!$fortune) {
                return $this->error('记录不存在', 404);
            }

            $beforeData = $fortune->toArray();
            $fortune->save($data);

            $this->logOperation('update', 'content', [
                'target_id'   => $id,
                'target_type' => 'daily_fortune',
                'detail'      => '更新每日运势: ' . $fortune->date,
                'before_data' => $beforeData,
                'after_data'  => $data,
            ]);

            return $this->success(null, '更新成功');
        } catch (\Exception $e) {
            return $this->error('更新失败', 500);
        }
    }

    /**
     * 删除每日运势
     */
    public function delete($id)
    {
        if (!$this->checkPermission('content_manage')) {
            return $this->error('无权限删除内容', 403);
        }

        try {
            $fortune = DailyFortune::find($id);
            if (!$fortune) {
                return $this->error('记录不存在', 404);
            }

            $beforeData = $fortune->toArray();
            DailyFortune::destroy($id);

            $this->logOperation('delete', 'content', [
                'target_id'   => $id,
                'target_type' => 'daily_fortune',
                'detail'      => '删除每日运势: ' . $fortune->date,
                'before_data' => $beforeData,
            ]);

            return $this->success(null, '删除成功');
        } catch (\Exception $e) {
            return $this->error('删除失败', 500);
        }
    }
}
