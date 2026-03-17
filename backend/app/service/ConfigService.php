<?php
declare(strict_types=1);

namespace app\service;

use app\model\SystemConfig;
use think\facade\Cache;

/**
 * 系统配置服务
 */
class ConfigService
{
    private const CACHE_KEY = 'system:config';
    private const CACHE_TTL = 3600; // 1小时缓存
    
    /**
     * 获取配置值（带缓存）
     */
    public static function get(string $key, $default = null)
    {
        // 先从缓存获取
        $cached = Cache::get(self::CACHE_KEY);
        if ($cached && isset($cached[$key])) {
            return $cached[$key];
        }
        
        // 从数据库获取并更新缓存
        $value = SystemConfig::getByKey($key, $default);
        self::refreshCache();
        
        return $value;
    }
    
    /**
     * 获取分类配置
     */
    public static function getCategory(string $category): array
    {
        $cacheKey = self::CACHE_KEY . ':category:' . $category;
        
        return Cache::remember($cacheKey, function () use ($category) {
            return SystemConfig::getByCategory($category);
        }, self::CACHE_TTL);
    }
    
    /**
     * 设置配置值
     */
    public static function set(string $key, $value): bool
    {
        $result = SystemConfig::setValue($key, $value);
        
        if ($result) {
            // 清除缓存
            self::clearCache();
        }
        
        return $result;
    }
    
    /**
     * 批量设置配置
     */
    public static function setBatch(array $configs): bool
    {
        $results = SystemConfig::setValues($configs);
        
        // 只要有成功的就清除缓存
        if (in_array(true, $results, true)) {
            self::clearCache();
        }
        
        return !in_array(false, $results, true);
    }
    
    /**
     * 获取所有配置（后台管理用）
     */
    public static function getAll(): array
    {
        return SystemConfig::getAllGrouped();
    }
    
    /**
     * 刷新缓存
     */
    public static function refreshCache(): void
    {
        $configs = SystemConfig::select();
        $cacheData = [];
        
        foreach ($configs as $config) {
            $cacheData[$config->config_key] = $config->typed_value;
        }
        
        Cache::set(self::CACHE_KEY, $cacheData, self::CACHE_TTL);
    }
    
    /**
     * 清除缓存
     */
    public static function clearCache(): void
    {
        Cache::delete(self::CACHE_KEY);
        
        // 清除分类缓存
        $categories = ['feature', 'vip', 'points', 'points_cost', 'limited_offer', 'package', 'new_user', 'recharge', 'report_tier'];
        foreach ($categories as $category) {
            Cache::delete(self::CACHE_KEY . ':category:' . $category);
        }
    }
    
    /**
     * 检查功能是否开启
     */
    public static function isFeatureEnabled(string $feature): bool
    {
        return self::get("feature_{$feature}_enabled", true);
    }
    
    /**
     * 获取功能开关状态（所有功能）
     */
    public static function getFeatureSwitches(): array
    {
        $features = [
            'vip' => 'VIP会员',
            'points' => '积分系统',
            'ai_analysis' => 'AI解盘',
            'yearly_fortune' => '流年运势',
            'dayun_analysis' => '大运分析',
            'dayun_chart' => '运势K线图',
            'hehun' => '八字合婚',
            'qiming' => '取名建议',
            'share_poster' => '分享海报',
            'invite' => '邀请好友',
            'limited_offer' => '限时优惠',
            'package' => '组合套餐',
            'report_tier' => '报告分层',
            'tasks' => '积分任务',
        ];
        
        $result = [];
        foreach ($features as $key => $name) {
            $result[$key] = [
                'name' => $name,
                'enabled' => self::isFeatureEnabled($key),
            ];
        }
        
        return $result;
    }
    
    /**
     * 获取客户端配置（公开接口用，去除敏感信息）
     */
    public static function getClientConfig(): array
    {
        return [
            'features' => self::getFeatureSwitches(),
            'points' => [
                'costs' => SystemConfig::getPointsCosts(),
                'tasks' => self::getPointsTasks(),
            ],
            'vip' => [
                'enabled' => self::isFeatureEnabled('vip'),
                'prices' => [
                    'month' => self::get('vip_month_price', 19.9),
                    'quarter' => self::get('vip_quarter_price', 49),
                    'year' => self::get('vip_year_price', 168),
                ],
                'benefits' => [
                    'daily_points_multiplier' => self::get('vip_daily_points_multiplier', 2),
                    'paipan_limit' => self::get('vip_paipan_limit', -1),
                    'unlock_basic_report' => self::get('vip_unlock_basic_report', true),
                    'unlock_hehun' => self::get('vip_unlock_hehun', true),
                    'unlock_qiming' => self::get('vip_unlock_qiming', false),
                ],
            ],
            'limited_offer' => [
                'enabled' => self::get('limited_offer_enabled', false),
                'discount' => self::get('limited_offer_discount', 50),
                'start_time' => self::get('limited_offer_start_time', ''),
                'end_time' => self::get('limited_offer_end_time', ''),
            ],
            'packages' => self::get('packages', []),
            'new_user' => [
                'enabled' => self::get('new_user_offer_enabled', true),
                'discount' => self::get('new_user_discount', 50),
                'valid_hours' => self::get('new_user_valid_hours', 24),
            ],
            'recharge' => [
                'enabled' => self::get('recharge_enabled', true),
                'ratio' => self::get('recharge_ratio', 10),
                'options' => self::get('recharge_options', []),
            ],
            'report_tier' => [
                'enabled' => self::get('report_tier_enabled', true),
                'basic_items' => self::get('basic_report_items', []),
                'premium_items' => self::get('premium_report_items', []),
                'premium_points' => self::get('premium_report_points', 50),
            ],
        ];
    }
    
    /**
     * 获取积分任务配置
     */
    public static function getPointsTasks(): array
    {
        $tasks = [
            'sign_daily' => ['name' => '每日签到', 'points' => 'points_sign_daily'],
            'sign_continuous_7' => ['name' => '连续7天签到', 'points' => 'points_sign_continuous_7'],
            'sign_continuous_30' => ['name' => '连续30天签到', 'points' => 'points_sign_continuous_30'],
            'share_app' => ['name' => '分享小程序', 'points' => 'points_share_app'],
            'invite_friend' => ['name' => '邀请好友', 'points' => 'points_invite_friend'],
            'complete_profile' => ['name' => '完善资料', 'points' => 'points_complete_profile'],
            'first_paipan' => ['name' => '首次排盘', 'points' => 'points_first_paipan'],
            'bind_wechat' => ['name' => '绑定微信', 'points' => 'points_bind_wechat'],
            'follow_mp' => ['name' => '关注公众号', 'points' => 'points_follow_mp'],
            'browse_article' => ['name' => '浏览文章', 'points' => 'points_browse_article'],
        ];
        
        $result = [];
        foreach ($tasks as $key => $task) {
            $result[$key] = [
                'name' => $task['name'],
                'points' => self::get($task['points'], 0),
            ];
        }
        
        return $result;
    }
    
    /**
     * 获取当前有效折扣
     */
    public static function getCurrentDiscount(): int
    {
        // 检查限时优惠
        if (self::get('limited_offer_enabled', false)) {
            $startTime = self::get('limited_offer_start_time');
            $endTime = self::get('limited_offer_end_time');
            $now = date('Y-m-d H:i:s');
            
            if ((!$startTime || $now >= $startTime) && (!$endTime || $now <= $endTime)) {
                return (int) self::get('limited_offer_discount', 50);
            }
        }
        
        return 0; // 无折扣
    }
    
    /**
     * 计算实际积分消耗
     */
    public static function calculatePointsCost(string $feature, $basePoints = null, bool $isNewUser = false): array
    {
        $originalPoints = self::normalizePointsCostBase($feature, $basePoints);
        $discount = 0;
        $reason = '';
        
        // 新用户优惠
        if ($isNewUser && self::get('new_user_offer_enabled', true)) {
            $newUserDiscount = (int) self::get('new_user_discount', 50);
            if ($newUserDiscount > $discount) {
                $discount = $newUserDiscount;
                $reason = '新用户专享';
            }
        }
        
        // 限时优惠
        $limitedDiscount = self::getCurrentDiscount();
        if ($limitedDiscount > $discount) {
            $discount = $limitedDiscount;
            $reason = '限时特惠';
        }
        
        $finalPoints = (int) ($originalPoints * (100 - $discount) / 100);
        if ($originalPoints > 0) {
            $finalPoints = max(1, $finalPoints); // 非免费项最低 1 积分
        }
        
        return [
            'original' => $originalPoints,
            'final' => $finalPoints,
            'discount' => $discount,
            'reason' => $reason,
            'saved' => max(0, $originalPoints - $finalPoints),
        ];
    }

    protected static function normalizePointsCostBase(string $feature, $basePoints): int
    {
        if (is_numeric($basePoints)) {
            return max(0, (int) $basePoints);
        }

        $configKeyMap = [
            'bazi' => 'points_cost_bazi',
            'hehun' => 'points_cost_hehun',
            'tarot' => 'points_cost_tarot',
            'liuyao' => 'points_cost_liuyao',
            'daily' => 'points_cost_daily',
        ];
        $featureDefaults = [
            'bazi' => 10,
            'hehun' => 80,
            'tarot' => 20,
            'liuyao' => 20,
            'daily' => 0,
        ];

        $configKey = $configKeyMap[$feature] ?? null;
        if ($configKey !== null) {
            $configuredValue = self::get($configKey, null);
            if (is_numeric($configuredValue)) {
                return max(0, (int) $configuredValue);
            }
        }

        return $featureDefaults[$feature] ?? 0;
    }
}