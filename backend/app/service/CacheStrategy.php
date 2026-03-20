<?php

namespace app\service;

use think\facade\Cache;

/**
 * Redis缓存策略服务
 */
class CacheStrategy
{
    protected $prefix = 'taichu:';
    protected $ttl = [
        'user_info' => 3600,           // 1小时
        'vip_status' => 1800,          // 30分钟
        'points_balance' => 600,       // 10分钟
        'bazi_result' => 86400,        // 24小时
        'daily_fortune' => 7200,       // 2小时
        'config' => 86400,             // 24小时
        'statistics' => 300,           // 5分钟
        'trend_data' => 600,           // 10分钟
    ];

    /**
     * 获取缓存
     */
    public function get($key, $default = null)
    {
        $cacheKey = $this->prefix . $key;

        if (!Cache::store('redis')->has($cacheKey)) {
            return $default;
        }

        return Cache::store('redis')->get($cacheKey);
    }

    /**
     * 设置缓存
     */
    public function set($key, $value, $ttl = null)
    {
        $cacheKey = $this->prefix . $key;

        if ($ttl === null) {
            $ttl = $this->getDefaultTtl($key);
        }

        return Cache::store('redis')->set($cacheKey, $value, $ttl);
    }

    /**
     * 删除缓存
     */
    public function delete($key)
    {
        $cacheKey = $this->prefix . $key;
        return Cache::store('redis')->delete($cacheKey);
    }

    /**
     * 批量删除缓存（支持通配符）
     */
    public function deletePattern($pattern)
    {
        $redis = Cache::store('redis')->handler();
        $keys = $redis->keys($this->prefix . $pattern);

        if (!empty($keys)) {
            return $redis->del($keys);
        }

        return 0;
    }

    /**
     * 记住缓存（如果不存在则执行回调并缓存）
     */
    public function remember($key, $callback, $ttl = null)
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }

    /**
     * 获取默认过期时间
     */
    protected function getDefaultTtl($key)
    {
        foreach ($this->ttl as $pattern => $time) {
            if (strpos($key, $pattern) !== false) {
                return $time;
            }
        }

        return 3600; // 默认1小时
    }

    /**
     * 预热缓存
     */
    public function warmup(array $keys)
    {
        foreach ($keys as $key => $callback) {
            if (!$this->get($key)) {
                $this->remember($key, $callback);
            }
        }
    }

    /**
     * 清除用户相关缓存
     */
    public function clearUserCache($userId)
    {
        return $this->deletePattern("user_{$userId}:*");
    }

    /**
     * 清除统计数据缓存
     */
    public function clearStatsCache()
    {
        return $this->deletePattern('statistics:*');
    }

    /**
     * 缓存命中率统计（Redis监控）
     */
    public function getCacheStats()
    {
        $redis = Cache::store('redis')->handler();
        $info = $redis->info('stats');

        return [
            'hits' => $info['keyspace_hits'] ?? 0,
            'misses' => $info['keyspace_misses'] ?? 0,
            'hit_rate' => isset($info['keyspace_hits']) && isset($info['keyspace_misses'])
                ? round($info['keyspace_hits'] / ($info['keyspace_hits'] + $info['keyspace_misses']), 4)
                : 0,
        ];
    }
}
