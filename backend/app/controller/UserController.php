<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\User;
use app\model\PointsRecord;
use think\facade\Log;

/**
 * 用户管理控制器
 */
class UserController extends BaseController
{
    /**
     * 更新用户状态
     */
    public function updateStatus($id)
    {
        try {
            $user = User::find($id);
            if (!$user) {
                return $this->error('用户不存在', 404);
            }

            $status = $this->request->post('status', 'normal');
            $user->status = $status;
            $user->save();

            return $this->success(['id' => $id, 'status' => $status], '用户状态更新成功');
        } catch (\Throwable $e) {
            Log::error('更新用户状态失败', [
                'user_id' => $id,
                'error' => $e->getMessage()
            ]);
            return $this->error('更新用户状态失败', 500);
        }
    }

    /**
     * 调整用户积分
     */
    public function adjustPoints($id)
    {
        try {
            $user = User::find($id);
            if (!$user) {
                return $this->error('用户不存在', 404);
            }

            $amount = (int) $this->request->post('amount', 0);
            $reason = trim($this->request->post('reason', ''));

            if ($amount === 0) {
                return $this->error('调整金额不能为0', 400);
            }

            if (empty($reason)) {
                return $this->error('调整原因不能为空', 400);
            }

            // 调整积分
            $user->points += $amount;
            if ($user->points < 0) {
                return $this->error('积分不足', 400);
            }
            $user->save();

            // 记录积分变动
            PointsRecord::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'type' => $amount > 0 ? 'manual_add' : 'manual_sub',
                'description' => $reason,
                'balance_after' => $user->points,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            return $this->success([
                'user_id' => $user->id,
                'amount' => $amount,
                'balance' => $user->points
            ], '积分调整成功');
        } catch (\Throwable $e) {
            Log::error('调整用户积分失败', [
                'user_id' => $id,
                'error' => $e->getMessage()
            ]);
            return $this->error('调整积分失败', 500);
        }
    }

    /**
     * 显示用户详情
     */
    public function show($id)
    {
        try {
            $user = User::find($id);
            if (!$user) {
                return $this->error('用户不存在', 404);
            }

            return $this->success($user->hidden(['password', 'openid', 'unionid']));
        } catch (\Throwable $e) {
            Log::error('获取用户详情失败', [
                'user_id' => $id,
                'error' => $e->getMessage()
            ]);
            return $this->error('获取用户详情失败', 500);
        }
    }
}
