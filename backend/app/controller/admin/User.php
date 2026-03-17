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

            $stats = [
                'bazi_count' => \app\model\BaziRecord::where('user_id', $id)->count(),
                'tarot_count' => \app\model\TarotRecord::where('user_id', $id)->count(),
                'liuyao_count' => \think\facade\Db::table('tc_liuyao_record')->where('user_id', $id)->count(),
                'points_records' => \app\model\PointsRecord::where('user_id', $id)
                    ->order('created_at', 'desc')
                    ->limit(10)
                    ->select(),
                'orders' => \think\facade\Db::table('tc_vip_order')
                    ->where('user_id', $id)
                    ->order('created_at', 'desc')
                    ->limit(10)
                    ->select(),
            ];

            return $this->success([
                'user' => $user,
                'stats' => $stats,
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

        try {
            $data = $this->request->post();
            $userId = filter_var($data['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            if ($userId === false || !isset($data['points']) || !is_numeric($data['points'])) {
                return $this->error('参数错误');
            }

            $reason = trim((string) ($data['reason'] ?? '管理员调整')) ?: '管理员调整';

            AdminStatsService::adjustUserPoints(
                (int) $userId,
                (int) $data['points'],
                $reason,
                $this->getAdminId()
            );

            return $this->success([], '积分调整成功');
        } catch (\Throwable $e) {
            Log::error('后台调整用户积分失败', [
                'admin_id' => $this->getAdminId(),
                'user_id' => $data['user_id'] ?? null,
                'error' => $e->getMessage(),
            ]);
            return $this->error('调整积分失败，请稍后重试', 500);
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
