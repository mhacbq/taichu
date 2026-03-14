<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

class DailyFortune extends Model
{
    protected $table = 'tc_daily_fortune';
    protected $pk = 'id';
    
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    
    protected $schema = [
        'id' => 'int',
        'date' => 'string',
        'lunar_date' => 'string',
        'overall_score' => 'int',
        'summary' => 'string',
        'career_score' => 'int',
        'career_desc' => 'string',
        'wealth_score' => 'int',
        'wealth_desc' => 'string',
        'love_score' => 'int',
        'love_desc' => 'string',
        'health_score' => 'int',
        'health_desc' => 'string',
        'yi' => 'string',
        'ji' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    /**
     * 获取指定日期的运势
     */
    public static function getByDate(string $date): ?self
    {
        return self::where('date', $date)->find();
    }
    
    /**
     * 获取今日运势（如果没有则生成）
     */
    public static function getToday(): self
    {
        $today = date('Y-m-d');
        $fortune = self::where('date', $today)->find();
        
        if (!$fortune) {
            $fortune = self::generateFortune($today);
        }
        
        return $fortune;
    }
    
    /**
     * 生成运势
     */
    protected static function generateFortune(string $date): self
    {
        // 简化的运势生成逻辑
        $yiList = ['出行', '签约', '搬家', '装修', '结婚', '开业', '祭祀', '祈福'];
        $jiList = ['动土', '安葬', '行丧', '破土', '开市', '入宅'];
        
        $overallScore = mt_rand(60, 95);
        
        return self::create([
            'date' => $date,
            'lunar_date' => self::solarToLunar($date),
            'overall_score' => $overallScore,
            'summary' => self::getSummaryByScore($overallScore),
            'career_score' => mt_rand(50, 100),
            'career_desc' => '事业运势' . (mt_rand(0, 1) ? '较好，工作顺利' : '平稳，保持现状'),
            'wealth_score' => mt_rand(50, 100),
            'wealth_desc' => '财运' . (mt_rand(0, 1) ? '不错，有意外收获' : '一般，谨慎理财'),
            'love_score' => mt_rand(50, 100),
            'love_desc' => '感情运势' . (mt_rand(0, 1) ? '甜蜜，桃花旺' : '平淡，需要主动'),
            'health_score' => mt_rand(60, 100),
            'health_desc' => '健康状况' . (mt_rand(0, 1) ? '良好' : '一般，注意休息'),
            'yi' => implode(',', array_slice($yiList, 0, mt_rand(2, 4))),
            'ji' => implode(',', array_slice($jiList, 0, mt_rand(1, 3))),
        ]);
    }
    
    /**
     * 公历转农历（简化版）
     */
    protected static function solarToLunar(string $date): string
    {
        // 实际项目中应使用农历转换库
        $month = mt_rand(1, 12);
        $day = mt_rand(1, 30);
        $ganzhi = ['甲子', '乙丑', '丙寅', '丁卯', '戊辰', '己巳'][mt_rand(0, 5)];
        return "{$ganzhi}年 {$month}月{$day}日";
    }
    
    /**
     * 根据分数获取运势摘要
     */
    protected static function getSummaryByScore(int $score): string
    {
        if ($score >= 90) return '今日运势极佳，万事顺遂，把握机会！';
        if ($score >= 80) return '今日运势良好，积极进取，收获颇丰。';
        if ($score >= 70) return '今日运势平稳，稳扎稳打，循序渐进。';
        if ($score >= 60) return '今日运势一般，保持谨慎，静待时机。';
        return '今日运势欠佳，低调行事，以守为攻。';
    }
}
