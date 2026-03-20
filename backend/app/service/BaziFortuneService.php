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
        $strength = $bazi['strength'] ?? [];
        $wuxingStats = $bazi['wuxing_stats'] ?? [];
        $yongshen = $bazi['yongshen'] ?? [];
        
        // 基础运势评分（格局层次）
        $baseScore = $patternLevel['score'] ?? 0;
        
        // 日主强弱评分优化
        $strengthScore = $this->calculateStrengthScore($strength);
        
        // 五行平衡评分
        $balanceScore = $this->calculateBalanceScore($wuxingStats);
        
        // 喜用神评分
        $yongshenScore = $this->calculateYongshenScore($wuxingStats, $yongshen);
        
        // 神煞评分
        $goodShenShaScore = 0;
        $badShenShaScore = 0;
        $goodShenShaCount = 0;
        $badShenShaCount = 0;
        
        foreach ($shenSha as $sha) {
            if (($sha['quality'] ?? '') === '吉') {
                $goodShenShaScore += ($sha['level'] ?? 0);
                $goodShenShaCount++;
            } elseif (($sha['quality'] ?? '') === '凶') {
                $badShenShaScore += ($sha['level'] ?? 0);
                $badShenShaCount++;
            }
        }
        
        // 特殊格局加分
        $specialPatternScore = 0;
        foreach ($specialPatterns as $special) {
            $specialPatternScore += ($special['level'] ?? 0);
        }
        
        // 八格评分
        $eightPatternScore = 0;
        foreach ($eightPatterns as $eight) {
            $eightPatternScore += ($eight['level'] ?? 0);
        }
        
        // 综合运势评分（权重优化）
        $totalScore = 
            ($baseScore * 0.25) +           // 格局层次：25%
            ($strengthScore * 0.20) +       // 日主强弱：20%
            ($balanceScore * 0.15) +        // 五行平衡：15%
            ($yongshenScore * 0.15) +       // 喜用神：15%
            ($specialPatternScore * 0.10) + // 特殊格局：10%
            ($eightPatternScore * 0.08) +   // 八格：8%
            ($goodShenShaScore * 0.07) -    // 吉神：7%
            ($badShenShaScore * 0.05);      // 凶煞：-5%
        
        // 确保分数在合理范围
        $totalScore = max(0, min(200, round($totalScore)));
        
        // 运势等级（优化标准）
        if ($totalScore >= 170) {
            $level = '极佳';
            $description = '运势极佳，富贵可期。格局上乘，有贵人相助，事业大成，名利双收。一生顺遂，福泽深厚。';
        } elseif ($totalScore >= 145) {
            $level = '很好';
            $description = '运势很好，事业有成。格局优良，有贵人相助，能够成就一番事业。中晚年运势更佳。';
        } elseif ($totalScore >= 120) {
            $level = '较好';
            $description = '运势较好，事业顺遂。格局良好，努力奋斗，能够有所成就。贵人运不错。';
        } elseif ($totalScore >= 95) {
            $level = '一般偏上';
            $description = '运势一般偏上，事业平稳。格局中等，需要努力奋斗，才能有所成就。有贵人扶持。';
        } elseif ($totalScore >= 70) {
            $level = '一般';
            $description = '运势一般，事业平稳。格局一般，需要付出更多努力。建议修身养性，循序渐进。';
        } elseif ($totalScore >= 50) {
            $level = '一般偏下';
            $description = '运势一般偏下，事业有阻。格局较弱，需要付出更多努力。建议多积德行善，改善运势。';
        } else {
            $level = '较差';
            $description = '运势较差，需要付出更多努力。格局较弱，建议修身养性，循序渐进，多积德行善。';
        }
        
        return [
            'score' => $totalScore,
            'level' => $level,
            'description' => $description,
            'good_shensha_count' => $goodShenShaCount,
            'bad_shensha_count' => $badShenShaCount,
            'pattern_level' => $patternLevel['level'] ?? '',
            'strength_status' => $strength['status'] ?? '',
        ];
    }
    
    /**
     * 计算日主强弱评分
     */
    protected function calculateStrengthScore(array $strength): int
    {
        $status = $strength['status'] ?? '';
        $score = $strength['score'] ?? 50;
        
        // 根据日主强弱状态调整评分
        switch ($status) {
            case '身旺':
                return min(90, $score + 10);
            case '中和偏旺':
                return min(85, $score + 5);
            case '中和':
                return 80;
            case '中和偏弱':
                return max(70, $score - 5);
            case '身弱':
                return max(60, $score - 10);
            case '极弱':
                return max(50, $score - 15);
            default:
                return 70;
        }
    }
    
    /**
     * 计算五行平衡评分
     */
    protected function calculateBalanceScore(array $wuxingStats): int
    {
        $values = array_values($wuxingStats);
        $max = max($values);
        $min = min($values);
        $avg = array_sum($values) / count($values);
        
        // 计算标准差
        $variance = 0;
        foreach ($values as $value) {
            $variance += pow($value - $avg, 2);
        }
        $stdDev = sqrt($variance / count($values));
        
        // 标准差越小，五行越平衡
        $balanceScore = 100 - ($stdDev * 20);
        
        // 检查是否有缺失的五行
        $missingCount = 0;
        foreach ($wuxingStats as $value) {
            if ($value < 0.2) {
                $missingCount++;
            }
        }
        
        // 缺失的五行越多，评分越低
        $balanceScore -= ($missingCount * 10);
        
        return max(0, min(100, round($balanceScore)));
    }
    
    /**
     * 计算喜用神评分
     */
    protected function calculateYongshenScore(array $wuxingStats, array $yongshen): int
    {
        if (empty($yongshen)) {
            return 70;
        }
        
        $shen = $yongshen['shen'] ?? '';
        $shishen = $yongshen['shishen'] ?? '';
        
        // 喜用神对应的五行
        $yongshenWuxing = '';
        if (in_array($shen, ['印', '正印', '偏印'], true)) {
            $yongshenWuxing = $this->shishenToWuxing($shishen);
        } elseif (in_array($shen, ['官', '正官', '七杀'], true)) {
            $yongshenWuxing = $this->shishenToWuxing($shishen);
        } elseif (in_array($shen, ['财', '正财', '偏财'], true)) {
            $yongshenWuxing = $this->shishenToWuxing($shishen);
        } elseif (in_array($shen, ['食', '食神', '伤官'], true)) {
            $yongshenWuxing = $this->shishenToWuxing($shishen);
        }
        
        // 检查喜用神五行是否旺盛
        if ($yongshenWuxing !== '' && isset($wuxingStats[$yongshenWuxing])) {
            $yongshenStrength = $wuxingStats[$yongshenWuxing];
            
            if ($yongshenStrength >= 2.5) {
                return 95;
            } elseif ($yongshenStrength >= 2.0) {
                return 90;
            } elseif ($yongshenStrength >= 1.5) {
                return 85;
            } elseif ($yongshenStrength >= 1.0) {
                return 80;
            } elseif ($yongshenStrength >= 0.5) {
                return 75;
            } else {
                return 65;
            }
        }
        
        return 70;
    }
    
    /**
     * 十神转五行
     */
    protected function shishenToWuxing(string $shishen): string
    {
        $shishenWuxingMap = [
            '比肩' => '同', '劫财' => '同',
            '正官' => '克', '七杀' => '克',
            '正财' => '被克', '偏财' => '被克',
            '正印' => '生', '偏印' => '生',
            '食神' => '被生', '伤官' => '被生',
        ];
        
        return $shishenWuxingMap[$shishen] ?? '';
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