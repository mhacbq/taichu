<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Cache;

/**
 * 缓存服务
 * 
 * 提供统一的缓存管理，支持标签、过期时间等
 */
class CacheService
{
    /**
     * 缓存标签
     */
    const TAG_BAZI = 'bazi';           // 八字排盘
    const TAG_AI = 'ai_analysis';      // AI分析
    const TAG_DAILY = 'daily';         // 每日运势
    const TAG_USER = 'user';           // 用户相关
    const TAG_HEHUN = 'hehun';         // 八字合婚
    
    /**
     * 默认过期时间（秒）
     */
    const TTL_SHORT = 300;      // 5分钟
    const TTL_NORMAL = 3600;    // 1小时
    const TTL_LONG = 86400;     // 1天
    const TTL_WEEK = 604800;    // 1周
    
    /**
     * 获取缓存
     * 
     * @param string $key 缓存键
     * @param mixed $default 默认值
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        return Cache::get($key, $default);
    }
    
    /**
     * 设置缓存
     * 
     * @param string $key 缓存键
     * @param mixed $value 缓存值
     * @param int $ttl 过期时间（秒）
     * @param string|null $tag 标签
     * @return bool
     */
    public static function set(string $key, $value, int $ttl = self::TTL_NORMAL, ?string $tag = null): bool
    {
        $options = [];
        if ($tag) {
            $options['tag'] = $tag;
        }
        
        return Cache::set($key, $value, $ttl, $options);
    }
    
    /**
     * 删除缓存
     * 
     * @param string $key 缓存键
     * @return bool
     */
    public static function delete(string $key): bool
    {
        return Cache::delete($key);
    }
    
    /**
     * 清空标签缓存
     * 
     * @param string $tag 标签名
     * @return bool
     */
    public static function clearTag(string $tag): bool
    {
        return Cache::tag($tag)->clear();
    }
    
    /**
     * 获取或设置缓存
     * 
     * @param string $key 缓存键
     * @param callable $callback 回调函数
     * @param int $ttl 过期时间
     * @param string|null $tag 标签
     * @return mixed
     */
    public static function remember(string $key, callable $callback, int $ttl = self::TTL_NORMAL, ?string $tag = null)
    {
        $value = self::get($key);
        
        if ($value !== null) {
            return $value;
        }
        
        $value = $callback();
        
        if ($value !== null) {
            self::set($key, $value, $ttl, $tag);
        }
        
        return $value;
    }
    
    /**
     * 生成八字排盘缓存键
     * 
     * @param string $birthDate 出生日期
     * @param string $gender 性别
     * @return string
     */
    public static function baziKey(string $birthDate, string $gender): string
    {
        $hash = md5($birthDate . $gender);
        return 'bazi:calc:' . $hash;
    }
    
    /**
     * 生成AI分析缓存键
     * 
     * @param array $bazi 八字数据
     * @param string $type 分析类型
     * @return string
     */
    public static function aiAnalysisKey(array $bazi, string $type = 'default'): string
    {
        $baziString = json_encode($bazi);
        $hash = md5($baziString . $type);
        return 'ai:analysis:' . $type . ':' . $hash;
    }
    
    /**
     * 生成每日运势缓存键
     * 
     * @param int $userId 用户ID
     * @param string $date 日期
     * @return string
     */
    public static function dailyFortuneKey(int $userId, string $date): string
    {
        return 'daily:fortune:' . $userId . ':' . $date;
    }
    
    /**
     * 检查缓存是否存在
     * 
     * @param string $key 缓存键
     * @return bool
     */
    public static function has(string $key): bool
    {
        return Cache::has($key);
    }
    
    /**
     * 缓存递增
     * 
     * @param string $key 缓存键
     * @param int $step 步长
     * @return int|false
     */
    public static function inc(string $key, int $step = 1)
    {
        return Cache::inc($key, $step);
    }
    
    /**
     * 缓存递减
     * 
     * @param string $key 缓存键
     * @param int $step 步长
     * @return int|false
     */
    public static function dec(string $key, int $step = 1)
    {
        return Cache::dec($key, $step);
    }
    
    /**
     * 生成流年运势缓存键
     * 
     * @param array $bazi 八字数据
     * @param int $year 年份
     * @return string
     */
    public static function yearlyFortuneKey(array $bazi, int $year): string
    {
        $baziString = json_encode($bazi);
        $hash = md5($baziString . $year);
        return 'yearly:fortune:' . $year . ':' . $hash;
    }
    
    /**
     * 生成大运分析缓存键
     * 
     * @param array $dayun 大运数据
     * @param string $dayMaster 日主
     * @return string
     */
    public static function dayunKey(array $dayun, string $dayMaster): string
    {
        $dayunString = $dayun['gan'] . $dayun['zhi'] . $dayun['start_age'];
        $hash = md5($dayunString . $dayMaster);
        return 'dayun:analysis:' . $hash;
    }
    
    /**
     * 生成八字合婚缓存键
     * 
     * @param string $maleBirthDate 男方出生日期
     * @param string $femaleBirthDate 女方出生日期
     * @return string
     */
    public static function hehunKey(string $maleBirthDate, string $femaleBirthDate): string
    {
        $hash = md5($maleBirthDate . $femaleBirthDate);
        return 'hehun:calc:' . $hash;
    }
}