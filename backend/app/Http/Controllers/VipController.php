<?php

namespace app\Http\Controllers;

use app\Http\Middleware\AdminAuth;
use Illuminate\Http\Request;
use app\Models\VipPackage;
use app\Models\UserVip;
use app\Models\PointsRecord;

class VipController extends Controller
{
    /**
     * 获取VIP套餐列表
     */
    public function packages(Request $request)
    {
        $packages = VipPackage::where('is_active', 1)
            ->orderBy('price')
            ->get();

        return $this->success('获取成功', $packages);
    }

    /**
     * 购买VIP套餐
     */
    public function purchase(Request $request)
    {
        $userId = $request->user()->id;
        $packageId = $request->input('package_id');
        $paymentMethod = $request->input('payment_method');

        // 验证支付方式
        if (!in_array($paymentMethod, ['alipay', 'wechat'])) {
            return $this->error('不支持的支付方式');
        }

        // 获取套餐信息
        $package = VipPackage::find($packageId);
        if (!$package || !$package->is_active) {
            return $this->error('套餐不存在或已下架');
        }

        // 检查是否已有未过期的VIP
        $currentVip = UserVip::where('user_id', $userId)
            ->where('end_time', '>', now())
            ->first();

        if ($currentVip) {
            return $this->error('您已是VIP会员，无需重复购买');
        }

        // 获取用户积分
        $user = $request->user();
        if ($user->points < $package->price) {
            return $this->error('积分不足', 403);
        }

        // 开启事务
        \DB::beginTransaction();
        try {
            // 扣除积分
            $user->points -= $package->price;
            $user->save();

            // 记录积分变动
            PointsRecord::create([
                'user_id' => $userId,
                'type' => 'consume',
                'amount' => $package->price,
                'description' => "购买VIP套餐：{$package->name}",
                'balance_after' => $user->points,
            ]);

            // 创建VIP记录
            $endTime = now()->addDays($package->days);
            UserVip::create([
                'user_id' => $userId,
                'package_id' => $packageId,
                'start_time' => now(),
                'end_time' => $endTime,
                'is_active' => 1,
            ]);

            \DB::commit();
            return $this->success('购买成功', [
                'end_time' => $endTime,
                'remaining_points' => $user->points
            ]);
        } catch (\Exception $e) {
            \DB::rollBack();
            return $this->error('购买失败：' . $e->getMessage());
        }
    }

    /**
     * 获取用户VIP状态
     */
    public function status(Request $request)
    {
        $userId = $request->user()->id;
        $vip = UserVip::where('user_id', $userId)
            ->where('end_time', '>', now())
            ->where('is_active', 1)
            ->first();

        if ($vip) {
            return $this->success('获取成功', [
                'is_vip' => true,
                'end_time' => $vip->end_time,
                'days_left' => now()->diffInDays($vip->end_time),
            ]);
        }

        return $this->success('获取成功', [
            'is_vip' => false,
            'end_time' => null,
            'days_left' => 0,
        ]);
    }

    /**
     * 获取VIP购买记录（管理员）
     */
    public function records(Request $request)
    {
        $query = UserVip::with(['user', 'package']);

        // 搜索条件
        if ($request->has('keyword')) {
            $keyword = $request->input('keyword');
            $query->whereHas('user', function ($q) use ($keyword) {
                $q->where('username', 'like', "%{$keyword}%")
                  ->orWhere('phone', 'like', "%{$keyword}%");
            });
        }

        if ($request->has('status')) {
            $status = $request->input('status');
            if ($status === 'active') {
                $query->where('end_time', '>', now());
            } elseif ($status === 'expired') {
                $query->where('end_time', '<=', now());
            }
        }

        $records = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('page_size', 15));

        return $this->success('获取成功', $records);
    }
}
