<?php

declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\service\AdminStatsService;
use app\service\SchemaInspector;
use think\Request;
use think\facade\Db;


/**
 * 后台用户管理控制器
 */
class User extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    /**
     * 获取用户列表
     */
    public function index()
    {
        if (!$this->hasAdminPermission('user_view')) {
            return $this->error('无权限查看用户列表', 403);
        }

        try {
            $params = $this->request->get();
            $data = AdminStatsService::getUserList($params);
            return $this->success($data);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_user_index', $e, '获取用户列表失败，请稍后重试', [
                'params' => $params ?? [],
            ]);
        }
    }

    /**
     * 获取用户详情
     */
    public function detail(int $id)
    {
        if (!$this->hasAdminPermission('user_view')) {
            return $this->error('无权限查看用户详情', 403);
        }

        try {
            $user = \app\model\User::find($id);
            if (!$user) {
                return $this->error('用户不存在', 404);
            }

            $userData = $user->toArray();
            $pointsRecords = SchemaInspector::tableExists('tc_points_record')
                ? \app\model\PointsRecord::where('user_id', $id)
                    ->order('created_at', 'desc')
                    ->limit(20)
                    ->select()
                    ->toArray()
                : [];

            $liuyaoTable = $this->resolveFirstExistingTable(['tc_liuyao_record', 'liuyao_records']);
            $vipOrderTable = $this->resolveFirstExistingTable(['tc_vip_order', 'vip_orders']);
            $rechargeOrderTable = $this->resolveFirstExistingTable(['tc_recharge_order']);

            $baziCount = SchemaInspector::tableExists('tc_bazi_record')
                ? \app\model\BaziRecord::where('user_id', $id)->count()
                : 0;
            $tarotCount = SchemaInspector::tableExists('tc_tarot_record')
                ? \app\model\TarotRecord::where('user_id', $id)->count()
                : 0;
            $liuyaoCount = $liuyaoTable !== null
                ? Db::table($liuyaoTable)->where('user_id', $id)->count()
                : 0;
            $vipOrders = $vipOrderTable !== null
                ? Db::table($vipOrderTable)->where('user_id', $id)->order('created_at', 'desc')->limit(10)->select()->toArray()
                : [];
            $rechargeOrders = $rechargeOrderTable !== null
                ? Db::table($rechargeOrderTable)->where('user_id', $id)->order('created_at', 'desc')->limit(10)->select()->toArray()
                : [];
            $canAdjustPoints = $this->hasAdminPermission('points_adjust');


            $stats = [
                'bazi_count' => $baziCount,
                'tarot_count' => $tarotCount,
                'liuyao_count' => $liuyaoCount,
                'points_records' => $pointsRecords,
                'vip_orders' => $vipOrders,
                'recharge_orders' => $rechargeOrders,
                'points_summary' => [
                    'current_balance' => (int) ($userData['points'] ?? 0),
                    'total_adjust_records' => count($pointsRecords),
                    'can_adjust' => $canAdjustPoints,
                ],
            ];

            $payload = array_merge($userData, [
                'username' => $this->resolveDisplayUsername($userData, $id),
                'email' => (string) ($userData['email'] ?? ''),
                'bazi_count' => $baziCount,
                'tarot_count' => $tarotCount,
                'liuyao_count' => $liuyaoCount,
                'points_records' => $pointsRecords,
                'vip_orders' => $vipOrders,
                'recharge_orders' => $rechargeOrders,
                'can_adjust_points' => $canAdjustPoints,
                'user' => $userData,
                'stats' => $stats,
                'actions' => [
                    'can_adjust_points' => $canAdjustPoints,
                ],
            ]);

            return $this->success($payload);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_user_detail', $e, '获取用户详情失败，请稍后重试', [
                'user_id' => $id,
            ]);
        }
    }

    /**
     * 调整用户积分
     */
    public function adjustPoints()
    {
        if (!$this->hasAdminPermission('points_adjust')) {
            return $this->error('无权限调整用户积分', 403);
        }

        $data = $this->request->post();
        $normalizedPoints = null;

        try {
            $userId = filter_var($data['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $normalizedPoints = $this->resolvePointsDelta($data);
            if ($userId === false || $normalizedPoints === null) {
                return $this->error('积分调整参数无效，请传入 points 或 type/amount', 400);
            }
            if ($normalizedPoints === 0) {
                return $this->error('积分调整值不能为 0', 400);
            }

            $reason = mb_substr(trim((string) ($data['reason'] ?? '管理员调整')) ?: '管理员调整', 0, 255);
            $result = AdminStatsService::adjustUserPoints(
                (int) $userId,
                $normalizedPoints,
                $reason,
                $this->getAdminId()
            );

            $result['current_points'] = $result['after_points'] ?? null;

            return $this->success($result, '积分调整成功');
        } catch (\Throwable $e) {
            $context = [
                'user_id' => $data['user_id'] ?? null,
                'points' => $normalizedPoints,
                'reason' => $data['reason'] ?? '',
            ];

            if ($e->getMessage() === '用户不存在') {
                return $this->respondBusinessException($e, 'admin_adjust_points_user_not_found', '用户不存在', 404, $context);
            }

            if ($e->getMessage() === '积分不足') {
                return $this->respondBusinessException($e, 'admin_adjust_points_insufficient', '积分不足', 400, $context);
            }

            return $this->respondSystemException('admin_adjust_points', $e, '调整积分失败，请稍后重试', $context);
        }
    }

    /**
     * 禁用/启用用户
     */
    public function toggleStatus(int $id)
    {
        if (!$this->hasAdminPermission('user_edit')) {
            return $this->error('无权限修改用户状态', 403);
        }

        try {
            $user = \app\model\User::find($id);
            if (!$user) {
                return $this->error('用户不存在', 404);
            }

            $requestedStatus = $this->request->put('status', null);
            if ($requestedStatus !== null && $requestedStatus !== '') {
                $newStatus = filter_var($requestedStatus, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
                if ($newStatus === false || !in_array($newStatus, [0, 1, 2], true)) {
                    return $this->error('状态值无效', 400);
                }
            } else {
                $newStatus = (int) ($user->status == 1 ? 0 : 1);
            }

            $oldStatus = (int) ($user->status ?? 0);
            $user->status = $newStatus;
            $user->save();

            $this->logOperation('toggle_user_status', 'user', [
                'target_id' => $id,
                'target_type' => 'user',
                'detail' => sprintf('更新用户状态: %s -> %s', $oldStatus, $newStatus),
                'before_data' => ['status' => $oldStatus],
                'after_data' => ['status' => $newStatus],
            ]);

            return $this->success(['status' => $newStatus], '状态更新成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_toggle_user_status', $e, '更新状态失败，请稍后重试', [
                'user_id' => $id,
                'requested_status' => $this->request->put('status', null),
            ]);
        }
    }

    /**
     * 批量更新用户状态
     */
    public function batchUpdateStatus(Request $request)
    {
        if (!$this->hasAdminPermission('user_edit')) {
            return $this->error('无权限批量编辑用户', 403);
        }

        $ids = $request->put('ids', []);
        $status = $request->put('status');

        try {
            if (!is_array($ids) || empty($ids)) {
                return $this->error('用户ID列表不能为空', 400);
            }
            if (!in_array($status, [0, 1, 2], true)) {
                return $this->error('状态值无效，必须是0(禁用)、1(正常)或2(待验证)', 400);
            }

            $userIds = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $value): bool => $value > 0)));
            if (empty($userIds)) {
                return $this->error('用户ID列表无效', 400);
            }

            $existingCount = \app\model\User::whereIn('id', $userIds)->count();
            if ($existingCount === 0) {
                return $this->error('未找到可更新的用户', 404);
            }

            \app\model\User::whereIn('id', $userIds)->update(['status' => $status]);

            $this->logOperation('batch_update_status', 'user', [
                'detail' => '批量更新用户状态为: ' . $status,
                'after_data' => [
                    'user_ids' => $userIds,
                    'status' => $status,
                    'matched_count' => $existingCount,
                ],
            ]);

            return $this->success([
                'updated_count' => $existingCount,
                'status' => $status,
                'user_ids' => $userIds,
            ], '批量操作成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('user_batch_update_status', $e, '批量操作失败，请稍后重试', [
                'ids' => $ids,
                'status' => $status,
            ]);
        }
    }

    /**
     * 兼容 points 与 type/amount 两套积分调整入参
     */

    protected function resolvePointsDelta(array $data): ?int
    {
        $directPoints = filter_var($data['points'] ?? null, FILTER_VALIDATE_INT);
        if ($directPoints !== false && $directPoints !== null) {
            return (int) $directPoints;
        }

        $type = strtolower(trim((string) ($data['type'] ?? '')));
        $amount = filter_var($data['amount'] ?? null, FILTER_VALIDATE_INT);
        if ($amount === false || $amount === null || (int) $amount === 0) {
            return null;
        }

        $amount = abs((int) $amount);
        if (in_array($type, ['add', 'increase', 'plus'], true)) {
            return $amount;
        }

        if (in_array($type, ['sub', 'subtract', 'reduce', 'minus'], true)) {
            return -$amount;
        }

        return null;
    }

    /**
     * 统一用户名展示口径，避免手机号误写入“用户名”字段
     */
    protected function resolveDisplayUsername(array $userData, int $userId): string
    {
        foreach (['username', 'nickname'] as $field) {
            $value = trim((string) ($userData[$field] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '用户#' . $userId;
    }

    /**
     * 返回首个存在的数据表名
     */
    protected function resolveFirstExistingTable(array $tables): ?string
    {
        foreach ($tables as $table) {
            if (SchemaInspector::tableExists((string) $table)) {
                return (string) $table;
            }
        }

        return null;
    }
}

