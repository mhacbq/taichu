<?php

namespace app\Http\Controllers;

use Illuminate\Http\Request;
use app\Models\User;
use app\Models\PointsRecord;
use app\Models\UserVip;

class UserController extends Controller
{
    /**
     * 获取用户列表（管理员）
     */
    public function index(Request $request)
    {
        $query = User::query();

        // 搜索条件
        if ($request->has('keyword')) {
            $keyword = $request->input('keyword');
            $query->where('username', 'like', "%{$keyword}%")
                  ->orWhere('phone', 'like', "%{$keyword}%")
                  ->orWhere('email', 'like', "%{$keyword}%");
        }

        // 状态筛选
        if ($request->has('status')) {
            $status = $request->input('status');
            $query->where('status', $status);
        }

        $users = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('page_size', 15));

        return $this->success('获取成功', $users);
    }

    /**
     * 更新用户状态
     */
    public function updateStatus(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->error('用户不存在');
        }

        $user->status = $request->input('status');
        $user->save();

        return $this->success('更新成功');
    }

    /**
     * 调整用户积分
     */
    public function adjustPoints(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->error('用户不存在');
        }

        $amount = $request->input('amount');
        $reason = $request->input('reason', '管理员调整');

        if (!$amount) {
            return $this->error('请输入调整金额');
        }

        \DB::beginTransaction();
        try {
            $user->points += $amount;
            $user->save();

            // 记录积分变动
            PointsRecord::create([
                'user_id' => $id,
                'type' => $amount > 0 ? 'reward' : 'deduct',
                'amount' => abs($amount),
                'description' => $reason,
                'balance_after' => $user->points,
            ]);

            \DB::commit();
            return $this->success('调整成功', [
                'remaining_points' => $user->points
            ]);
        } catch (\Exception $e) {
            \DB::rollBack();
            return $this->error('调整失败：' . $e->getMessage());
        }
    }

    /**
     * 获取用户详情
     */
    public function show($id)
    {
        $user = User::with(['vipRecord', 'pointsRecords' => function ($query) {
            $query->orderBy('created_at', 'desc')->limit(20);
        }])->find($id);

        if (!$user) {
            return $this->error('用户不存在');
        }

        return $this->success('获取成功', $user);
    }
}
