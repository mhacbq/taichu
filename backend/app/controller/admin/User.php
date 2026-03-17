<?php

declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\service\AdminStatsService;
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

            $pointsRecords = \app\model\PointsRecord::where('user_id', $id)
                ->order('created_at', 'desc')
                ->limit(20)
                ->select()
                ->toArray();

            $stats = [
                'bazi_count' => \app\model\BaziRecord::where('user_id', $id)->count(),
                'tarot_count' => \app\model\TarotRecord::where('user_id', $id)->count(),
                'liuyao_count' => \think\facade\Db::table('tc_liuyao_record')->where('user_id', $id)->count(),
                'points_records' => $pointsRecords,
                'vip_orders' => \think\facade\Db::table('tc_vip_order')
                    ->where('user_id', $id)
                    ->order('created_at', 'desc')
                    ->limit(10)
                    ->select()
                    ->toArray(),
                'recharge_orders' => \think\facade\Db::table('tc_recharge_order')
                    ->where('user_id', $id)
                    ->order('created_at', 'desc')
                    ->limit(10)
                    ->select()
                    ->toArray(),
                'points_summary' => [
                    'current_balance' => (int) ($user->points ?? 0),
                    'total_adjust_records' => count($pointsRecords),
                    'can_adjust' => $this->hasAdminPermission('points_adjust'),
                ],
            ];

            return $this->success([
                'user' => $user,
                'stats' => $stats,
                'actions' => [
                    'can_adjust_points' => $this->hasAdminPermission('points_adjust'),
                ],
            ]);
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

        try {
            $userId = filter_var($data['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $points = filter_var($data['points'] ?? null, FILTER_VALIDATE_INT);
            if ($userId === false || $points === false) {
                return $this->error('用户ID和积分调整值必须为整数', 400);
            }
            if ($points === 0) {
                return $this->error('积分调整值不能为 0', 400);
            }

            $reason = mb_substr(trim((string) ($data['reason'] ?? '管理员调整')) ?: '管理员调整', 0, 255);
            $result = AdminStatsService::adjustUserPoints(
                (int) $userId,
                (int) $points,
                $reason,
                $this->getAdminId()
            );

            return $this->success($result, '积分调整成功');
        } catch (\Throwable $e) {
            Log::error('后台调整用户积分失败', [
                'admin_id' => $this->getAdminId(),
                'user_id' => $data['user_id'] ?? null,
                'points' => $data['points'] ?? null,
                'error' => $e->getMessage(),
            ]);
            return $this->error(in_array($e->getMessage(), ['用户不存在', '积分不足'], true) ? $e->getMessage() : '调整积分失败，请稍后重试', in_array($e->getMessage(), ['用户不存在'], true) ? 404 : (in_array($e->getMessage(), ['积分不足'], true) ? 400 : 500));
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

            $newStatus = $user->status == 1 ? 0 : 1;
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
}
