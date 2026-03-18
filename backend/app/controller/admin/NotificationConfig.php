<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use app\controller\Notification as NotificationController;
use app\service\SchemaInspector;
use think\Request;
use think\facade\Db;
use think\facade\Env;

/**
 * 后台通知配置控制器
 */
class NotificationConfig extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    protected const SETTINGS_SCOPE_USER_ID = 0;
    protected const BOOLEAN_FIELDS = [
        'daily_fortune',
        'system_notice',
        'activity',
        'recharge',
        'points_change',
        'push_enabled',
        'sound_enabled',
        'vibration_enabled',
    ];
    protected const TIME_FIELDS = ['quiet_hours_start', 'quiet_hours_end'];

    public function getSettings()
    {
        if (!$this->canManageNotificationConfig()) {
            return $this->error('无权限查看通知配置', 403);
        }

        try {
            $columns = $this->getNotificationSettingColumns();
            $settings = $this->loadSettings(self::SETTINGS_SCOPE_USER_ID, $columns);

            return $this->success([
                'settings' => $settings,
                'summary' => [
                    'scope_user_id' => self::SETTINGS_SCOPE_USER_ID,
                    'table_mode' => $this->usesNarrowNotificationSettingTable($columns) ? 'narrow' : 'wide',
                    'provider' => $this->resolvePushProvider(),
                    'active_device_count' => $this->countActiveDevices(),
                    'push_table_ready' => SchemaInspector::tableExists('tc_push_device'),
                ],
            ], '获取成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_notification_config_get', $e, '获取通知配置失败，请稍后重试');
        }
    }

    public function saveSettings(Request $request)
    {
        if (!$this->canManageNotificationConfig()) {
            return $this->error('无权限修改通知配置', 403);
        }

        $payload = $request->isPut() ? $request->put() : $request->post();
        if (!is_array($payload)) {
            $payload = [];
        }

        try {
            $columns = $this->getNotificationSettingColumns();
            if (empty($columns)) {
                return $this->error('通知设置表不存在，请先执行 database/20260317_create_notification_tables.sql', 500);
            }

            $normalized = $this->normalizeIncomingSettings($payload);
            if (empty($normalized)) {
                return $this->error('没有可更新的通知设置', 400);
            }

            Db::startTrans();
            $this->persistSettings(self::SETTINGS_SCOPE_USER_ID, $normalized, $columns);
            Db::commit();

            $settings = $this->loadSettings(self::SETTINGS_SCOPE_USER_ID, $columns);
            $this->logOperation('save_notification_config', 'config', [
                'target_type' => 'notification_config',
                'detail' => '更新后台通知默认配置',
                'after_data' => $settings,
            ]);

            return $this->success($settings, '保存成功');
        } catch (\InvalidArgumentException $e) {
            Db::rollback();
            return $this->respondBusinessException($e, 'admin_notification_config_validation', $e->getMessage(), 400);
        } catch (\Throwable $e) {
            Db::rollback();
            return $this->respondSystemException('admin_notification_config_save', $e, '保存通知配置失败，请稍后重试');
        }
    }

    public function sendTest(Request $request)
    {
        if (!$this->canManageNotificationConfig()) {
            return $this->error('无权限发送测试通知', 403);
        }

        $payload = $request->post();
        $userId = filter_var($payload['user_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($userId === false || $userId === null) {
            return $this->error('测试用户ID无效，请填写有效用户ID', 400);
        }

        $title = trim((string) ($payload['title'] ?? '后台测试通知')) ?: '后台测试通知';
        $content = trim((string) ($payload['content'] ?? '这是一条来自管理后台的测试通知，用于验证通知配置是否生效。'))
            ?: '这是一条来自管理后台的测试通知，用于验证通知配置是否生效。';

        try {
            $result = NotificationController::sendNotificationDetailed(
                (int) $userId,
                'test',
                $title,
                $content,
                [
                    'source' => 'admin_notification_config',
                    'admin_id' => $this->getAdminId(),
                ]
            );

            $this->logOperation('send_test_notification', 'config', [
                'target_id' => (int) $userId,
                'target_type' => 'notification_test',
                'detail' => '发送后台测试通知',
                'after_data' => $result,
            ]);

            if (!($result['success'] ?? false)) {
                return $this->error((string) ($result['message'] ?? '测试通知发送失败'), 400, $result);
            }

            $message = !empty($result['push_success'])
                ? '测试通知已发送并触达推送通道'
                : (string) ($result['message'] ?? '测试通知已创建');

            return $this->success($result, $message);
        } catch (\Throwable $e) {
            return $this->respondSystemException('admin_notification_config_test', $e, '发送测试通知失败，请稍后重试', [
                'user_id' => (int) $userId,
            ]);
        }
    }

    protected function canManageNotificationConfig(): bool
    {
        return $this->hasAnyAdminRole(['admin', 'operator'])
            || $this->hasAdminPermission('config_manage');
    }

    protected function getNotificationSettingColumns(): array
    {
        return SchemaInspector::getTableColumns('tc_notification_setting');
    }

    protected function usesNarrowNotificationSettingTable(array $columns): bool
    {
        if (!isset($columns['type']) || !isset($columns['enabled'])) {
            return false;
        }

        foreach (array_merge(self::BOOLEAN_FIELDS, self::TIME_FIELDS) as $field) {
            if (isset($columns[$field])) {
                return false;
            }
        }

        return true;
    }

    protected function normalizeIncomingSettings(array $payload): array
    {
        $normalized = [];

        foreach (self::BOOLEAN_FIELDS as $field) {
            if (!array_key_exists($field, $payload)) {
                continue;
            }
            $normalized[$field] = $this->normalizeBoolFlag($payload[$field]);
        }

        foreach (self::TIME_FIELDS as $field) {
            if (!array_key_exists($field, $payload)) {
                continue;
            }

            $value = trim((string) $payload[$field]);
            if (!$this->isValidHourMinute($value)) {
                throw new \InvalidArgumentException('免打扰时间格式无效，请使用 HH:MM');
            }
            $normalized[$field] = $value;
        }

        return $normalized;
    }

    protected function persistSettings(int $userId, array $payload, array $columns): void
    {
        if ($this->usesNarrowNotificationSettingTable($columns)) {
            $this->persistNarrowSettings($userId, $payload);
            return;
        }

        $saveData = [];
        foreach (array_merge(self::BOOLEAN_FIELDS, self::TIME_FIELDS) as $field) {
            if (isset($columns[$field]) && array_key_exists($field, $payload)) {
                $saveData[$field] = $payload[$field];
            }
        }

        if (empty($saveData)) {
            throw new \InvalidArgumentException('当前通知设置表缺少可写字段');
        }

        $now = date('Y-m-d H:i:s');
        $saveData['updated_at'] = $now;

        $existing = Db::name('tc_notification_setting')->where('user_id', $userId)->find();
        if ($existing) {
            Db::name('tc_notification_setting')->where('user_id', $userId)->update($saveData);
            return;
        }

        $saveData['user_id'] = $userId;
        if (isset($columns['created_at'])) {
            $saveData['created_at'] = $now;
        }
        Db::name('tc_notification_setting')->insert($saveData);
    }

    protected function persistNarrowSettings(int $userId, array $payload): void
    {
        $now = date('Y-m-d H:i:s');
        foreach ($payload as $type => $value) {
            if (!in_array($type, self::BOOLEAN_FIELDS, true)) {
                continue;
            }

            $query = Db::name('tc_notification_setting')
                ->where('user_id', $userId)
                ->where('type', $type);
            $existing = $query->find();
            if ($existing) {
                $query->update([
                    'enabled' => $value,
                    'updated_at' => $now,
                ]);
                continue;
            }

            Db::name('tc_notification_setting')->insert([
                'user_id' => $userId,
                'type' => $type,
                'enabled' => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    protected function loadSettings(int $userId, array $columns): array
    {
        if (empty($columns)) {
            return $this->buildSettingsPayload([]);
        }

        if ($this->usesNarrowNotificationSettingTable($columns)) {
            $rows = Db::name('tc_notification_setting')
                ->where('user_id', $userId)
                ->select()
                ->toArray();

            return $this->buildSettingsPayload($rows);
        }

        $row = Db::name('tc_notification_setting')->where('user_id', $userId)->find();
        return $this->buildSettingsPayload(is_array($row) ? $row : []);
    }

    protected function buildSettingsPayload(array $settings): array
    {
        if ($this->isListArray($settings)) {
            $rowMap = [];
            foreach ($settings as $row) {
                if (!is_array($row)) {
                    continue;
                }
                $type = trim((string) ($row['type'] ?? ''));
                if ($type === '') {
                    continue;
                }
                $rowMap[$type] = $this->normalizeBoolFlag($row['enabled'] ?? 1);
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
            'daily_fortune' => $this->normalizeBoolFlag($settings['daily_fortune'] ?? 1),
            'system_notice' => $this->normalizeBoolFlag($settings['system_notice'] ?? 1),
            'activity' => $this->normalizeBoolFlag($settings['activity'] ?? 1),
            'recharge' => $this->normalizeBoolFlag($settings['recharge'] ?? 1),
            'points_change' => $this->normalizeBoolFlag($settings['points_change'] ?? 1),
            'push_enabled' => $this->normalizeBoolFlag($settings['push_enabled'] ?? 1),
            'sound_enabled' => $this->normalizeBoolFlag($settings['sound_enabled'] ?? 1),
            'vibration_enabled' => $this->normalizeBoolFlag($settings['vibration_enabled'] ?? 1),
            'quiet_hours_start' => $this->isValidHourMinute((string) ($settings['quiet_hours_start'] ?? '22:00'))
                ? (string) $settings['quiet_hours_start']
                : '22:00',
            'quiet_hours_end' => $this->isValidHourMinute((string) ($settings['quiet_hours_end'] ?? '08:00'))
                ? (string) $settings['quiet_hours_end']
                : '08:00',
        ];
    }

    protected function normalizeBoolFlag(mixed $value): int
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        if (is_numeric($value)) {
            return (int) ((int) $value !== 0);
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'on'], true) ? 1 : 0;
    }

    protected function isValidHourMinute(string $value): bool
    {
        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value) === 1;
    }

    protected function isListArray(array $value): bool
    {
        if ($value === []) {
            return true;
        }

        return array_keys($value) === range(0, count($value) - 1);
    }

    protected function resolvePushProvider(): string
    {
        $provider = strtolower(trim((string) Env::get('PUSH_PROVIDER', '')));

        return match ($provider) {
            'firebase', 'firebase-fcm' => 'fcm',
            'jiguang', '极光' => 'jpush',
            '' => '未配置',
            default => $provider,
        };
    }

    protected function countActiveDevices(): int
    {
        if (!SchemaInspector::tableExists('tc_push_device')) {
            return 0;
        }

        return (int) Db::name('tc_push_device')
            ->where('is_active', 1)
            ->count();
    }
}
