<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\DailyFortune;

class Daily extends BaseController
{
    protected $middleware = [\app\middleware\Auth::class];
    
    /**
     * 获取今日运势
     */
    public function fortune()
    {
        $fortune = DailyFortune::getToday();
        
        $yi = explode(',', $fortune->yi);
        $ji = explode(',', $fortune->ji);
        
        return $this->success([
            'date' => $fortune->date,
            'lunarDate' => $fortune->lunar_date,
            'overallScore' => $fortune->overall_score,
            'summary' => $fortune->summary,
            'aspects' => [
                ['name' => '事业运', 'icon' => '💼', 'score' => $fortune->career_score, 'description' => $fortune->career_desc],
                ['name' => '财运', 'icon' => '💰', 'score' => $fortune->wealth_score, 'description' => $fortune->wealth_desc],
                ['name' => '感情运', 'icon' => '💕', 'score' => $fortune->love_score, 'description' => $fortune->love_desc],
                ['name' => '健康运', 'icon' => '🏃', 'score' => $fortune->health_score, 'description' => $fortune->health_desc],
            ],
            'yi' => $yi,
            'ji' => $ji,
            'details' => [
                'career' => $fortune->career_desc,
                'wealth' => $fortune->wealth_desc,
                'love' => $fortune->love_desc,
                'health' => $fortune->health_desc,
            ],
        ]);
    }
    
    /**
     * 获取今日宜忌
     */
    public function luck()
    {
        $fortune = DailyFortune::getToday();
        
        return $this->success([
            'date' => $fortune->date,
            'lunarDate' => $fortune->lunar_date,
            'yi' => explode(',', $fortune->yi),
            'ji' => explode(',', $fortune->ji),
            'luckyNumbers' => $this->generateLuckyNumbers(),
            'luckyColors' => $this->generateLuckyColors(),
            'luckyDirections' => $this->generateLuckyDirections(),
        ]);
    }
    
    /**
     * 生成幸运数字
     */
    protected function generateLuckyNumbers(): array
    {
        $numbers = [];
        while (count($numbers) < 3) {
            $num = mt_rand(1, 99);
            if (!in_array($num, $numbers)) {
                $numbers[] = $num;
            }
        }
        return $numbers;
    }
    
    /**
     * 生成幸运颜色
     */
    protected function generateLuckyColors(): array
    {
        $colors = ['红色', '黄色', '蓝色', '绿色', '紫色', '橙色', '白色', '黑色'];
        $keys = array_rand($colors, 2);
        return [$colors[$keys[0]], $colors[$keys[1]]];
    }
    
    /**
     * 生成幸运方位
     */
    protected function generateLuckyDirections(): array
    {
        $directions = ['东', '南', '西', '北', '东南', '东北', '西南', '西北'];
        $keys = array_rand($directions, 2);
        return [$directions[$keys[0]], $directions[$keys[1]]];
    }
}
