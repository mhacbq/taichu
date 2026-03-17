<?php

declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\service\AdminStatsService;
use think\facade\Db;
use think\facade\Log;

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
            Log::error('后台获取用户列表失败', [
                'admin_id' => $this->getAdminId(),
                'error' => $e->getMessage(),
            ]);
            return $this->error('获取用户列表失败，请稍后重试', 500);
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
            $pointsRecords = \app\model\PointsRecord::where('user_id', $id)
                ->order('created_at', 'desc')
                ->limit(20)
                ->select()
                ->toArray();

            $baziCount = \app\model\BaziRecord::where('user_id', $id)->count();
            $tarotCount = \app\model\TarotRecord::where('user_id', $id)->count();
            $liuyaoCount = $this->tableExists('tc_liuyao_record')
                ? Db::table('tc_liuyao_record')->where('user_id', $id)->count()
                : 0;
            $vipOrders = $this->tableExists('tc_vip_order')
                ? Db::table('tc_vip_order')->where('user_id', $id)->order('created_at', 'desc')->limit(10)->select()->toArray()
                : [];
            $rechargeOrders = $this->tableExists('tc_recharge_order')
                ? Db::table('tc_recharge_order')->where('user_id', $id)->order('created_at', 'desc')->limit(10)->select()->toArray()
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
                'username' => (string) (($userData['phone'] ?? '') ?: ('用户#' . $id)),
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
            Log::error('后台获取用户详情失败', [
                'admin_id' => $this->getAdminId(),
                'user_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return $this->error('获取用户详情失败，请稍后重试', 500);
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
            Log::error('后台调整用户积分失败', [
                'admin_id' => $this->getAdminId(),
                'user_id' => $data['user_id'] ?? null,
                'points' => $normalizedPoints,
                'error' => $e->getMessage(),
            ]);

            $statusCode = in_array($e->getMessage(), ['用户不存在'], true)
                ? 404
                : (in_array($e->getMessage(), ['积分不足'], true) ? 400 : 500);
            $message = in_array($e->getMessage(), ['用户不存在', '积分不足'], true)
                ? $e->getMessage()
                : '调整积分失败，请稍后重试';

            return $this->error($message, $statusCode);
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

            $user->status = $newStatus;
            $user->save();

            return $this->success(['status' => $newStatus], '状态更新成功');
        } catch (\Throwable $e) {
            Log::error('后台更新用户状态失败', [
                'admin_id' => $this->getAdminId(),
                'user_id' => $id,
                'error' => $e->getMessage(),
            ]);
            return $this->error('更新状态失败，请稍后重试', 500);
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
     * 判断数据表是否存在
     */
    protected function tableExists(string $table): bool
    {
        $escapedTable = addslashes($table);
        return !empty(Db::query("SHOW TABLES LIKE '{$escapedTable}'"));
    }
}
