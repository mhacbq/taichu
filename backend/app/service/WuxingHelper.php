<?php
declare(strict_types=1);

namespace app\service;

/**
 * 五行工具类
 * 集中管理天干地支五行映射、生克关系，供各模块复用，避免重复定义。
 */
class WuxingHelper
{
    // 天干 → 五行
    public const GAN_WUXING = [
        '甲' => '木', '乙' => '木',
        '丙' => '火', '丁' => '火',
        '戊' => '土', '己' => '土',
        '庚' => '金', '辛' => '金',
        '壬' => '水', '癸' => '水',
    ];

    // 地支 → 五行
    public const ZHI_WUXING = [
        '子' => '水', '丑' => '土', '寅' => '木', '卯' => '木',
        '辰' => '土', '巳' => '火', '午' => '火', '未' => '土',
        '申' => '金', '酉' => '金', '戌' => '土', '亥' => '水',
    ];

    // 五行生克：生我者、我生者、克我者、我克者
    public const WUXING_SHENG = [
        '木' => '水', '火' => '木', '土' => '火', '金' => '土', '水' => '金',
    ];
    public const WUXING_WO_SHENG = [
        '木' => '火', '火' => '土', '土' => '金', '金' => '水', '水' => '木',
    ];
    public const WUXING_KE = [
        '木' => '土', '火' => '金', '土' => '水', '金' => '木', '水' => '火',
    ];
    public const WUXING_BEI_KE = [
        '木' => '金', '火' => '水', '土' => '木', '金' => '火', '水' => '土',
    ];

    // 五行 → 幸运色
    public const LUCKY_COLORS = [
        '金' => ['白色', '金色', '银色'],
        '木' => ['绿色', '青色', '翠色'],
        '水' => ['黑色', '蓝色', '灰色'],
        '火' => ['红色', '紫色', '橙色'],
        '土' => ['黄色', '棕色', '咖啡色'],
    ];

    // 五行 → 幸运方位
    public const LUCKY_DIRECTIONS = [
        '金' => ['西方', '西北方'],
        '木' => ['东方', '东南方'],
        '水' => ['北方'],
        '火' => ['南方'],
        '土' => ['中央', '东北方', '西南方'],
    ];

    // 五行 → 幸运数字池
    public const LUCKY_NUMBERS = [
        '木' => [3, 8, 13, 18, 23, 28],
        '火' => [2, 7, 12, 17, 22, 27],
        '土' => [5, 10, 15, 20, 25, 30],
        '金' => [4, 9, 14, 19, 24, 29],
        '水' => [1, 6, 11, 16, 21, 26],
    ];

    /**
     * 获取天干对应五行
     */
    public static function ganToWuxing(string $gan): string
    {
        return self::GAN_WUXING[$gan] ?? '';
    }

    /**
     * 获取地支对应五行
     */
    public static function zhiToWuxing(string $zhi): string
    {
        return self::ZHI_WUXING[$zhi] ?? '';
    }

    /**
     * 判断日主强弱（月令生扶为强）
     */
    public static function isStrong(string $dayMasterWuxing, string $monthZhi): bool
    {
        $monthWx = self::zhiToWuxing($monthZhi);
        return $monthWx === $dayMasterWuxing
            || $monthWx === (self::WUXING_SHENG[$dayMasterWuxing] ?? '');
    }

    /**
     * 根据日主五行推算喜用五行（身弱取印比，身旺取食财官）
     */
    public static function getFavoriteWuxing(string $dayMasterWuxing, bool $isStrong): string
    {
        if (!$isStrong) {
            // 身弱：取生我者（印）
            return self::WUXING_SHENG[$dayMasterWuxing] ?? $dayMasterWuxing;
        }

        // 身旺：取我克者（财）为首选
        $options = [
            '木' => ['金', '火', '土'],
            '火' => ['水', '土', '金'],
            '土' => ['木', '金', '水'],
            '金' => ['火', '水', '木'],
            '水' => ['土', '木', '火'],
        ];

        return ($options[$dayMasterWuxing] ?? [])[0] ?? $dayMasterWuxing;
    }

    /**
     * 获取幸运色（按日期+用户ID稳定随机取2个）
     */
    public static function getLuckyColors(string $wuxing, int $userId = 0): array
    {
        $pool = self::LUCKY_COLORS[$wuxing] ?? ['黄色', '棕色'];
        return self::stablePick($pool, $userId, 'colors', 2);
    }

    /**
     * 获取幸运方位
     */
    public static function getLuckyDirections(string $wuxing, int $userId = 0): array
    {
        $pool = self::LUCKY_DIRECTIONS[$wuxing] ?? ['中央'];
        if (count($pool) === 1) {
            return $pool;
        }
        return self::stablePick($pool, $userId, 'directions', 2);
    }

    /**
     * 获取幸运数字
     */
    public static function getLuckyNumbers(string $wuxing, int $userId = 0): array
    {
        $pool = self::LUCKY_NUMBERS[$wuxing] ?? self::LUCKY_NUMBERS['土'];
        return self::stablePick($pool, $userId, 'numbers', 3);
    }

    /**
     * 基于用户ID+日期+盐值稳定取N个元素
     */
    private static function stablePick(array $pool, int $userId, string $salt, int $count): array
    {
        $seed = (int) sprintf('%u', crc32($userId . date('Ymd') . $salt));
        $offset = $seed % count($pool);
        $result = [];
        for ($i = 0; $i < $count; $i++) {
            $result[] = $pool[($offset + $i) % count($pool)];
        }
        return $result;
    }
}
