<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Db;
use think\facade\Log;

/**
 * 积分服务类
 * 确保积分操作的原子性和数据一致性
 */
class PointsService
{
    /**
     * 消耗积分（事务完整版）
     * 同时更新用户积分和记录积分变动，确保原子性
     *
     * @param int $userId 用户ID
     * @param int $points 扣除积分数量
     * @param string $action 操作描述
     * @param string $type 类型
     * @param int $relatedId 关联ID
     * @param string $remark 备注
     * @return array [success, message, balance]
     */
    public function consume(int $userId, int $points, string $action, string $type = 'consume', int $relatedId = 0, string $remark = ''): array
    {
        if ($points <= 0) {
            return ['success' => false, 'message' => '积分数量无效', 'balance' => 0];
        }

        if (empty($action)) {
            return ['success' => false, 'message' => '操作描述不能为空', 'balance' => 0];
        }

        Db::startTrans();
        try {
            // 1. 使用行锁获取用户当前积分
            $userData = Db::name('tc_user')
                ->where('id', $userId)
                ->where('status', 1)
                ->lock(true)
                ->find();

            if (!$userData) {
                Db::rollback();
                return ['success' => false, 'message' => '用户不存在', 'balance' => 0];
            }

            // 2. 检查积分是否足够
            if ($userData['points'] < $points) {
                Db::rollback();
                return ['success' => false, 'message' => '积分不足', 'balance' => $userData['points']];
            }

            // 3. 扣除积分
            $result = Db::name('tc_user')
                ->where('id', $userId)
                ->dec('points', $points)
                ->update();

            if ($result === 0) {
                Db::rollback();
                return ['success' => false, 'message' => '积分扣除失败', 'balance' => $userData['points']];
            }

            // 4. 记录积分变动（使用数据库插入而非模型，保持在同一连接中）
            $recordId = Db::name('tc_points_record')->insertGetId([
                'user_id' => $userId,
                'action' => $action,
                'points' => -$points,
                'type' => $type,
                'related_id' => $relatedId,
                'remark' => $remark,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            if (!$recordId) {
                Db::rollback();
                return ['success' => false, 'message' => '积分记录失败', 'balance' => $userData['points']];
            }

            // 5. 提交事务
            Db::commit();

            // 6. 返回新的积分余额
            $newBalance = $userData['points'] - $points;

            return [
                'success' => true,
                'message' => '积分扣除成功',
                'balance' => $newBalance,
                'record_id' => $recordId,
            ];

        } catch (\Exception $e) {
            Db::rollback();
            $this->logTransactionFailure('consume', $e, [
                'user_id' => $userId,
                'points' => $points,
                'action' => $action,
                'type' => $type,
                'related_id' => $relatedId,
                'remark' => $remark,
            ]);
            throw $e;
        }
    }

    /**
     * 增加积分（事务完整版）
     *
     * @param int $userId 用户ID
     * @param int $points 增加积分数量
     * @param string $action 操作描述
     * @param string $type 类型
     * @param int $relatedId 关联ID
     * @param string $remark 备注
     * @return array [success, message, balance]
     */
    public function add(int $userId, int $points, string $action, string $type = 'add', int $relatedId = 0, string $remark = ''): array
    {
        if ($points <= 0) {
            return ['success' => false, 'message' => '积分数量无效', 'balance' => 0];
        }

        Db::startTrans();
        try {
            // 1. 使用行锁获取用户数据
            $userData = Db::name('tc_user')
                ->where('id', $userId)
                ->where('status', 1)
                ->lock(true)
                ->find();

            if (!$userData) {
                Db::rollback();
                return ['success' => false, 'message' => '用户不存在', 'balance' => 0];
            }

            // 2. 增加积分
            $result = Db::name('tc_user')
                ->where('id', $userId)
                ->inc('points', $points)
                ->update();

            if ($result === 0) {
                Db::rollback();
                return ['success' => false, 'message' => '积分增加失败', 'balance' => $userData['points']];
            }

            // 3. 记录积分变动
            $recordId = Db::name('tc_points_record')->insertGetId([
                'user_id' => $userId,
                'action' => $action,
                'points' => $points,
                'type' => $type,
                'related_id' => $relatedId,
                'remark' => $remark,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            if (!$recordId) {
                Db::rollback();
                return ['success' => false, 'message' => '积分记录失败', 'balance' => $userData['points']];
            }

            Db::commit();

            $newBalance = $userData['points'] + $points;

            return [
                'success' => true,
                'message' => '积分增加成功',
                'balance' => $newBalance,
                'record_id' => $recordId,
            ];

        } catch (\Exception $e) {
            Db::rollback();
            $this->logTransactionFailure('add', $e, [
                'user_id' => $userId,
                'points' => $points,
                'action' => $action,
                'type' => $type,
                'related_id' => $relatedId,
                'remark' => $remark,
            ]);
            throw $e;
        }
    }

    protected function logTransactionFailure(string $operation, \Throwable $exception, array $context = []): void
    {
        Log::error('积分事务失败', [
            'operation' => $operation,
            'error' => $exception->getMessage(),
            'exception' => get_class($exception),
            'context' => $this->sanitizeLogContext($context),
        ]);
    }

    protected function sanitizeLogContext(array $context): array
    {
        $sanitized = [];

        foreach ($context as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = mb_strlen($value) > 200 ? mb_substr($value, 0, 200) . '...' : $value;
                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    /**
     * 批量增加积分（用于充值、奖励等）
     *
     * @param array $userIds 用户ID数组
     * @param int $points 积分数量
     * @param string $action 操作描述
     * @param string $remark 备注
     * @return array [success_count, fail_count]
     */
    public function batchAdd(array $userIds, int $points, string $action, string $remark = ''): array
    {
        $successCount = 0;
        $failCount = 0;

        foreach ($userIds as $userId) {
            $result = $this->add($userId, $points, $action, 'batch_add', 0, $remark);
            if ($result['success']) {
                $successCount++;
            } else {
                $failCount++;
            }
        }

        return [
            'success_count' => $successCount,
            'fail_count' => $failCount,
        ];
    }

    /**
     * 检查用户是否有足够积分
     *
     * @param int $userId 用户ID
     * @param int $points 需要积分
     * @return bool
     */
    public function hasEnoughPoints(int $userId, int $points): bool
    {
        $userPoints = Db::name('tc_user')
            ->where('id', $userId)
            ->where('status', 1)
            ->value('points');

        return $userPoints !== null && $userPoints >= $points;
    }

    /**
     * 获取用户积分余额（带缓存）
     *
     * @param int $userId 用户ID
     * @return int
     */
    public function getBalance(int $userId): int
    {
        $points = Db::name('tc_user')
            ->where('id', $userId)
            ->value('points');

        return $points ?? 0;
    }

    /**
     * 积分转账（用户之间）
     *
     * @param int $fromUserId 转出用户ID
     * @param int $toUserId 转入用户ID
     * @param int $points 积分数量
     * @param string $remark 备注
     * @return array
     */
    public function transfer(int $fromUserId, int $toUserId, int $points, string $remark = ''): array
    {
        if ($fromUserId === $toUserId) {
            return ['success' => false, 'message' => '不能向自己转账'];
        }

        Db::startTrans();
        try {
            // 先扣除转出用户积分
            $deductResult = $this->consume(
                $fromUserId,
                $points,
                '转账给用户' . $toUserId,
                'transfer_out',
                $toUserId,
                $remark
            );

            if (!$deductResult['success']) {
                Db::rollback();
                return $deductResult;
            }

            // 再增加转入用户积分
            $addResult = $this->add(
                $toUserId,
                $points,
                '来自用户' . $fromUserId . '的转账',
                'transfer_in',
                $fromUserId,
                $remark
            );

            if (!$addResult['success']) {
                Db::rollback();
                return ['success' => false, 'message' => '转账失败，积分已退回'];
            }

            Db::commit();

            return [
                'success' => true,
                'message' => '转账成功',
                'from_balance' => $deductResult['balance'],
                'to_balance' => $addResult['balance'],
            ];

        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }
}
