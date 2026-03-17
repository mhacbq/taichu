<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Log;
use think\facade\Db;

/**
 * 推送通知服务类
 * 集成第三方推送平台（极光推送 JPush / Firebase FCM）
 */
class PushService
{
    /**
     * 发送推送通知
     * 
     * @param int $userId 用户ID
     * @param string $title 标题
     * @param string $content 内容
     * @param array $extra 额外参数
     * @return bool
     */
    public static function sendPush(int $userId, string $title, string $content, array $extra = []): bool
    {
        // 1. 获取用户活跃设备令牌
        $devices = Db::name('tc_push_device')
            ->where('user_id', $userId)
            ->where('is_active', 1)
            ->select();
        
        if ($devices->isEmpty()) {
            return false;
        }

        $success = true;
        foreach ($devices as $device) {
            $res = false;
            if ($device['platform'] === 'ios' || $device['platform'] === 'android') {
                // TODO: 实际集成 JPush 或 FCM SDK
                // 示例：JPushService::push($device['device_token'], $title, $content, $extra);
                Log::info("模拟推送消息: [{$device['platform']}] to {$device['device_token']} - Title: {$title}");
                $res = true; 
            } elseif ($device['platform'] === 'web') {
                // Web Push
                Log::info("模拟Web推送: to {$device['device_token']}");
                $res = true;
            }
            
            if (!$res) $success = false;
        }

        return $success;
    }

    /**
     * 广播通知（全员推送）
     */
    public static function broadcast(string $title, string $content, array $extra = []): bool
    {
        Log::info("全员广播推送: {$title}");
        // 实际逻辑应调用推送平台的 broadcast API
        return true;
    }
}
