<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\Feedback as FeedbackModel;
use think\Request;
use think\facade\Db;

/**
 * 反馈管理控制器
 */
class FeedbackManage extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    /**
     * 获取反馈列表
     */
    public function getList()
    {
        if (!$this->hasAdminPermission('feedback_view')) {
            return $this->error('无权限查看反馈', 403);
        }

        $params = $this->request->get();
        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['page_size'] ?? 20);
        $status = $params['status'] ?? '';
        $type = $params['type'] ?? '';

        try {
            $query = FeedbackModel::alias('f')
                ->leftJoin('tc_user u', 'f.user_id = u.id')
                ->field('f.*, u.username, u.nickname, u.phone');

            if ($status) {
                $normalizedStatus = FeedbackModel::normalizeStatusValue($status);
                $statusCode = FeedbackModel::normalizeStatusCode($normalizedStatus);
                $query->where('f.status', $statusCode);
            }

            if ($type) {
                $query->where('f.type', $type);
            }

            $total = $query->count();
            $list = $query->order('f.id', 'desc')
                ->limit($pageSize)
                ->page($page)
                ->select()
                ->toArray();

            // 格式化数据
            $formattedList = [];
            foreach ($list as $item) {
                $formattedList[] = $this->formatFeedbackRow($item);
            }

            return $this->success([
                'list' => $formattedList,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize
            ]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_feedback_list', $e, '获取反馈列表失败');
        }
    }

    /**
     * 获取反馈详情
     */
    public function getDetail()
    {
        if (!$this->hasAdminPermission('feedback_view')) {
            return $this->error('无权限查看反馈详情', 403);
        }

        $id = $this->request->param('id');

        if (!$id) {
            return $this->error('反馈ID不能为空');
        }

        try {
            $feedback = FeedbackModel::alias('f')
                ->leftJoin('tc_user u', 'f.user_id = u.id')
                ->where('f.id', $id)
                ->field('f.*, u.username, u.nickname, u.phone')
                ->find();

            if (!$feedback) {
                return $this->error('反馈不存在', 404);
            }

            return $this->success($this->formatFeedbackRow($feedback->toArray()));
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_feedback_detail', $e, '获取反馈详情失败');
        }
    }

    /**
     * 删除反馈
     */
    public function delete()
    {
        if (!$this->hasAdminPermission('feedback_delete')) {
            return $this->error('无权限删除反馈', 403);
        }

        $id = $this->request->param('id');

        if (!$id) {
            return $this->error('反馈ID不能为空');
        }

        try {
            $feedback = FeedbackModel::find($id);
            if (!$feedback) {
                return $this->error('反馈不存在', 404);
            }

            $feedback->delete();

            $this->logOperation('delete_feedback', 'feedback', [
                'feedback_id' => $id
            ]);

            return $this->success(null, '删除成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_feedback_delete', $e, '删除反馈失败');
        }
    }

    /**
     * 更新反馈状态
     */
    public function updateStatus()
    {
        if (!$this->hasAdminPermission('feedback_edit')) {
            return $this->error('无权限更新反馈状态', 403);
        }

        $id = $this->request->param('id');
        $status = $this->request->post('status');

        if (!$id) {
            return $this->error('反馈ID不能为空');
        }

        if (!$status) {
            return $this->error('状态不能为空');
        }

        try {
            $normalizedStatus = FeedbackModel::normalizeStatusValue($status);
            $statusCode = FeedbackModel::normalizeStatusCode($normalizedStatus);

            $feedback = FeedbackModel::find($id);
            if (!$feedback) {
                return $this->error('反馈不存在', 404);
            }

            $feedback->status = $statusCode;
            $feedback->save();

            $this->logOperation('update_feedback_status', 'feedback', [
                'feedback_id' => $id,
                'old_status' => $feedback->status,
                'new_status' => $statusCode
            ]);

            return $this->success(null, '更新成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_feedback_update_status', $e, '更新反馈状态失败');
        }
    }

    /**
     * 格式化反馈数据
     */
    protected function formatFeedbackRow(array $row): array
    {
        $statusValue = FeedbackModel::normalizeStatusValue($row['status'] ?? null, FeedbackModel::STATUS_PENDING);
        $statusCode = FeedbackModel::normalizeStatusCode($statusValue);

        return [
            'id' => (int) ($row['id'] ?? 0),
            'user_id' => (int) ($row['user_id'] ?? 0),
            'username' => $row['username'] ?? '',
            'nickname' => $row['nickname'] ?? '',
            'phone' => $row['phone'] ?? '',
            'type' => $row['type'] ?? 'other',
            'title' => $row['title'] ?? '',
            'content' => $row['content'] ?? '',
            'contact' => $row['contact'] ?? '',
            'status' => $statusCode,
            'reply' => $row['reply'] ?? '',
            'reply_time' => $row['reply_time'] ?? null,
            'created_at' => $row['created_at'] ?? null,
            'updated_at' => $row['updated_at'] ?? null
        ];
    }
}
