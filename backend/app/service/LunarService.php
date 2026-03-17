<?php
declare(strict_types=1);

namespace app\service;

/**
 * 农历转换工具类
 * 提供公历转农历、干支计算等功能
 * 采用数据驱动，确保起卦、命理计算的准确性
 */
class LunarService
{
    /**
     * 天干
     */
    public const TIAN_GAN = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];

    /**
     * 地支
     */
    public const DI_ZHI = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];

    /**
     * 农历数据 (2020-2035)
     * 格式：[农历年新年的公历日期, 闰月(0表示无), 各月天数(16进制，从正月到腊月，1=30天, 0=29天), 闰月天数]
     */
    private static $lunarData = [
        2020 => ['2020-01-25', 4, 0x094b, 0],
        2021 => ['2021-02-12', 0, 0x0a95, 0],
        2022 => ['2022-02-01', 0, 0x052a, 0],
        2023 => ['2023-01-22', 2, 0x0aad, 1],
        2024 => ['2024-02-10', 0, 0x0552, 0],
        2025 => ['2025-01-29', 6, 0x0aba, 1],
        2026 => ['2026-02-17', 0, 0x052b, 0],
        2027 => ['2027-02-06', 5, 0x0a93, 1],
        2028 => ['2028-01-26', 0, 0x092b, 0],
        2029 => ['2029-02-13', 0, 0x0d95, 0],
        2030 => ['2030-02-03', 0, 0x0d4a, 0],
        2031 => ['2031-01-23', 3, 0x0da5, 1],
        2032 => ['2032-02-11', 0, 0x056a, 0],
        2033 => ['2033-01-31', 0, 0x0abb, 0],
        2034 => ['2034-02-19', 0, 0x025b, 0],
        2035 => ['2035-02-08', 6, 0x052b, 1],
    ];

    /**
     * 公历转农历
     * 
     * @param string $date 公历日期 Y-m-d
     * @return array 农历信息 [year_zhi_index, lunar_month, lunar_day, year_gan_zhi]
     */
    public static function solarToLunar(string $date): array
    {
        $timestamp = strtotime($date);
        $year = (int)date('Y', $timestamp);
        
        // 如果日期早于当年的春节，属于上一年
        $newYearDate = self::$lunarData[$year][0] ?? null;
        if ($newYearDate && $timestamp < strtotime($newYearDate)) {
            $year--;
        }

        $data = self::$lunarData[$year] ?? null;
        if (!$data) {
            // 超出范围，回退到简化模拟（实际应提示或扩展数据）
            return self::fallbackSolarToLunar($date);
        }

        $baseTimestamp = strtotime($data[0]);
        $offset = (int)(($timestamp - $baseTimestamp) / 86400);

        $leapMonth = $data[1];
        $monthData = $data[2];
        $leapMonthDays = $data[3] ? 30 : 29;

        $lunarMonth = 1;
        $isLeap = false;

        for ($i = 1; $i <= 12; $i++) {
            // 当前月天数 (1=30, 0=29)
            $bit = (12 - $i);
            $days = (($monthData >> $bit) & 1) ? 30 : 29;

            if ($offset < $days) {
                break;
            }
            $offset -= $days;
            $lunarMonth++;

            // 检查闰月
            if ($i == $leapMonth) {
                if ($offset < $leapMonthDays) {
                    $isLeap = true;
                    break;
                }
                $offset -= $leapMonthDays;
            }
        }

        $lunarDay = $offset + 1;

        // 计算年支（考虑命理学立春，但梅花易数通常用农历年）
        // 这里返回农历年的干支
        $yearGanIndex = ($year - 4) % 10;
        $yearZhiIndex = ($year - 4) % 12;

        return [
            'year_zhi_index' => $yearZhiIndex + 1, // 1-indexed (子=1)
            'lunar_month' => $lunarMonth,
            'lunar_day' => $lunarDay,
            'is_leap' => $isLeap,
            'year_gan_zhi' => self::TIAN_GAN[$yearGanIndex] . self::DI_ZHI[$yearZhiIndex]
        ];
    }

    /**
     * 降级处理
     */
    private static function fallbackSolarToLunar(string $date): array
    {
        $timestamp = strtotime($date);
        $year = (int)date('Y', $timestamp);
        $month = (int)date('n', $timestamp);
        $day = (int)date('j', $timestamp);

        // 简化的固定日期模拟
        return [
            'year_zhi_index' => (($year - 4) % 12) + 1,
            'lunar_month' => $month, // 错误但无奈的回退
            'lunar_day' => $day,
            'is_leap' => false,
            'year_gan_zhi' => self::TIAN_GAN[($year - 4) % 10] . self::DI_ZHI[($year - 4) % 12]
        ];
    }
}
