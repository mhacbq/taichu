<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\model\Feedback as FeedbackModel;
use think\Request;
use think\facade\Db;

/**
 * 后台反馈管理控制器
 */
class Feedback extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    private const CATEGORY_FEEDBACK = 'feedback_category';

    /**
     * 反馈列表
     */
    public function index(Request $request)
    {
        if (!$this->checkPermission('feedback_view')) {
            return $this->error('无权限查看反馈列表', 403);
        }

        try {
            $pagination = $this->getPaginationParams('page', 'pageSize', 20, 100);
            $type = trim((string) $request->get('type', ''));
            $status = trim((string) $request->get('status', ''));
            $normalizedStatus = FeedbackModel::normalizeStatusValue($status);

            $query = FeedbackModel::order('id', 'desc');
            if ($type !== '') {
                $query->where('type', $type);
            }
            if ($status !== '' && $normalizedStatus !== null) {
                $statusCode = FeedbackModel::normalizeStatusCode($normalizedStatus);
                $query->where(function ($subQuery) use ($normalizedStatus, $statusCode) {
                    $subQuery->where('status', $normalizedStatus)
                        ->whereOr('status', $statusCode);
                });
            }

            $total = (clone $query)->count();
            $rows = $query->page($pagination['page'], $pagination['pageSize'])->select()->toArray();

            return $this->success([
                'list' => array_map([$this, 'formatFeedbackRow'], $rows),
                'total' => $total,
                'page' => $pagination['page'],
                'pageSize' => $pagination['pageSize'],
            ], '获取成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('feedback_index', $e, '获取反馈列表失败，请稍后重试', [
                'type' => $type ?? '',
                'status' => $status ?? '',
            ]);
        }
    }

    /**
     * 反馈详情
     */
    public function detail(int $id)
    {
        if (!$this->checkPermission('feedback_view')) {
            return $this->error('无权限查看反馈详情', 403);
        }

        try {
            $feedback = FeedbackModel::find($id);
            if (!$feedback) {
                return $this->error('反馈不存在', 404);
            }

            return $this->success($this->formatFeedbackRow($feedback->toArray()), '获取成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('feedback_detail', $e, '获取反馈详情失败，请稍后重试', [
                'feedback_id' => $id,
            ]);
        }
    }

    /**
     * 回复反馈
     */
    public function reply(Request $request, int $id)
    {
        if (!$this->checkPermission('content_manage')) {
            return $this->error('无权限回复反馈', 403);
        }

        $reply = trim((string) $request->post('reply', ''));
        $statusCode = trim((string) $request->post('status', 'resolved')) ?: 'resolved';
        $status = FeedbackModel::normalizeStatusValue($statusCode, FeedbackModel::STATUS_RESOLVED);

        try {
            $feedback = FeedbackModel::find($id);
            if (!$feedback) {
                return $this->error('反馈不存在', 404);
            }

            $before = $this->formatFeedbackRow($feedback->toArray());
            $feedback->reply = $reply;
            $feedback->status = $status;
            $feedback->replied_at = date('Y-m-d H:i:s');
            $feedback->save();

            // 记录反馈操作日志
            \app\model\FeedbackLog::create([
                'feedback_id' => $id,
                'admin_id' => $this->getAdminId(),
                'action' => 'reply',
                'content' => '回复用户反馈：' . mb_substr($reply, 0, 50) . (mb_strlen($reply) > 50 ? '...' : ''),
            ]);

            $this->logOperation('update', 'feedback', [
                'target_id' => $id,
                'target_type' => 'feedback',
                'detail' => '回复用户反馈',
                'before_data' => $before,
                'after_data' => $this->formatFeedbackRow($feedback->toArray()),
            ]);

            return $this->success(null, '回复成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('feedback_reply', $e, '回复反馈失败，请稍后重试', [
                'feedback_id' => $id,
                'status' => $status,
            ]);
        }
    }

    /**
     * 更新反馈状态
     */
    public function updateStatus(Request $request, int $id)
    {
        if (!$this->checkPermission('content_manage')) {
            return $this->error('无权限修改反馈状态', 403);
        }

        $statusInput = $request->put('status', '');
        if ($statusInput === '') {
            return $this->error('反馈状态不能为空', 422);
        }

        $status = FeedbackModel::normalizeStatusValue($statusInput);
        if ($status === null) {
            return $this->error('反馈状态无效', 422);
        }

        try {
            $feedback = FeedbackModel::find($id);
            if (!$feedback) {
                return $this->error('反馈不存在', 404);
            }

            $before = $this->formatFeedbackRow($feedback->toArray());
            $oldStatus = $feedback->status;
            $feedback->status = $status;
            $feedback->save();

            // 记录状态变更日志
            \app\model\FeedbackLog::create([
                'feedback_id' => $id,
                'admin_id' => $this->getAdminId(),
                'action' => 'status',
                'content' => '更新反馈状态',
                'old_value' => $oldStatus,
                'new_value' => $status,
            ]);

            $this->logOperation('update', 'feedback', [
                'target_id' => $id,
                'target_type' => 'feedback',
                'detail' => '更新反馈状态为：' . $status,
                'before_data' => $before,
                'after_data' => $this->formatFeedbackRow($feedback->toArray()),
            ]);

            return $this->success(null, '更新成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('feedback_update_status', $e, '更新反馈状态失败，请稍后重试', [
                'feedback_id' => $id,
                'status' => $status,
            ]);
        }
    }

    /**
     * 删除反馈
     */
    public function delete(int $id)
    {
        if (!$this->checkPermission('content_manage')) {
            return $this->error('无权限删除反馈', 403);
        }

        try {
            $feedback = FeedbackModel::find($id);
            if (!$feedback) {
                return $this->error('反馈不存在', 404);
            }

            $before = $this->formatFeedbackRow($feedback->toArray());
            $feedback->delete();

            $this->logOperation('delete', 'feedback', [
                'target_id' => $id,
                'target_type' => 'feedback',
                'detail' => '删除反馈记录',
                'before_data' => $before,
            ]);

            return $this->success(null, '删除成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('feedback_delete', $e, '删除反馈失败，请稍后重试', [
                'feedback_id' => $id,
            ]);
        }
    }

    /**
     * 反馈分类列表
     */
    public function categories()
    {
        if (!$this->checkPermission('feedback_view')) {
            return $this->error('无权限查看反馈分类', 403);
        }

        try {
            $rows = Db::name('system_config')
                ->where('category', self::CATEGORY_FEEDBACK)
                ->order('sort_order', 'asc')
                ->order('id', 'desc')
                ->select()
                ->toArray();

            return $this->success(array_map([$this, 'formatCategoryRow'], $rows), '获取成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('feedback_categories', $e, '获取反馈分类失败，请稍后重试');
        }
    }

    /**
     * 保存反馈分类
     */
    public function saveCategory(Request $request)
    {
        if (!$this->checkPermission('content_manage')) {
            return $this->error('无权限管理反馈分类', 403);
        }

        $data = $request->post();
        $id = (int) ($data['id'] ?? 0);
        $name = trim((string) ($data['name'] ?? ''));
        $code = trim((string) ($data['code'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $sort = max(0, (int) ($data['sort'] ?? 0));

        if ($name === '' || $code === '') {
            return $this->error('分类名称和标识不能为空', 422);
        }
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $code)) {
            return $this->error('分类标识仅支持字母、数字、中划线和下划线', 422);
        }

        try {
            $existing = null;
            if ($id > 0) {
                $existing = Db::name('system_config')
                    ->where('id', $id)
                    ->where('category', self::CATEGORY_FEEDBACK)
                    ->find();
                if (!$existing) {
                    return $this->error('反馈分类不存在', 404);
                }
            }

            $duplicateQuery = Db::name('system_config')
                ->where('category', self::CATEGORY_FEEDBACK)
                ->where('config_key', $this->buildCategoryConfigKey($code));
            if ($id > 0) {
                $duplicateQuery->where('id', '<>', $id);
            }
            if ($duplicateQuery->find()) {
                return $this->error('分类标识已存在', 422);
            }

            $payload = [
                'name' => $name,
                'code' => $code,
                'description' => $description,
                'sort' => $sort,
            ];
            $rowData = [
                'config_key' => $this->buildCategoryConfigKey($code),
                'config_value' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'config_type' => 'json',
                'description' => $name,
                'category' => self::CATEGORY_FEEDBACK,
                'is_editable' => 1,
                'sort_order' => $sort,
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($existing) {
                Db::name('system_config')->where('id', $id)->update($rowData);
            } else {
                $rowData['created_at'] = date('Y-m-d H:i:s');
                $id = (int) Db::name('system_config')->insertGetId($rowData);
            }

            $saved = Db::name('system_config')->where('id', $id)->find();
            $this->logOperation($existing ? 'update' : 'create', 'feedback', [
                'target_id' => $id,
                'target_type' => 'feedback_category',
                'detail' => ($existing ? '更新' : '新增') . '反馈分类：' . $name,
                'before_data' => $existing ? $this->formatCategoryRow($existing) : null,
                'after_data' => $saved ? $this->formatCategoryRow($saved) : $payload,
            ]);

            return $this->success(['id' => $id], '保存成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('feedback_save_category', $e, '保存反馈分类失败，请稍后重试', [
                'category_id' => $id,
                'code' => $code,
            ]);
        }
    }

    /**
     * 删除反馈分类
     */
    public function deleteCategory(int $id)
    {
        if (!$this->checkPermission('content_manage')) {
            return $this->error('无权限删除反馈分类', 403);
        }

        try {
            $existing = Db::name('system_config')
                ->where('id', $id)
                ->where('category', self::CATEGORY_FEEDBACK)
                ->find();
            if (!$existing) {
                return $this->error('反馈分类不存在', 404);
            }

            Db::name('system_config')->where('id', $id)->delete();
            $this->logOperation('delete', 'feedback', [
                'target_id' => $id,
                'target_type' => 'feedback_category',
                'detail' => '删除反馈分类：' . ($this->formatCategoryRow($existing)['name'] ?? $id),
                'before_data' => $this->formatCategoryRow($existing),
            ]);

            return $this->success(null, '删除成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('feedback_delete_category', $e, '删除反馈分类失败，请稍后重试', [
                'category_id' => $id,
            ]);
        }
    }

    /**
     * 获取反馈处理日志
     */
    public function logs(Request $request)
    {
        if (!$this->checkPermission('feedback_view')) {
            return $this->error('无权限查看反馈日志', 403);
        }

        $feedbackId = $request->get('feedback_id');
        
        if (!$feedbackId) {
            return $this->error('缺少反馈ID', 400);
        }

        try {
            $logs = \app\model\FeedbackLog::with(['feedback', 'admin'])
                ->where('feedback_id', $feedbackId)
                ->order('created_at', 'desc')
                ->select()
                ->toArray();

            return $this->success($logs, '获取成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('feedback_logs', $e, '获取处理日志失败', [
                'feedback_id' => $feedbackId,
            ]);
        }
    }

    /**
     * Dashboard 待处理反馈摘要
     */
    public function pendingSummary()
    {
        if (!$this->checkPermission('feedback_view')) {
            return $this->error('无权限查看反馈', 403);
        }

        try {
            $query = $this->buildPendingFeedbackQuery();
            $count = (clone $query)->count();
            $rows = $query->order('created_at', 'desc')->limit(5)->select()->toArray();

            return $this->success([
                'count' => $count,
                'list' => array_map([$this, 'formatFeedbackRow'], $rows),
            ], '获取成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('feedback_pending_summary', $e, '获取待处理反馈失败，请稍后重试');
        }
    }

    /**
     * 构建待处理反馈查询
     */
    protected function buildPendingFeedbackQuery()
    {
        return FeedbackModel::where(function ($query) {
            $query->where('status', FeedbackModel::STATUS_PENDING)
                ->whereOr('status', FeedbackModel::normalizeStatusCode(FeedbackModel::STATUS_PENDING));
        });
    }

    /**
     * 格式化反馈记录
     */
    protected function formatFeedbackRow(array $row): array
    {
        $images = $this->normalizeImages($row['images'] ?? []);

        $statusValue = FeedbackModel::normalizeStatusValue($row['status'] ?? null, FeedbackModel::STATUS_PENDING);

        return [
            'id' => (int) ($row['id'] ?? 0),
            'user_id' => (int) ($row['user_id'] ?? 0),
            'type' => (string) ($row['type'] ?? ''),
            'title' => trim((string) ($row['title'] ?? '')),
            'content' => trim((string) ($row['content'] ?? '')),
            'contact' => (string) ($row['contact'] ?? ''),
            'images' => $images,
            'status' => FeedbackModel::normalizeStatusCode($statusValue),
            'status_value' => $statusValue,
            'reply' => (string) ($row['reply'] ?? ''),
            'replied_at' => $row['replied_at'] ?? null,
            'created_at' => (string) ($row['created_at'] ?? ''),
            'updated_at' => (string) ($row['updated_at'] ?? ''),
        ];
    }

    /**
     * 归一化反馈图片字段
     */
    protected function normalizeImages(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('strval', $value)));
        }

        if (!is_string($value)) {
            return [];
        }

        $value = trim($value);
        if ($value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return array_values(array_filter(array_map('strval', $decoded)));
        }

        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }

    /**
     * 格式化分类记录
     */
    protected function formatCategoryRow(array $row): array
    {
        $payload = $this->decodeConfigJson((string) ($row['config_value'] ?? ''));
        $code = (string) ($payload['code'] ?? $this->extractCategoryCode((string) ($row['config_key'] ?? '')));
        $name = (string) ($payload['name'] ?? ($row['description'] ?? $code));

        return [
            'id' => (int) ($row['id'] ?? 0),
            'name' => $name,
            'code' => $code,
            'description' => (string) ($payload['description'] ?? ''),
            'sort' => (int) ($payload['sort'] ?? ($row['sort_order'] ?? 0)),
        ];
    }

    /**
     * 解析配置 JSON
     */
    protected function decodeConfigJson(string $value): array
    {
        if ($value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * 生成反馈分类配置键
     */
    protected function buildCategoryConfigKey(string $code): string
    {
        return 'feedback_category_' . strtolower($code);
    }

    /**
     * 从配置键中提取分类标识
     */
    protected function extractCategoryCode(string $configKey): string
    {
        foreach (['feedback_category_', 'fb_cat_'] as $prefix) {
            if (str_starts_with($configKey, $prefix)) {
                return substr($configKey, strlen($prefix));
            }
        }

        return $configKey;
    }
}
