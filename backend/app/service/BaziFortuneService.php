<?php
declare(strict_types=1);

namespace app\service;

/**
 * 八字运势分析服务
 * 
 * 结合格局分析运势，提供更准确的运势预测
 * 包括大运、流年、流月的运势分析
 */
class BaziFortuneService
{
    /**
     * 分析整体运势
     */
    public function analyzeOverallFortune(array $bazi): array
    {
        $pattern = $bazi['pattern'] ?? [];
        $patternLevel = $pattern['pattern_level'] ?? [];
        $eightPatterns = $pattern['eight_patterns'] ?? [];
        $specialPatterns = $pattern['special_patterns'] ?? [];
        $shenSha = $pattern['shen_sha'] ?? [];
        
        // 基础运势评分
        $baseScore = $patternLevel['score'] ?? 0;
        
        // 神煞加分
        $goodShenShaScore = 0;
        $badShenShaScore = 0;
        foreach ($shenSha as $sha) {
            if (($sha['quality'] ?? '') === '吉') {
                $goodShenShaScore += ($sha['level'] ?? 0);
            } elseif (($sha['quality'] ?? '') === '凶') {
                $badShenShaScore += ($sha['level'] ?? 0);
            }
        }
        
        // 综合运势评分
        $totalScore = $baseScore + $goodShenShaScore - $badShenShaScore;
        
        // 运势等级
        if ($totalScore >= 150) {
            $level = '极佳';
            $description = '运势极佳，富贵可期。有贵人相助，事业大成，名利双收。';
        } elseif ($totalScore >= 120) {
            $level = '很好';
            $description = '运势很好，事业有成。有贵人相助，能够成就一番事业。';
        } elseif ($totalScore >= 90) {
            $level = '较好';
            $description = '运势较好，事业顺遂。努力奋斗，能够有所成就。';
        } elseif ($totalScore >= 60) {
            $level = '一般';
            $description = '运势一般，事业平稳。需要努力奋斗，才能有所成就。';
        } else {
            $level = '较差';
            $description = '运势较差，需要付出更多努力。建议修身养性，循序渐进。';
        }
        
        return [
            'score' => $totalScore,
            'level' => $level,
            'description' => $description,
            'good_shensha_count' => count(array_filter($shenSha, fn($item) => ($item['quality'] ?? '') === '吉')),
            'bad_shensha_count' => count(array_filter($shenSha, fn($item) => ($item['quality'] ?? '') === '凶')),
            'pattern_level' => $patternLevel['level'] ?? '',
        ];
    }
    
    /**
     * 分析大运运势
     */
    public function analyzeDayunFortune(array $bazi, array $dayuns): array
    {
        $patternService = new BaziPatternService();
        $fortunes = [];
        
        foreach ($dayuns as $index => $dayun) {
            $dayunPattern = $patternService->analyzeDayunPattern($bazi, $dayun, $index);
            
            // 计算大运运势评分
            $score = $dayunPattern['dayun_score'] ?? 0;
            $isFavorable = $dayunPattern['is_favorable'] ?? false;
            
            // 运势等级
            if ($score >= 80) {
                $level = '极佳';
            } elseif ($score >= 60) {
                $level = '很好';
            } elseif ($score >= 40) {
                $level = '较好';
            } elseif ($score >= 20) {
                $level = '一般';
            } else {
                $level = '较差';
            }
            
            $fortunes[] = [
                'dayun' => $dayun,
                'dayun_index' => $index,
                'score' => $score,
                'level' => $level,
                'is_favorable' => $isFavorable,
                'description' => $dayunPattern['dayun_description'] ?? '',
                'advice' => $dayunPattern['advice'] ?? '',
                'pattern' => $dayunPattern['pattern'] ?? [],
            ];
        }
        
        return $fortunes;
    }
    
    /**
     * 分析流年运势
     */
    public function analyzeLiunianFortune(array $bazi, array $liunians): array
    {
        $patternService = new BaziPatternService();
        $fortunes = [];
        
        foreach ($liunians as $index => $liunian) {
            $liunianYear = $liunian['year'] ?? (date('Y') + $index);
            $liunianPattern = $patternService->analyzeLiunianPattern($bazi, $liunian, $liunianYear);
            
            // 计算流年运势评分
            $score = $liunianPattern['liunian_score'] ?? 0;
            $isFavorable = $liunianPattern['is_favorable'] ?? false;
            
            // 运势等级
            if ($score >= 80) {
                $level = '极佳';
            } elseif ($score >= 60) {
                $level = '很好';
            } elseif ($score >= 40) {
                $level = '较好';
            } elseif ($score >= 20) {
                $level = '一般';
            } else {
                $level = '较差';
            }
            
            $fortunes[] = [
                'liunian' => $liunian,
                'liunian_year' => $liunianYear,
                'score' => $score,
                'level' => $level,
                'is_favorable' => $isFavorable,
                'description' => $liunianPattern['liunian_description'] ?? '',
                'advice' => $liunianPattern['advice'] ?? '',
                'pattern' => $liunianPattern['pattern'] ?? [],
            ];
        }
        
        return $fortunes;
    }
    
    /**
     * 分析最佳运程
     */
    public function analyzeBestFortune(array $dayunFortunes, array $liunianFortunes): array
    {
        $bestDayun = [];
        $bestLiunian = [];
        
        // 找出最好的大运
        foreach ($dayunFortunes as $dayun) {
            if (empty($bestDayun) || $dayun['score'] > ($bestDayun['score'] ?? 0)) {
                $bestDayun = $dayun;
            }
        }
        
        // 找出最好的流年
        foreach ($liunianFortunes as $liunian) {
            if (empty($bestLiunian) || $liunian['score'] > ($bestLiunian['score'] ?? 0)) {
                $bestLiunian = $liunian;
            }
        }
        
        return [
            'best_dayun' => $bestDayun,
            'best_liunian' => $bestLiunian,
            'advice' => $this->generateBestFortuneAdvice($bestDayun, $bestLiunian),
        ];
    }
    
    /**
     * 生成最佳运程建议
     */
    protected function generateBestFortuneAdvice(array $bestDayun, array $bestLiunian): string
    {
        $advice = '';
        
        if (!empty($bestDayun)) {
            $dayunGanZhi = ($bestDayun['dayun']['gan'] ?? '') . ($bestDayun['dayun']['zhi'] ?? '');
            $advice .= "最佳大运是【{$dayunGanZhi}】，运势等级为【{$bestDayun['level']}】。";
            $advice .= ($bestDayun['advice'] ?? '') . ' ';
        }
        
        if (!empty($bestLiunian)) {
            $liunianYear = $bestLiunian['liunian_year'] ?? '';
            $liunianGanZhi = ($bestLiunian['liunian']['gan'] ?? '') . ($bestLiunian['liunian']['zhi'] ?? '');
            $advice .= "最佳流年是【{$liunianYear}】年（{$liunianGanZhi}），运势等级为【{$bestLiunian['level']}】。";
            $advice .= ($bestLiunian['advice'] ?? '');
        }
        
        return $advice;
    }
    
    /**
     * 分析运势变化趋势
     */
    public function analyzeFortuneTrend(array $fortunes): array
    {
        if (empty($fortunes)) {
            return [
                'trend' => '平稳',
                'description' => '数据不足，无法分析趋势。',
            ];
        }
        
        $scores = array_column($fortunes, 'score');
        $firstScore = $scores[0] ?? 0;
        $lastScore = $scores[count($scores) - 1] ?? 0;
        $avgScore = array_sum($scores) / count($scores);
        
        $trend = '';
        $description = '';
        
        if ($lastScore > $firstScore * 1.2) {
            $trend = '上升';
            $description = '运势呈上升趋势，越来越好。';
        } elseif ($lastScore < $firstScore * 0.8) {
            $trend = '下降';
            $description = '运势呈下降趋势，需要谨慎。';
        } else {
            $trend = '平稳';
            $description = '运势比较平稳，没有大的波动。';
        }
        
        // 计算波动性
        $variance = 0;
        foreach ($scores as $score) {
            $variance += pow($score - $avgScore, 2);
        }
        $stdDev = sqrt($variance / count($scores));
        
        if ($stdDev > 20) {
            $description .= ' 运势波动较大，需要注意调整。';
        } else {
            $description .= ' 运势波动较小，比较稳定。';
        }
        
        return [
            'trend' => $trend,
            'description' => $description,
            'first_score' => $firstScore,
            'last_score' => $lastScore,
            'avg_score' => $avgScore,
            'std_dev' => $stdDev,
        ];
    }
    
    /**
     * 生成运程序列建议
     */
    public function generateFortuneSequenceAdvice(array $dayunFortunes, array $liunianFortunes): array
    {
        $advice = [];
        
        // 大运建议
        foreach ($dayunFortunes as $dayun) {
            $dayunGanZhi = ($dayun['dayun']['gan'] ?? '') . ($dayun['dayun']['zhi'] ?? '');
            $level = $dayun['level'] ?? '';
            
            if ($level === '极佳' || $level === '很好') {
                $advice[] = [
                    'type' => 'dayun',
                    'gan_zhi' => $dayunGanZhi,
                    'advice' => "【{$dayunGanZhi}】大运运势很好，可以积极进取，把握机会。但要保持谦虚，避免骄傲自满。",
                    'is_important' => true,
                ];
            } elseif ($level === '较差') {
                $advice[] = [
                    'type' => 'dayun',
                    'gan_zhi' => $dayunGanZhi,
                    'advice' => "【{$dayunGanZhi}】大运运势较差，需要谨慎行事，稳健发展。建议修身养性，积累实力。",
                    'is_important' => true,
                ];
            }
        }
        
        // 流年建议
        foreach ($liunianFortunes as $liunian) {
            $liunianYear = $liunian['liunian_year'] ?? '';
            $liunianGanZhi = ($liunian['liunian']['gan'] ?? '') . ($liunian['liunian']['zhi'] ?? '');
            $level = $liunian['level'] ?? '';
            
            if ($level === '极佳' || $level === '很好') {
                $advice[] = [
                    'type' => 'liunian',
                    'year' => $liunianYear,
                    'gan_zhi' => $liunianGanZhi,
                    'advice' => "【{$liunianYear}】年（{$liunianGanZhi}）运势很好，可以积极进取，把握机会。",
                    'is_important' => false,
                ];
            } elseif ($level === '较差') {
                $advice[] = [
                    'type' => 'liunian',
                    'year' => $liunianYear,
                    'gan_zhi' => $liunianGanZhi,
                    'advice' => "【{$liunianYear}】年（{$liunianGanZhi}）运势较差，需要谨慎行事，稳健发展。",
                    'is_important' => false,
                ];
            }
        }
        
        return $advice;
    }
}
