<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\service\PushService;
use app\service\SchemaInspector;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Log;

/**
 * 推送通知控制器
 */
class Notification extends BaseController
{
    protected $middleware = [\app\middleware\Auth::class];

    protected const SETTING_BOOLEAN_FIELDS = [
        'daily_fortune',
        'system_notice',
        'activity',
        'recharge',
        'points_change',
        'push_enabled',
        'sound_enabled',
        'vibration_enabled',
    ];

    protected const SETTING_TIME_FIELDS = ['quiet_hours_start', 'quiet_hours_end'];
    
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
        $userId = (int) ($user['sub'] ?? 0);

        if ($userId <= 0) {
            return $this->error('用户信息无效', 401);
        }

        $settings = self::loadUserSettings($userId);

        return $this->success($settings);
    }
    
    /**
     * 更新通知设置
     */
    public function updateSettings()
    {
        $user = $this->request->user;
        $userId = (int) ($user['sub'] ?? 0);
        $data = $this->request->isPut() ? $this->request->put() : $this->request->post();

        if ($userId <= 0) {
            return $this->error('用户信息无效', 401);
        }
        if (!is_array($data)) {
            $data = [];
        }

        $columns = self::getNotificationSettingColumns();
        $booleanUpdateData = [];
        foreach (self::SETTING_BOOLEAN_FIELDS as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            $booleanUpdateData[$field] = self::normalizeBoolFlag($data[$field]);
        }

        $timeUpdateData = [];
        foreach (self::SETTING_TIME_FIELDS as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $timeValue = trim((string) $data[$field]);
            if (!self::isValidHourMinute($timeValue)) {
                return $this->error('免打扰时间格式无效，请使用 HH:MM');
            }
            $timeUpdateData[$field] = $timeValue;
        }

        if (empty($booleanUpdateData) && empty($timeUpdateData)) {
            return $this->error('没有可更新的设置');
        }

        if (self::usesNarrowNotificationSettingTable($columns)) {
            if (!empty($booleanUpdateData)) {
                $now = date('Y-m-d H:i:s');
                Db::startTrans();
                try {
                    foreach ($booleanUpdateData as $type => $enabled) {
                        $query = Db::name('tc_notification_setting')
                            ->where('user_id', $userId)
                            ->where('type', $type);
                        $existing = $query->find();
                        if ($existing) {
                            $query->update([
                                'enabled' => $enabled,
                                'updated_at' => $now,
                            ]);
                        } else {
                            Db::name('tc_notification_setting')->insert([
                                'user_id' => $userId,
                                'type' => $type,
                                'enabled' => $enabled,
                                'created_at' => $now,
                                'updated_at' => $now,
                            ]);
                        }
                    }
                    Db::commit();
                } catch (\Throwable $e) {
                    Db::rollback();
                    throw $e;
                }
            }

            $message = empty($timeUpdateData)
                ? '设置更新成功'
                : '开关设置已更新，当前数据表暂不支持免打扰时间持久化';

            return $this->success(self::loadUserSettings($userId), $message);
        }

        $updateData = $booleanUpdateData + $timeUpdateData;
        $saveData = self::filterWideNotificationSettingPayload($updateData, $columns);
        if (empty($saveData)) {
            return $this->error('当前通知设置表缺少可写字段');
        }

        $saveData['updated_at'] = date('Y-m-d H:i:s');
        $exists = Db::name('tc_notification_setting')
            ->where('user_id', $userId)
            ->find();

        if ($exists) {
            Db::name('tc_notification_setting')
                ->where('user_id', $userId)
                ->update($saveData);
        } else {
            $saveData['user_id'] = $userId;
            $saveData['created_at'] = date('Y-m-d H:i:s');
            Db::name('tc_notification_setting')->insert($saveData);
        }

        return $this->success(self::loadUserSettings($userId), '设置更新成功');
    }

    
    /**
     * 注册推送设备
     */
    public function registerDevice()
    {
        $user = $this->request->user;
        $userId = (int) ($user['sub'] ?? 0);
        $platform = strtolower(trim((string) $this->request->post('platform', ''))); // ios, android, web
        $deviceToken = trim((string) ($this->request->post('device_token', '') ?: $this->request->post('token', '')));
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

        $columns = self::getPushDeviceColumns();
        if (empty($columns)) {
            return $this->error('推送设备表不存在，请先初始化通知相关数据表', 500);
        }

        try {
            Db::name('tc_push_device')
                ->where('device_id', $deviceId)
                ->delete();

            $payload = self::buildPushDevicePayload($userId, $platform, $deviceToken, $deviceId, $columns);
            Db::name('tc_push_device')->insert($payload);

            self::logNotificationEvent('info', '推送设备注册成功', [
                'user_id' => $userId,
                'platform' => $platform,
                'device_id' => $deviceId,
                'device_token' => $deviceToken,
            ]);

            return $this->success([], '设备注册成功');
        } catch (\Throwable $e) {
            self::logNotificationEvent('error', '推送设备注册失败', [
                'user_id' => $userId,
                'platform' => $platform,
                'device_id' => $deviceId,
                'device_token' => $deviceToken,
                'error' => $e->getMessage(),
            ]);

            return $this->error('设备注册失败，请稍后重试', 500);
        }
    }

    
    /**
     * 注销推送设备
     */
    public function unregisterDevice()
    {
        $user = $this->request->user;
        $userId = (int) ($user['sub'] ?? 0);
        $deviceId = trim((string) $this->request->post('device_id', ''));
        
        if ($deviceId === '') {
            return $this->error('设备ID不能为空');
        }

        try {
            $deleted = Db::name('tc_push_device')
                ->where('user_id', $userId)
                ->where('device_id', $deviceId)
                ->delete();

            self::logNotificationEvent($deleted > 0 ? 'info' : 'warning', '推送设备注销结果', [
                'user_id' => $userId,
                'device_id' => $deviceId,
                'deleted' => $deleted,
            ]);

            return $this->success([], '设备注销成功');
        } catch (\Throwable $e) {
            self::logNotificationEvent('error', '推送设备注销失败', [
                'user_id' => $userId,
                'device_id' => $deviceId,
                'error' => $e->getMessage(),
            ]);

            return $this->error('设备注销失败，请稍后重试', 500);
        }
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
            $settings = self::loadUserSettings($userId);

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
                self::logNotificationEvent('info', '通知发送被用户设置拦截', [
                    'user_id' => $userId,
                    'type' => $type,
                    'setting_field' => $typeField,
                    'data' => $data,
                ]);

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
                self::logNotificationEvent('info', '通知已保存，设备推送关闭', [
                    'user_id' => $userId,
                    'notification_id' => $notificationId,
                    'type' => $type,
                    'data' => $data,
                ]);

                return [
                    'success' => true,
                    'stored' => true,
                    'notification_id' => $notificationId,
                    'push_success' => false,
                    'message' => '通知已保存，用户已关闭设备推送',
                ];
            }

            if ($settings && !self::canSendDuringCurrentTime($settings)) {
                self::logNotificationEvent('info', '通知已保存，当前命中免打扰时段', [
                    'user_id' => $userId,
                    'notification_id' => $notificationId,
                    'type' => $type,
                    'quiet_hours_start' => $settings['quiet_hours_start'] ?? null,
                    'quiet_hours_end' => $settings['quiet_hours_end'] ?? null,
                ]);

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

            self::logNotificationEvent($pushSuccess ? 'info' : 'warning', '通知发送结果', [
                'user_id' => $userId,
                'notification_id' => $notificationId,
                'type' => $type,
                'push_success' => $pushSuccess,
                'push_result' => $pushResult,
                'data' => $data,
            ]);

            return [
                'success' => true,
                'stored' => true,
                'notification_id' => $notificationId,
                'push_success' => $pushSuccess,
                'push' => $pushResult,
                'message' => $pushSuccess ? '通知发送成功' : '通知已保存，但推送未送达',
            ];
        } catch (\Throwable $e) {
            self::logNotificationEvent('error', '发送通知失败', [
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'data' => $data,
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
     * 读取用户通知设置（兼容宽表/按 type 分行两种结构）
     */
    protected static function loadUserSettings(int $userId): array
    {
        $columns = self::getNotificationSettingColumns();
        if (empty($columns)) {
            return self::buildSettingsPayload([]);
        }

        if (self::usesNarrowNotificationSettingTable($columns)) {
            $rows = self::loadNarrowSettingsRows($userId);
            return self::buildSettingsPayload($rows);
        }

        $row = self::loadWideSettingsRow($userId);
        return self::buildSettingsPayload($row ?: []);
    }

    protected static function loadNarrowSettingsRows(int $userId): array
    {
        $rows = Db::name('tc_notification_setting')
            ->where('user_id', $userId)
            ->select()
            ->toArray();

        if (!empty($rows)) {
            return $rows;
        }

        return Db::name('tc_notification_setting')
            ->where('user_id', 0)
            ->select()
            ->toArray();
    }

    protected static function loadWideSettingsRow(int $userId): array
    {
        $row = Db::name('tc_notification_setting')
            ->where('user_id', $userId)
            ->find();

        if (is_array($row) && !empty($row)) {
            return $row;
        }

        $defaultRow = Db::name('tc_notification_setting')
            ->where('user_id', 0)
            ->find();

        return is_array($defaultRow) ? $defaultRow : [];
    }


    /**
     * 获取通知设置表字段
     */
    protected static function getNotificationSettingColumns(): array
    {
        return SchemaInspector::getTableColumns('tc_notification_setting');
    }

    /**
     * 判断是否为按 type + enabled 分行存储的窄表
     */
    protected static function usesNarrowNotificationSettingTable(array $columns): bool
    {
        if (!isset($columns['type']) || !isset($columns['enabled'])) {
            return false;
        }

        foreach (array_merge(self::SETTING_BOOLEAN_FIELDS, self::SETTING_TIME_FIELDS) as $field) {
            if (isset($columns[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * 过滤宽表可写字段
     */
    protected static function filterWideNotificationSettingPayload(array $payload, array $columns): array
    {
        $filtered = [];
        foreach (array_merge(self::SETTING_BOOLEAN_FIELDS, self::SETTING_TIME_FIELDS) as $field) {
            if (isset($columns[$field]) && array_key_exists($field, $payload)) {
                $filtered[$field] = $payload[$field];
            }
        }

        return $filtered;
    }

    /**
     * 获取推送设备表字段
     */
    protected static function getPushDeviceColumns(): array
    {
        return SchemaInspector::getTableColumns('tc_push_device');
    }

    /**
     * 构建设备注册写入数据
     */
    protected static function buildPushDevicePayload(int $userId, string $platform, string $deviceToken, string $deviceId, array $columns): array
    {
        $now = date('Y-m-d H:i:s');
        $payload = [
            'user_id' => $userId,
            'platform' => $platform,
            'device_id' => $deviceId,
        ];

        if (isset($columns['device_token'])) {
            $payload['device_token'] = $deviceToken;
        }
        if (isset($columns['token'])) {
            $payload['token'] = $deviceToken;
        }
        if (isset($columns['is_active'])) {
            $payload['is_active'] = 1;
        }
        if (isset($columns['last_active_at'])) {
            $payload['last_active_at'] = $now;
        }
        if (isset($columns['last_used_at'])) {
            $payload['last_used_at'] = $now;
        }
        if (isset($columns['created_at'])) {
            $payload['created_at'] = $now;
        }

        return $payload;
    }

    /**
     * 判断数组是否为顺序列表
     */
    protected static function isListArray(array $value): bool
    {
        if ($value === []) {
            return true;
        }

        return array_keys($value) === range(0, count($value) - 1);
    }

    /**
     * 构建标准化通知设置
     */
    protected static function buildSettingsPayload(array $settings): array
    {
        if (self::isListArray($settings)) {
            $rowMap = [];
            foreach ($settings as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $type = trim((string) ($row['type'] ?? ''));
                if ($type === '') {
                    continue;
                }
                $rowMap[$type] = self::normalizeBoolFlag($row['enabled'] ?? 1);
            }

            return [
                'daily_fortune' => $rowMap['daily_fortune'] ?? 1,
                'system_notice' => $rowMap['system_notice'] ?? 1,
                'activity' => $rowMap['activity'] ?? 1,
                'recharge' => $rowMap['recharge'] ?? 1,
                'points_change' => $rowMap['points_change'] ?? 1,
                'push_enabled' => $rowMap['push_enabled'] ?? 1,
                'sound_enabled' => $rowMap['sound_enabled'] ?? 1,
                'vibration_enabled' => $rowMap['vibration_enabled'] ?? 1,
                'quiet_hours_start' => '22:00',
                'quiet_hours_end' => '08:00',
            ];
        }

        return [
            'daily_fortune' => self::normalizeBoolFlag($settings['daily_fortune'] ?? 1),
            'system_notice' => self::normalizeBoolFlag($settings['system_notice'] ?? 1),
            'activity' => self::normalizeBoolFlag($settings['activity'] ?? 1),
            'recharge' => self::normalizeBoolFlag($settings['recharge'] ?? 1),
            'points_change' => self::normalizeBoolFlag($settings['points_change'] ?? 1),
            'push_enabled' => self::normalizeBoolFlag($settings['push_enabled'] ?? 1),
            'sound_enabled' => self::normalizeBoolFlag($settings['sound_enabled'] ?? 1),
            'vibration_enabled' => self::normalizeBoolFlag($settings['vibration_enabled'] ?? 1),
            'quiet_hours_start' => self::isValidHourMinute((string) ($settings['quiet_hours_start'] ?? '22:00'))
                ? (string) $settings['quiet_hours_start']
                : '22:00',
            'quiet_hours_end' => self::isValidHourMinute((string) ($settings['quiet_hours_end'] ?? '08:00'))
                ? (string) $settings['quiet_hours_end']
                : '08:00',
        ];
    }

    /**
     * 归一化布尔开关
     */
    protected static function normalizeBoolFlag(mixed $value): int
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_numeric($value)) {
            return (int) ((int) $value !== 0);
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true) ? 1 : 0;
    }

    /**
     * 验证 HH:MM 时间格式
     */
    protected static function isValidHourMinute(string $value): bool
    {
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value) === 1;
    }

    /**
     * 检查当前是否可发送推送
     */
    protected static function canSendDuringCurrentTime(array $settings): bool
    {
        $start = trim((string) ($settings['quiet_hours_start'] ?? ''));
        $end = trim((string) ($settings['quiet_hours_end'] ?? ''));

        if (!self::isValidHourMinute($start) || !self::isValidHourMinute($end) || $start === $end) {
            return true;
        }

        $now = date('H:i');
        $inQuietHours = $start < $end
            ? ($now >= $start && $now < $end)
            : ($now >= $start || $now < $end);

        return !$inQuietHours;
    }

    /**
     * 统一记录通知日志
     */
    protected static function logNotificationEvent(string $level, string $message, array $context = []): void
    {
        $sanitizedContext = self::sanitizeNotificationLogContext($context);

        switch ($level) {

            case 'error':
                Log::error($message, $sanitizedContext);
                break;
            case 'warning':
                Log::warning($message, $sanitizedContext);
                break;
            default:
                Log::info($message, $sanitizedContext);
                break;
        }
    }

    /**
     * 递归脱敏日志上下文
     */
    protected static function sanitizeNotificationLogContext(mixed $value, ?string $field = null): mixed
    {
        if (is_array($value)) {
            $sanitized = [];
            foreach ($value as $key => $item) {
                $sanitized[$key] = self::sanitizeNotificationLogContext($item, is_string($key) ? $key : $field);
            }


            return $sanitized;
        }

        if (!is_string($value)) {
            return $value;
        }

        $normalizedField = strtolower((string) $field);
        foreach (['token', 'device_id', 'registration_id', 'secret'] as $sensitiveField) {
            if ($normalizedField !== '' && str_contains($normalizedField, $sensitiveField)) {
                return self::maskLogValue($value);
            }
        }

        return mb_strlen($value) > 300 ? mb_substr($value, 0, 300) . '…' : $value;
    }

    /**
     * 掩码标识类字符串
     */
    protected static function maskLogValue(string $value): string
    {
        $length = mb_strlen($value);
        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return mb_substr($value, 0, 4) . str_repeat('*', max(0, $length - 8)) . mb_substr($value, -4);
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
