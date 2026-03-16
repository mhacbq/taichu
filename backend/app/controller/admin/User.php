<?php

declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\service\AdminStatsService;
use think\Request;

/**
 * 后台用户管理控制器
 */
class User extends BaseController
{
    /**
     * 获取用户列表
     */
    public function index()
    {
        try {
            $params = $this->request->get();
            $data = AdminStatsService::getUserList($params);
            return $this->success($data);
        } catch (\Exception $e) {
            return $this->error('获取用户列表失败：' . $e->getMessage());
        }
    }

    /**
     * 获取用户详情
     */
    public function detail(int $id)
    {
        try {
            $user = \app\model\User::find($id);
            if (!$user) {
                return $this->error('用户不存在');
            }

            // 获取用户统计信息
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
        } catch (\Exception $e) {
            return $this->error('获取用户详情失败：' . $e->getMessage());
        }
    }

    /**
     * 调整用户积分
     */
    public function adjustPoints()
    {
        try {
            $data = $this->request->post();
            
            // 验证参数
            if (empty($data['user_id']) || !isset($data['points'])) {
                return $this->error('参数错误');
            }

            $adminId = $this->request->adminId ?? 0;
            $reason = $data['reason'] ?? '管理员调整';

            AdminStatsService::adjustUserPoints(
                (int)$data['user_id'],
                (int)$data['points'],
                $reason,
                $adminId
            );

            return $this->success([], '积分调整成功');
        } catch (\Exception $e) {
            return $this->error('调整积分失败：' . $e->getMessage());
        }
    }

    /**
     * 禁用/启用用户
     */
    public function toggleStatus(int $id)
    {
        try {
            $user = \app\model\User::find($id);
            if (!$user) {
                return $this->error('用户不存在');
            }

            $newStatus = $user->status == 1 ? 0 : 1;
            $user->status = $newStatus;
            $user->save();

            return $this->success(['status' => $newStatus], '状态更新成功');
        } catch (\Exception $e) {
            return $this->error('更新状态失败：' . $e->getMessage());
        }
    }
}
