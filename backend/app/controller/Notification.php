<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use think\facade\Cache;
use think\facade\Db;

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
        $userId = $user['sub'];
        $platform = $this->request->post('platform'); // ios, android, web
        $deviceToken = $this->request->post('device_token');
        $deviceId = $this->request->post('device_id');
        
        if (empty($platform) || empty($deviceToken)) {
            return $this->error('平台类型和设备令牌不能为空');
        }
        
        // 验证平台类型
        if (!in_array($platform, ['ios', 'android', 'web'])) {
            return $this->error('无效的平台类型');
        }
        
        // 删除该设备的旧记录
        Db::name('tc_push_device')
            ->where('device_id', $deviceId)
            ->delete();
        
        // 插入新记录
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
        $userId = $user['sub'];
        
        // 创建测试通知
        $notificationId = Db::name('tc_notification')->insertGetId([
            'user_id' => $userId,
            'type' => 'test',
            'title' => '测试通知',
            'content' => '这是一条测试通知，用于验证推送功能是否正常工作。',
            'data' => json_encode(['action' => 'test']),
            'is_read' => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        
        return $this->success([
            'notification_id' => $notificationId,
        ], '测试通知已发送');
    }
    
    /**
     * 发送通知（静态方法，供其他控制器调用）
     */
    public static function sendNotification(int $userId, string $type, string $title, string $content, array $data = []): bool
    {
        try {
            // 检查用户通知设置
            $settings = Db::name('tc_notification_setting')
                ->where('user_id', $userId)
                ->find();
            
            // 根据类型检查是否允许发送
            $typeFieldMap = [
                'daily_fortune' => 'daily_fortune',
                'system' => 'system_notice',
                'activity' => 'activity',
                'recharge' => 'recharge',
                'points' => 'points_change',
            ];
            
            if (isset($typeFieldMap[$type]) && $settings) {
                if (!$settings[$typeFieldMap[$type]] || !$settings['push_enabled']) {
                    return false;
                }
            }
            
            // 检查免打扰时间
            if ($settings && $settings['quiet_hours_start'] && $settings['quiet_hours_end']) {
                $now = date('H:i');
                $start = $settings['quiet_hours_start'];
                $end = $settings['quiet_hours_end'];
                
                if ($start <= $end) {
                    if ($now >= $start && $now <= $end) {
                        return false;
                    }
                } else {
                    if ($now >= $start || $now <= $end) {
                        return false;
                    }
                }
            }
            
            // 保存通知
            Db::name('tc_notification')->insert([
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'content' => $content,
                'data' => json_encode($data),
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            
            // 增加未读计数
            $unreadKey = "user:{$userId}:unread_notifications";
            \think\facade\Cache::inc($unreadKey);
            
            // 3. 调用第三方推送服务
            \app\service\PushService::sendPush($userId, $title, $content, $data);
            
            return true;
        } catch (\Exception $e) {
            trace('发送通知失败: ' . $e->getMessage(), 'error');
            return false;
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
