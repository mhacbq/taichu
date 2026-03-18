<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Db;
use think\facade\Env;
use think\facade\Log;

/**
 * 推送通知服务类
 * 支持通过 HTTP 直连 JPush / Firebase FCM / 自定义 Webhook
 */
class PushService
{
    /**
     * 发送推送通知（兼容旧调用，返回是否至少一台设备发送成功）
     */
    public static function sendPush(int $userId, string $title, string $content, array $extra = []): bool
    {
        $result = self::sendPushDetailed($userId, $title, $content, $extra);

        return (bool) ($result['success'] ?? false);
    }

    /**
     * 发送推送通知并返回详细结果
     */
    public static function sendPushDetailed(int $userId, string $title, string $content, array $extra = []): array
    {
        if (!SchemaInspector::tableExists('tc_push_device')) {
            return [
                'success' => false,
                'code' => 'NO_DEVICE_TABLE',
                'message' => '推送设备表不存在',
                'provider' => self::getProvider(),
                'attempted' => 0,
                'succeeded' => 0,
                'failed' => 0,
                'errors' => [],
            ];
        }

        $devices = Db::name('tc_push_device')
            ->where('user_id', $userId)
            ->where('is_active', 1)
            ->select()
            ->toArray();

        if (empty($devices)) {
            return [
                'success' => false,
                'code' => 'NO_DEVICE',
                'message' => '没有可用推送设备',
                'provider' => self::getProvider(),
                'attempted' => 0,
                'succeeded' => 0,
                'failed' => 0,
                'errors' => [],
            ];
        }

        $provider = self::getProvider();
        if ($provider === '') {
            Log::warning('推送发送被跳过：未配置推送服务提供商', [
                'user_id' => $userId,
                'device_count' => count($devices),
            ]);

            return [
                'success' => false,
                'code' => 'NO_PROVIDER',
                'message' => '未配置推送服务提供商',
                'provider' => '',
                'attempted' => count($devices),
                'succeeded' => 0,
                'failed' => count($devices),
                'errors' => [[
                    'message' => '请配置 PUSH_PROVIDER 及对应凭据',
                ]],
            ];
        }

        $attempted = 0;
        $succeeded = 0;
        $failed = 0;
        $errors = [];

        foreach ($devices as $device) {
            $attempted++;
            $device = self::normalizePushDeviceRow($device);
            $token = (string) ($device['device_token'] ?? '');

            if ($token === '') {
                $failed++;
                $errors[] = [
                    'device_id' => (int) ($device['id'] ?? 0),
                    'platform' => (string) ($device['platform'] ?? ''),
                    'message' => '推送设备缺少可用令牌',
                ];

                Log::warning('推送设备缺少可用令牌', [
                    'user_id' => $userId,
                    'device_id' => (int) ($device['id'] ?? 0),
                    'platform' => (string) ($device['platform'] ?? ''),
                ]);
                continue;
            }

            try {
                $result = self::dispatchToProvider($provider, $device, $title, $content, $extra);
                $isSuccess = (bool) ($result['success'] ?? false);

                if ($isSuccess) {
                    $succeeded++;
                    continue;
                }

                $failed++;
                $errors[] = [
                    'device_id' => (int) ($device['id'] ?? 0),
                    'platform' => (string) ($device['platform'] ?? ''),
                    'message' => (string) ($result['message'] ?? '未知推送错误'),
                ];

                if (!empty($result['deactivate_device']) && !empty($device['id'])) {
                    self::deactivateDevice((int) $device['id'], (string) ($result['message'] ?? 'token失效'));
                }
            } catch (\Throwable $e) {
                $failed++;
                $errors[] = [
                    'device_id' => (int) ($device['id'] ?? 0),
                    'platform' => (string) ($device['platform'] ?? ''),
                    'message' => '推送异常：' . $e->getMessage(),
                ];

                Log::error('推送发送异常', [
                    'provider' => $provider,
                    'user_id' => $userId,
                    'device_id' => (int) ($device['id'] ?? 0),
                    'platform' => (string) ($device['platform'] ?? ''),
                    'token' => self::maskToken((string) ($device['device_token'] ?? '')),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $success = $succeeded > 0;
        $message = $success
            ? ($failed > 0 ? '部分设备推送成功' : '推送成功')
            : '推送失败';

        Log::info('推送发送结果', [
            'provider' => $provider,
            'user_id' => $userId,
            'attempted' => $attempted,
            'succeeded' => $succeeded,
            'failed' => $failed,
        ]);

        return [
            'success' => $success,
            'code' => $success ? ($failed > 0 ? 'PARTIAL_SUCCESS' : 'SUCCESS') : 'FAILED',
            'message' => $message,
            'provider' => $provider,
            'attempted' => $attempted,
            'succeeded' => $succeeded,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * 广播通知（全员推送）
     */
    public static function broadcast(string $title, string $content, array $extra = []): bool
    {
        $result = self::broadcastDetailed($title, $content, $extra);

        return (bool) ($result['success'] ?? false);
    }

    /**
     * 广播通知详细结果
     */
    public static function broadcastDetailed(string $title, string $content, array $extra = []): array
    {
        if (!SchemaInspector::tableExists('tc_push_device')) {
            return [
                'success' => false,
                'message' => '推送设备表不存在',
                'total_users' => 0,
                'success_users' => 0,
                'failed_users' => 0,
                'details' => [],
            ];
        }

        $userIds = Db::name('tc_push_device')
            ->where('is_active', 1)
            ->distinct(true)
            ->column('user_id');

        if (empty($userIds)) {
            return [
                'success' => false,
                'message' => '没有可广播的活跃设备',
                'total_users' => 0,
                'success_users' => 0,
                'failed_users' => 0,
                'details' => [],
            ];
        }

        $successUsers = 0;
        $failedUsers = 0;
        $details = [];

        foreach ($userIds as $userId) {
            $result = self::sendPushDetailed((int) $userId, $title, $content, $extra);
            $details[] = [
                'user_id' => (int) $userId,
                'success' => (bool) ($result['success'] ?? false),
                'message' => (string) ($result['message'] ?? ''),
            ];

            if (!empty($result['success'])) {
                $successUsers++;
            } else {
                $failedUsers++;
            }
        }

        return [
            'success' => $successUsers > 0,
            'message' => $successUsers > 0 ? '广播完成' : '广播失败',
            'total_users' => count($userIds),
            'success_users' => $successUsers,
            'failed_users' => $failedUsers,
            'details' => $details,
        ];
    }

    /**
     * 根据 provider 分发推送
     */
    protected static function dispatchToProvider(string $provider, array $device, string $title, string $content, array $extra): array
    {
        return match ($provider) {
            'jpush' => self::sendViaJPush($device, $title, $content, $extra),
            'fcm' => self::sendViaFcm($device, $title, $content, $extra),
            'webhook' => self::sendViaWebhook($device, $title, $content, $extra),
            default => [
                'success' => false,
                'message' => '不支持的推送服务提供商：' . $provider,
            ],
        };
    }

    /**
     * 通过极光推送发送
     */
    protected static function sendViaJPush(array $device, string $title, string $content, array $extra): array
    {
        $appKey = self::getEnvValue(['PUSH_JPUSH_APP_KEY', 'push.jpush_app_key']);
        $masterSecret = self::getEnvValue(['PUSH_JPUSH_MASTER_SECRET', 'push.jpush_master_secret']);

        if ($appKey === '' || $masterSecret === '') {
            return [
                'success' => false,
                'message' => '缺少 JPush 配置',
            ];
        }

        $payload = [
            'platform' => [$device['platform'] === 'web' ? 'android' : $device['platform']],
            'audience' => [
                'registration_id' => [(string) ($device['device_token'] ?? '')],
            ],
            'notification' => [
                'alert' => $content,
                'android' => [
                    'title' => $title,
                    'extras' => $extra,
                ],
                'ios' => [
                    'alert' => $content,
                    'sound' => 'default',
                    'extras' => $extra,
                ],
            ],
            'options' => [
                'apns_production' => self::getEnvValue(['PUSH_APNS_PRODUCTION', 'push.apns_production'], 'false') === 'true',
            ],
        ];

        $response = self::sendHttpJson(
            'https://api.jpush.cn/v3/push',
            $payload,
            [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($appKey . ':' . $masterSecret),
            ]
        );

        $body = self::decodeJsonBody($response['body']);
        $httpCode = (int) ($response['status'] ?? 0);

        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'message' => 'JPush 发送成功'];
        }

        $errorMessage = (string) ($body['error']['message'] ?? $body['message'] ?? 'JPush 发送失败');
        $deactivate = str_contains(strtolower($errorMessage), 'registration') || str_contains(strtolower($errorMessage), 'invalid');

        return [
            'success' => false,
            'message' => $errorMessage,
            'deactivate_device' => $deactivate,
        ];
    }

    /**
     * 通过 Firebase FCM 发送
     */
    protected static function sendViaFcm(array $device, string $title, string $content, array $extra): array
    {
        $serverKey = self::getEnvValue(['PUSH_FCM_SERVER_KEY', 'push.fcm_server_key']);
        if ($serverKey === '') {
            return [
                'success' => false,
                'message' => '缺少 FCM Server Key 配置',
            ];
        }

        $payload = [
            'to' => (string) ($device['device_token'] ?? ''),
            'notification' => [
                'title' => $title,
                'body' => $content,
            ],
            'data' => $extra,
        ];

        $response = self::sendHttpJson(
            'https://fcm.googleapis.com/fcm/send',
            $payload,
            [
                'Content-Type: application/json',
                'Authorization: key=' . $serverKey,
            ]
        );

        $body = self::decodeJsonBody($response['body']);
        $httpCode = (int) ($response['status'] ?? 0);
        $resultItem = $body['results'][0] ?? [];
        $errorCode = (string) ($resultItem['error'] ?? '');

        if ($httpCode >= 200 && $httpCode < 300 && (int) ($body['success'] ?? 0) > 0) {
            return ['success' => true, 'message' => 'FCM 发送成功'];
        }

        $invalidErrors = ['NotRegistered', 'InvalidRegistration', 'MismatchSenderId'];

        return [
            'success' => false,
            'message' => $errorCode !== '' ? 'FCM 发送失败：' . $errorCode : 'FCM 发送失败',
            'deactivate_device' => in_array($errorCode, $invalidErrors, true),
        ];
    }

    /**
     * 通过自定义 Webhook 发送
     */
    protected static function sendViaWebhook(array $device, string $title, string $content, array $extra): array
    {
        $webhookUrl = self::getEnvValue(['PUSH_WEBHOOK_URL', 'push.webhook_url']);
        if ($webhookUrl === '') {
            return [
                'success' => false,
                'message' => '缺少 Webhook URL 配置',
            ];
        }
        if (!filter_var($webhookUrl, FILTER_VALIDATE_URL)) {
            return [
                'success' => false,
                'message' => 'Webhook URL 配置无效',
            ];
        }


        $headers = ['Content-Type: application/json'];
        $bearerToken = self::getEnvValue(['PUSH_WEBHOOK_BEARER', 'push.webhook_bearer']);
        if ($bearerToken !== '') {
            $headers[] = 'Authorization: Bearer ' . $bearerToken;
        }

        $response = self::sendHttpJson($webhookUrl, [
            'platform' => (string) ($device['platform'] ?? ''),
            'device_token' => (string) ($device['device_token'] ?? ''),
            'device_id' => (string) ($device['device_id'] ?? ''),
            'title' => $title,
            'content' => $content,
            'extra' => $extra,
        ], $headers);

        $httpCode = (int) ($response['status'] ?? 0);
        if ($httpCode >= 200 && $httpCode < 300) {
            return ['success' => true, 'message' => 'Webhook 推送成功'];
        }

        return [
            'success' => false,
            'message' => 'Webhook 推送失败，HTTP ' . $httpCode,
        ];
    }

    /**
     * 发送 JSON HTTP 请求
     */
    protected static function sendHttpJson(string $url, array $payload, array $headers = []): array
    {
        if (!function_exists('curl_init')) {
            throw new \RuntimeException('当前环境未安装 cURL 扩展');
        }

        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('初始化 HTTP 客户端失败');
        }

        $verifySsl = self::getEnvValue(['PUSH_SSL_VERIFY', 'push.ssl_verify'], 'true') !== 'false';

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifySsl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $verifySsl ? 2 : 0);


        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false) {
            throw new \RuntimeException('HTTP 请求失败：' . $error);
        }

        return [
            'status' => $status,
            'body' => (string) $body,
        ];
    }

    /**
     * 归一化推送设备记录，兼容 token/device_token 等新旧字段
     */
    protected static function normalizePushDeviceRow(array $device): array
    {
        $device['device_token'] = self::extractDeviceToken($device);

        if (!isset($device['last_active_at']) && isset($device['last_used_at'])) {
            $device['last_active_at'] = $device['last_used_at'];
        }
        if (!isset($device['last_used_at']) && isset($device['last_active_at'])) {
            $device['last_used_at'] = $device['last_active_at'];
        }

        return $device;
    }

    /**
     * 提取设备令牌
     */
    protected static function extractDeviceToken(array $device): string
    {
        $token = trim((string) ($device['device_token'] ?? ''));
        if ($token !== '') {
            return $token;
        }

        return trim((string) ($device['token'] ?? ''));
    }

    /**
     * 注销失效设备
     */
    protected static function deactivateDevice(int $deviceId, string $reason = ''): void
    {
        if (!SchemaInspector::tableExists('tc_push_device')) {
            return;
        }

        Db::name('tc_push_device')
            ->where('id', $deviceId)
            ->update([
                'is_active' => 0,
            ]);

        Log::warning('检测到失效推送设备，已自动停用', [
            'device_id' => $deviceId,
            'reason' => $reason,
        ]);
    }

    /**
     * 获取推送提供商
     */
    protected static function getProvider(): string
    {
        $provider = strtolower(trim(self::getEnvValue(['PUSH_PROVIDER', 'push.provider'])));

        return match ($provider) {
            'firebase', 'firebase-fcm' => 'fcm',
            'jiguang', '极光' => 'jpush',
            default => $provider,
        };
    }


    /**
     * 读取环境变量，兼容多种命名
     */
    protected static function getEnvValue(array $keys, string $default = ''): string
    {
        foreach ($keys as $key) {
            $value = Env::get($key);
            if ($value !== null && $value !== '') {
                return trim((string) $value);
            }
        }

        return $default;
    }

    /**
     * 解码 JSON 响应体
     */
    protected static function decodeJsonBody(string $body): array
    {
        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * 避免日志直接输出完整 token
     */
    protected static function maskToken(string $token): string
    {
        $length = strlen($token);
        if ($length <= 8) {
            return $token;
        }

        return substr($token, 0, 4) . str_repeat('*', max(0, $length - 8)) . substr($token, -4);
    }
}
