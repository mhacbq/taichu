<?php

declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\FeedbackAssign as FeedbackAssignModel;
use app\model\FeedbackLog;
use think\facade\Db;
use think\Request;

/**
 * 后台反馈分配控制器
 */
class FeedbackAssign extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    /**
     * 获取分配列表
     */
    public function list(Request $request)
    {
        if (!$this->hasAdminPermission('feedback_view')) {
            return $this->error('无权限查看分配列表', 403);
        }

        try {
            $params = $request->get();

            $query = FeedbackAssignModel::with(['feedback', 'assignedBy', 'assignedTo'])
                ->order('created_at', 'desc');

            if (!empty($params['status'])) {
                $query->where('status', $params['status']);
            }

            if (!empty($params['assigned_to'])) {
                $query->where('assigned_to', $params['assigned_to']);
            }

            $list = $query->paginate([
                'list_rows' => (int) ($params['pageSize'] ?? 20),
                'page'      => (int) ($params['page'] ?? 1),
            ]);

            return $this->success([
                'list'  => $list->items(),
                'total' => $list->total(),
            ], '获取成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('feedback_assign_list', $e, '获取分配列表失败');
        }
    }

    /**
     * 分配反馈
     */
    public function assign(Request $request)
    {
        if (!$this->hasAdminPermission('content_manage')) {
            return $this->error('无权限分配反馈', 403);
        }

        $params = $request->post();

        if (empty($params['feedback_id']) || empty($params['assigned_to'])) {
            return $this->error('参数错误：feedback_id 和 assigned_to 不能为空', 422);
        }

        Db::startTrans();
        try {
            // 检查反馈是否已分配
            $exists = FeedbackAssignModel::where('feedback_id', $params['feedback_id'])
                ->where('status', 1)
                ->find();

            if ($exists) {
                Db::rollback();
                return $this->error('该反馈已被分配', 422);
            }

            $adminId = $this->getAdminId();

            // 创建分配记录
            FeedbackAssignModel::create([
                'feedback_id' => (int) $params['feedback_id'],
                'assigned_by' => $adminId,
                'assigned_to' => (int) $params['assigned_to'],
                'status'      => 1,
                'note'        => $params['note'] ?? null,
            ]);

            // 记录分配日志
            FeedbackLog::create([
                'feedback_id' => (int) $params['feedback_id'],
                'admin_id'    => $adminId,
                'action'      => 'assign',
                'content'     => '分配反馈给管理员ID: ' . $params['assigned_to'],
            ]);

            Db::commit();
            return $this->success([], '分配成功');
        } catch (\Throwable $e) {
            Db::rollback();
            return $this->respondSystemException('feedback_assign_create', $e, '分配失败');
        }
    }

    /**
     * 更新分配状态
     * 路由：POST feedback/assign/:id/status
     */
    public function updateStatus(Request $request, int $id)
    {
        if (!$this->hasAdminPermission('content_manage')) {
            return $this->error('无权限更新分配状态', 403);
        }

        $status = $request->post('status');
        if ($status === null || $status === '') {
            return $this->error('参数错误：status 不能为空', 422);
        }

        $assign = FeedbackAssignModel::find($id);
        if (!$assign) {
            return $this->error('分配记录不存在', 404);
        }

        $oldStatus = $assign->status;

        Db::startTrans();
        try {
            $assign->status = (int) $status;

            if ((int) $status === 2) {
                $assign->completed_at = date('Y-m-d H:i:s');
            }

            $assign->save();

            // 记录状态变更日志
            FeedbackLog::create([
                'feedback_id' => $assign->feedback_id,
                'admin_id'    => $this->getAdminId(),
                'action'      => 'status',
                'content'     => '更新分配状态',
                'old_value'   => (string) $oldStatus,
                'new_value'   => (string) $status,
            ]);

            Db::commit();
            return $this->success([], '更新成功');
        } catch (\Throwable $e) {
            Db::rollback();
            return $this->respondSystemException('feedback_assign_status', $e, '更新失败');
        }
    }

    /**
     * 获取我的待处理分配
     */
    public function myAssignments()
    {
        if (!$this->hasAdminPermission('feedback_view')) {
            return $this->error('无权限查看分配列表', 403);
        }

        try {
            $adminId = $this->getAdminId();

            $list = FeedbackAssignModel::with(['feedback'])
                ->where('assigned_to', $adminId)
                ->where('status', 1)
                ->order('created_at', 'desc')
                ->limit(10)
                ->select();

            return $this->success($list, '获取成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('feedback_assign_my', $e, '获取我的分配失败');
        }
    }
}
