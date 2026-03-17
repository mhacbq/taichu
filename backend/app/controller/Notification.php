<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\service\PushService;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

/**
 * 推送通知控制器
 */
class Notification extends BaseController
{
    protected $middleware = [\app\middleware\Auth::class];
    
    /**
     * 获取用户通知列表
     */
    public function getNotifications()
    {
        $user = $this->request->user;
        $userId = $user['sub'];
        $page = (int) $this->request->get('page', 1);
        $limit = (int) $this->request->get('limit', 20);
        $unreadOnly = (bool) $this->request->get('unread_only', false);
        
        $query = Db::name('tc_notification')
            ->where('user_id', $userId);
        
        if ($unreadOnly) {
            $query->where('is_read', 0);
        }
        
        $total = $query->count();
        $list = $query->order('created_at', 'desc')
            ->page($page, $limit)
            ->select()
            ->toArray();
        
        // 获取未读数量
        $unreadCount = Db::name('tc_notification')
            ->where('user_id', $userId)
            ->where('is_read', 0)
            ->count();
        
        return $this->success([
            'list' => $list,
            'total' => $total,
            'unread_count' => $unreadCount,
            'page' => $page,
            'limit' => $limit,
        ]);
    }
    
    /**
     * 标记通知为已读
     */
    public function markAsRead()
    {
        $user = $this->request->user;
        $userId = $user['sub'];
        $notificationId = $this->request->post('notification_id');
        
        if ($notificationId) {
            // 标记单条通知
            $notification = Db::name('tc_notification')
                ->where('id', $notificationId)
                ->where('user_id', $userId)
                ->find();
            
            if (!$notification) {
                return $this->error('通知不存在', 404);
            }
            
            Db::name('tc_notification')
                ->where('id', $notificationId)
                ->update([
                    'is_read' => 1,
                    'read_at' => date('Y-m-d H:i:s'),
                ]);
        } else {
            // 标记所有未读通知
            Db::name('tc_notification')
                ->where('user_id', $userId)
                ->where('is_read', 0)
                ->update([
                    'is_read' => 1,
                    'read_at' => date('Y-m-d H:i:s'),
                ]);
        }
        
        return $this->success([], '标记成功');
    }
    
    /**
     * 删除通知
     */
    public function deleteNotification()
    {
        $user = $this->request->user;
        $userId = $user['sub'];
        $notificationId = $this->request->post('notification_id');
        
        if (empty($notificationId)) {
            return $this->error('通知ID不能为空');
        }
        
        $notification = Db::name('tc_notification')
            ->where('id', $notificationId)
            ->where('user_id', $userId)
            ->find();
        
        if (!$notification) {
            return $this->error('通知不存在', 404);
        }
        
        Db::name('tc_notification')
            ->where('id', $notificationId)
            ->delete();
        
        return $this->success([], '删除成功');
    }
    
    /**
     * 获取通知设置
     */
    public function getSettings()
    {
        $user = $this->request->user;
        $userId = $user['sub'];
        
        $settings = Db::name('tc_notification_setting')
            ->where('user_id', $userId)
            ->find();
        
        if (!$settings) {
            // 返回默认设置
            $settings = [
                'daily_fortune' => 1,
                'system_notice' => 1,
                'activity' => 1,
                'recharge' => 1,
                'points_change' => 1,
                'push_enabled' => 1,
                'sound_enabled' => 1,
                'vibration_enabled' => 1,
                'quiet_hours_start' => '22:00',
                'quiet_hours_end' => '08:00',
            ];
        }
        
        return $this->success($settings);
    }
    
    /**
     * 更新通知设置
     */
    public function updateSettings()
    {
        $user = $this->request->user;
        $userId = $user['sub'];
        $data = $this->request->post();
        
        $allowedFields = [
            'daily_fortune',
            'system_notice',
            'activity',
            'recharge',
            'points_change',
            'push_enabled',
            'sound_enabled',
            'vibration_enabled',
            'quiet_hours_start',
            'quiet_hours_end',
        ];
        
        $updateData = [];
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }
        
        if (empty($updateData)) {
            return $this->error('没有可更新的设置');
        }
        
        $updateData['updated_at'] = date('Y-m-d H:i:s');
        
        // 检查是否已存在设置
        $exists = Db::name('tc_notification_setting')
            ->where('user_id', $userId)
            ->find();
        
        if ($exists) {
            Db::name('tc_notification_setting')
                ->where('user_id', $userId)
                ->update($updateData);
        } else {
            $updateData['user_id'] = $userId;
            $updateData['created_at'] = date('Y-m-d H:i:s');
            Db::name('tc_notification_setting')->insert($updateData);
        }
        
        return $this->success([], '设置更新成功');
    }
    
    /**
     * 注册推送设备
     */
    public function registerDevice()
    {
        $user = $this->request->user;
        $userId = (int) ($user['sub'] ?? 0);
        $platform = strtolower(trim((string) $this->request->post('platform', ''))); // ios, android, web
        $deviceToken = trim((string) $this->request->post('device_token', ''));
        $deviceId = trim((string) $this->request->post('device_id', ''));

        if ($userId <= 0) {
            return $this->error('用户信息无效', 401);
        }
        if ($platform === '' || $deviceToken === '' || $deviceId === '') {
            return $this->error('平台类型、设备ID和设备令牌不能为空');
        }

        if (!in_array($platform, ['ios', 'android', 'web'], true)) {
            return $this->error('无效的平台类型');
        }

        Db::name('tc_push_device')
            ->where('device_id', $deviceId)
            ->delete();

        Db::name('tc_push_device')->insert([
            'user_id' => $userId,
            'platform' => $platform,
            'device_token' => $deviceToken,
            'device_id' => $deviceId,
            'is_active' => 1,
            'last_active_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->success([], '设备注册成功');
    }
    
    /**
     * 注销推送设备
     */
    public function unregisterDevice()
    {
        $user = $this->request->user;
        $userId = $user['sub'];
        $deviceId = $this->request->post('device_id');
        
        if (empty($deviceId)) {
            return $this->error('设备ID不能为空');
        }
        
        Db::name('tc_push_device')
            ->where('user_id', $userId)
            ->where('device_id', $deviceId)
            ->delete();
        
        return $this->success([], '设备注销成功');
    }
    
    /**
     * 发送测试通知（仅用于测试）
     */
    public function sendTestNotification()
    {
        $user = $this->request->user;
        $userId = (int) ($user['sub'] ?? 0);

        if ($userId <= 0) {
            return $this->error('用户信息无效', 401);
        }

        $result = self::sendNotificationDetailed(
            $userId,
            'system',
            '测试通知',
            '这是一条测试通知，用于验证推送功能是否正常工作。',
            ['action' => 'test', 'source' => 'manual_test']
        );

        if (!($result['success'] ?? false)) {
            return $this->error((string) ($result['message'] ?? '测试通知发送失败'), 400, $result);
        }

        $message = !empty($result['push_success'])
            ? '测试通知已发送并触达推送通道'
            : (string) ($result['message'] ?? '测试通知已创建');

        return $this->success($result, $message);
    }
    
    /**
     * 发送通知（静态方法，供其他控制器调用）
     */
    public static function sendNotification(int $userId, string $type, string $title, string $content, array $data = []): bool
    {
        $result = self::sendNotificationDetailed($userId, $type, $title, $content, $data);

        return (bool) ($result['success'] ?? false);
    }

    /**
     * 发送通知并返回详细结果
     */
    public static function sendNotificationDetailed(int $userId, string $type, string $title, string $content, array $data = []): array
    {
        try {
            $settings = Db::name('tc_notification_setting')
                ->where('user_id', $userId)
                ->find();

            $typeFieldMap = [
                'daily_fortune' => 'daily_fortune',
                'system' => 'system_notice',
                'activity' => 'activity',
                'recharge' => 'recharge',
                'points' => 'points_change',
                'test' => 'system_notice',
            ];

            $typeField = $typeFieldMap[$type] ?? null;
            if ($typeField !== null && $settings && isset($settings[$typeField]) && !(bool) $settings[$typeField]) {
                return [
                    'success' => false,
                    'stored' => false,
                    'push_success' => false,
                    'message' => '用户已关闭该类型通知',
                ];
            }

            $notificationId = (int) Db::name('tc_notification')->insertGetId([
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'content' => $content,
                'data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $unreadKey = "user:{$userId}:unread_notifications";
            Cache::inc($unreadKey);

            $pushEnabled = !($settings && array_key_exists('push_enabled', $settings) && !(bool) $settings['push_enabled']);
            if (!$pushEnabled) {
                return [
                    'success' => true,
                    'stored' => true,
                    'notification_id' => $notificationId,
                    'push_success' => false,
                    'message' => '通知已保存，用户已关闭设备推送',
                ];
            }

            if ($settings && !self::canSendDuringCurrentTime($settings)) {
                return [
                    'success' => true,
                    'stored' => true,
                    'notification_id' => $notificationId,
                    'push_success' => false,
                    'message' => '通知已保存，当前处于免打扰时段',
                ];
            }

            $pushResult = PushService::sendPushDetailed($userId, $title, $content, $data);
            $pushSuccess = (bool) ($pushResult['success'] ?? false);

            if (!$pushSuccess) {
                Log::warning('通知已入库，但推送通道未成功送达', [
                    'user_id' => $userId,
                    'notification_id' => $notificationId,
                    'type' => $type,
                    'push_result' => $pushResult,
                ]);
            }

            return [
                'success' => true,
                'stored' => true,
                'notification_id' => $notificationId,
                'push_success' => $pushSuccess,
                'push' => $pushResult,
                'message' => $pushSuccess ? '通知发送成功' : '通知已保存，但推送未送达',
            ];
        } catch (\Throwable $e) {
            Log::error('发送通知失败', [
                'user_id' => $userId,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'stored' => false,
                'push_success' => false,
                'message' => '发送通知失败，请稍后重试',
            ];
        }
    }
    
    /**
     * 发送每日运势提醒
     */
    public static function sendDailyFortuneReminder(int $userId): bool
    {
        return self::sendNotification(
            $userId,
            'daily_fortune',
            '每日运势已更新',
            '点击查看今日运势，把握好运气！',
            ['action' => 'open_daily_fortune']
        );
    }
    
    /**
     * 发送充值成功通知
     */
    public static function sendRechargeSuccessNotification(int $userId, float $amount, int $points): bool
    {
        return self::sendNotification(
            $userId,
            'recharge',
            '充值成功',
            "您已成功充值{$amount}元，获得{$points}积分",
            ['action' => 'open_recharge_history']
        );
    }
    
    /**
     * 发送积分变动通知
     */
    public static function sendPointsChangeNotification(int $userId, int $points, string $reason): bool
    {
        $actionText = $points > 0 ? '增加' : '减少';
        $absPoints = abs($points);
        
        return self::sendNotification(
            $userId,
            'points',
            "积分{$actionText}提醒",
            "您的积分{$actionText}了{$absPoints}分，原因：{$reason}",
            ['action' => 'open_points_record']
        );
    }
}
