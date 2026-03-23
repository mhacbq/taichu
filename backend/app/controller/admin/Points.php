<?php
namespace app\controller\admin;

use app\BaseController;
use app\model\User;
use app\model\PointsRecord;
use think\Request;
use think\facade\Db;

class Points extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    /**
     * 获取积分记录列表
     */
    public function getRecords()
    {
        if (!$this->hasAdminPermission('points_view')) {
            return $this->error('无权限查看积分记录', 403);
        }

        $params = $this->request->get();
        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['page_size'] ?? 20);
        $userId = $params['user_id'] ?? '';
        $type = $params['type'] ?? '';

        try {
            $query = PointsRecord::alias('pr')
                ->leftJoin('tc_user u', 'pr.user_id = u.id')
                ->field('pr.*, u.username, u.nickname, u.phone');

            if ($userId) {
                $query->where('pr.user_id', $userId);
            }

            if ($type) {
                $query->where('pr.type', $type);
            }

            $total = $query->count();
            $list = $query->order('pr.id', 'desc')
                ->limit($pageSize)
                ->page($page)
                ->select()
                ->toArray();

            return $this->success([
                'list' => $list,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize
            ]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_points_records', $e, '获取积分记录失败');
        }
    }

    /**
     * 积分调整
     */
    public function adjust()
    {
        if (!$this->hasAdminPermission('points_edit')) {
            return $this->error('无权限调整积分', 403);
        }

        $data = $this->request->post();
        $targetType = $data['target_type'] ?? 'specific';
        $targets = $data['targets'] ?? [];
        $type = $data['type'] ?? 'add';
        $amount = (int) ($data['amount'] ?? 0);
        $reason = $data['reason'] ?? '';

        if (!$amount || $amount === 0) {
            return $this->error('积分调整数量不能为0');
        }

        if (empty($reason)) {
            return $this->error('调整原因不能为空');
        }

        // 如果是增加，amount为正数；如果是减少，amount为负数
        // 前端已经处理了amount的符号，所以直接使用
        $points = $type === 'add' ? abs($amount) : -abs($amount);

        if ($targetType === 'specific') {
            if (empty($targets) || !is_array($targets)) {
                return $this->error('请选择要调整的用户');
            }
            
            return $this->adjustForUsers($targets, $points, $reason);
        } elseif ($targetType === 'all') {
            return $this->adjustForAll($points, $reason);
        } elseif ($targetType === 'active') {
            return $this->adjustForActiveUsers($points, $reason);
        } else {
            return $this->error('无效的调整对象类型');
        }
    }

    /**
     * 为特定用户调整积分
     */
    private function adjustForUsers($targets, $points, $reason)
    {
        try {
            Db::startTrans();

            $successCount = 0;
            $failedCount = 0;

            foreach ($targets as $target) {
                // 支持用户ID或手机号
                $user = null;
                if (is_numeric($target)) {
                    $user = User::find($target);
                } else {
                    $user = User::where('phone', $target)->find();
                }

                if (!$user) {
                    $failedCount++;
                    continue;
                }

                $oldPoints = $user->points ?? 0;
                $newPoints = $oldPoints + $points;

                if ($newPoints < 0) {
                    $failedCount++;
                    continue;
                }

                $user->points = $newPoints;
                $user->save();

                PointsRecord::create([
                    'user_id' => $user->id,
                    'type' => 'admin_adjust',
                    'amount' => $points,
                    'balance_after' => $newPoints,
                    'remark' => $reason,
                    'created_at' => date('Y-m-d H:i:s')
                ]);

                $successCount++;
            }

            $this->logOperation('adjust_points_batch', 'points', [
                'target_count' => count($targets),
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'points' => $points,
                'reason' => $reason
            ]);

            Db::commit();

            return $this->success([
                'target_count' => count($targets),
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'points' => $points
            ], "成功调整{$successCount}个用户，失败{$failedCount}个");
        } catch (\Throwable $e) {
            Db::rollback();
            return $this->respondSystemException('admin_points_adjust_users', $e, '批量调整积分失败');
        }
    }

    /**
     * 为所有用户调整积分
     */
    private function adjustForAll($points, $reason)
    {
        try {
            Db::startTrans();

            $users = User::select();
            $successCount = 0;
            $failedCount = 0;

            foreach ($users as $user) {
                $oldPoints = $user->points ?? 0;
                $newPoints = $oldPoints + $points;

                if ($newPoints < 0) {
                    $failedCount++;
                    continue;
                }

                $user->points = $newPoints;
                $user->save();

                PointsRecord::create([
                    'user_id' => $user->id,
                    'type' => 'admin_adjust',
                    'amount' => $points,
                    'balance_after' => $newPoints,
                    'remark' => $reason,
                    'created_at' => date('Y-m-d H:i:s')
                ]);

                $successCount++;
            }

            $this->logOperation('adjust_points_all', 'points', [
                'total_count' => count($users),
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'points' => $points,
                'reason' => $reason
            ]);

            Db::commit();

            return $this->success([
                'total_count' => count($users),
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'points' => $points
            ], "成功调整{$successCount}个用户，失败{$failedCount}个");
        } catch (\Throwable $e) {
            Db::rollback();
            return $this->respondSystemException('admin_points_adjust_all', $e, '全站调整积分失败');
        }
    }

    /**
     * 为活跃用户调整积分
     */
    private function adjustForActiveUsers($points, $reason)
    {
        try {
            Db::startTrans();

            // 30天内登录过的用户
            $activeDate = date('Y-m-d H:i:s', strtotime('-30 days'));
            $users = User::where('last_login_time', '>=', $activeDate)->select();

            $successCount = 0;
            $failedCount = 0;

            foreach ($users as $user) {
                $oldPoints = $user->points ?? 0;
                $newPoints = $oldPoints + $points;

                if ($newPoints < 0) {
                    $failedCount++;
                    continue;
                }

                $user->points = $newPoints;
                $user->save();

                PointsRecord::create([
                    'user_id' => $user->id,
                    'type' => 'admin_adjust',
                    'amount' => $points,
                    'balance_after' => $newPoints,
                    'remark' => $reason,
                    'created_at' => date('Y-m-d H:i:s')
                ]);

                $successCount++;
            }

            $this->logOperation('adjust_points_active', 'points', [
                'total_count' => count($users),
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'points' => $points,
                'reason' => $reason
            ]);

            Db::commit();

            return $this->success([
                'total_count' => count($users),
                'success_count' => $successCount,
                'failed_count' => $failedCount,
                'points' => $points
            ], "成功调整{$successCount}个用户，失败{$failedCount}个");
        } catch (\Throwable $e) {
            Db::rollback();
            return $this->respondSystemException('admin_points_adjust_active', $e, '活跃用户调整积分失败');
        }
    }

    /**
     * 获取积分规则（从数据库获取）
     */
    public function getRules()
    {
        if (!$this->hasAdminPermission('points_view')) {
            return $this->error('无权限查看积分规则', 403);
        }

        try {
            $rules = PointsRecord::distinct()->column('type');
            return $this->success([
                'list' => array_map(function($type) {
                    return [
                        'id' => $type,
                        'rule_name' => $this->getRuleName($type),
                        'points' => 0,
                        'description' => $this->getRuleDescription($type),
                        'status' => 1
                    ];
                }, $rules),
                'total' => count($rules)
            ]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_points_rules', $e, '获取积分规则失败');
        }
    }

    /**
     * 保存积分规则（暂不支持，规则由系统自动生成）
     */
    public function saveRule()
    {
        return $this->error('积分规则由系统自动管理，不支持手动新增', 400);
    }

    /**
     * 删除积分规则（暂不支持）
     */
    public function deleteRule()
    {
        return $this->error('积分规则由系统自动管理，不支持手动删除', 400);
    }

    /**
     * 获取积分统计
     */
    public function getStats()
    {
        if (!$this->hasAdminPermission('points_view')) {
            return $this->error('无权限查看积分统计', 403);
        }

        try {
            $stats = PointsRecord::alias('pr')
                ->leftJoin('tc_user u', 'pr.user_id = u.id')
                ->field([
                    'COUNT(pr.id) as total_records',
                    'SUM(CASE WHEN pr.amount > 0 THEN pr.amount ELSE 0 END) as total_earned',
                    'SUM(CASE WHEN pr.amount < 0 THEN ABS(pr.amount) ELSE 0 END) as total_spent',
                    'COUNT(DISTINCT pr.user_id) as active_users',
                    'AVG(u.points) as avg_points',
                    'MAX(u.points) as max_points',
                    'MIN(u.points) as min_points'
                ])
                ->where('pr.created_at', '>=', date('Y-m-d 00:00:00', strtotime('-30 days')))
                ->find();

            $typeStats = PointsRecord::field('type, COUNT(*) as count, SUM(ABS(amount)) as total_amount')
                ->where('created_at', '>=', date('Y-m-d 00:00:00', strtotime('-30 days')))
                ->group('type')
                ->select()
                ->toArray();

            return $this->success([
                'overview' => $stats,
                'type_stats' => $typeStats
            ]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_points_stats', $e, '获取积分统计失败');
        }
    }

    /**
     * 批量调整积分
     */
    public function batchAdjust()
    {
        if (!$this->hasAdminPermission('points_edit')) {
            return $this->error('无权限调整积分', 403);
        }

        $data = $this->request->post();
        $userIds = $data['user_ids'] ?? [];
        $points = $data['points'] ?? 0;
        $remark = $data['remark'] ?? '管理员批量调整';

        if (empty($userIds) || !is_array($userIds)) {
            return $this->error('用户ID列表不能为空');
        }

        if ($points === 0) {
            return $this->error('积分调整数量不能为0');
        }

        try {
            Db::startTrans();

            $successCount = 0;
            $failCount = 0;

            foreach ($userIds as $userId) {
                try {
                    $user = User::find($userId);
                    if (!$user) {
                        $failCount++;
                        continue;
                    }

                    $oldPoints = $user->points ?? 0;
                    $newPoints = $oldPoints + $points;

                    if ($newPoints < 0) {
                        $failCount++;
                        continue;
                    }

                    $user->points = $newPoints;
                    $user->save();

                    PointsRecord::create([
                        'user_id' => $userId,
                        'type' => 'manual_batch',
                        'amount' => $points,
                        'balance_after' => $newPoints,
                        'remark' => $remark,
                        'created_at' => date('Y-m-d H:i:s')
                    ]);

                    $successCount++;
                } catch (\Throwable $e) {
                    $failCount++;
                }
            }

            $this->logOperation('batch_adjust_points', 'user', [
                'user_ids' => $userIds,
                'points' => $points,
                'success_count' => $successCount,
                'fail_count' => $failCount
            ]);

            Db::commit();

            return $this->success([
                'total' => count($userIds),
                'success_count' => $successCount,
                'fail_count' => $failCount
            ], "批量调整完成: 成功{$successCount}个, 失败{$failCount}个");
        } catch (\Throwable $e) {
            Db::rollback();
            return $this->respondSystemException('admin_points_batch_adjust', $e, '批量积分调整失败');
        }
    }

    /**
     * 获取规则名称
     */
    protected function getRuleName($type)
    {
        $names = [
            'sign_in' => '每日签到',
            'complete_profile' => '完善资料',
            'invite_friend' => '邀请好友',
            'paid_order' => '付费订单',
            'vip_purchase' => '购买VIP',
            'manual' => '管理员调整',
            'manual_batch' => '批量调整',
            'consumption' => '积分消费',
            'refund' => '退款返还',
            'expire' => '积分过期'
        ];
        return $names[$type] ?? $type;
    }

    /**
     * 获取规则描述
     */
    protected function getRuleDescription($type)
    {
        $descriptions = [
            'sign_in' => '每日签到获得积分奖励',
            'complete_profile' => '首次完善个人资料获得积分',
            'invite_friend' => '邀请好友注册获得积分',
            'paid_order' => '完成付费订单获得积分',
            'vip_purchase' => '购买VIP套餐获得积分',
            'manual' => '管理员手动调整积分',
            'manual_batch' => '管理员批量调整积分',
            'consumption' => '使用积分购买服务',
            'refund' => '订单退款返还积分',
            'expire' => '积分过期扣除'
        ];
        return $descriptions[$type] ?? '';
    }
}