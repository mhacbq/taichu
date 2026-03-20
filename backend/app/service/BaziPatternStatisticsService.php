<?php
declare(strict_types=1);

namespace app\service;

/**
 * 八字格局统计服务
 * 
 * 提供格局准确率统计、大数据分析等功能
 */
class BaziPatternStatisticsService
{
    /**
     * 统计格局分布
     */
    public function analyzePatternDistribution(array $bazis): array
    {
        $distribution = [
            'eight_patterns' => [],
            'special_patterns' => [],
            'shen_sha' => [],
        ];
        
        foreach ($bazis as $bazi) {
            $pattern = $bazi['pattern'] ?? [];
            
            // 统计八格
            foreach ($pattern['eight_patterns'] ?? [] as $eightPattern) {
                $name = $eightPattern['name'] ?? '';
                if ($name !== '') {
                    if (!isset($distribution['eight_patterns'][$name])) {
                        $distribution['eight_patterns'][$name] = [
                            'count' => 0,
                            'percentage' => 0,
                        ];
                    }
                    $distribution['eight_patterns'][$name]['count']++;
                }
            }
            
            // 统计特殊格局
            foreach ($pattern['special_patterns'] ?? [] as $specialPattern) {
                $name = $specialPattern['name'] ?? '';
                if ($name !== '') {
                    if (!isset($distribution['special_patterns'][$name])) {
                        $distribution['special_patterns'][$name] = [
                            'count' => 0,
                            'percentage' => 0,
                        ];
                    }
                    $distribution['special_patterns'][$name]['count']++;
                }
            }
            
            // 统计神煞
            foreach ($pattern['shen_sha'] ?? [] as $shenSha) {
                $name = $shenSha['name'] ?? '';
                if ($name !== '') {
                    if (!isset($distribution['shen_sha'][$name])) {
                        $distribution['shen_sha'][$name] = [
                            'count' => 0,
                            'percentage' => 0,
                        ];
                    }
                    $distribution['shen_sha'][$name]['count']++;
                }
            }
        }
        
        // 计算百分比
        $total = count($bazis);
        if ($total > 0) {
            foreach ($distribution['eight_patterns'] as $name => &$data) {
                $data['percentage'] = round($data['count'] / $total * 100, 2);
            }
            foreach ($distribution['special_patterns'] as $name => &$data) {
                $data['percentage'] = round($data['count'] / $total * 100, 2);
            }
            foreach ($distribution['shen_sha'] as $name => &$data) {
                $data['percentage'] = round($data['count'] / $total * 100, 2);
            }
        }
        
        // 按数量排序
        uasort($distribution['eight_patterns'], fn($a, $b) => $b['count'] <=> $a['count']);
        uasort($distribution['special_patterns'], fn($a, $b) => $b['count'] <=> $a['count']);
        uasort($distribution['shen_sha'], fn($a, $b) => $b['count'] <=> $a['count']);
        
        return [
            'distribution' => $distribution,
            'total_count' => $total,
            'top_eight_patterns' => array_slice($distribution['eight_patterns'], 0, 10, true),
            'top_special_patterns' => array_slice($distribution['special_patterns'], 0, 10, true),
            'top_shen_sha' => array_slice($distribution['shen_sha'], 0, 10, true),
        ];
    }
    
    /**
     * 统计格局层次分布
     */
    public function analyzePatternLevelDistribution(array $bazis): array
    {
        $distribution = [
            '上上' => 0,
            '上' => 0,
            '中上' => 0,
            '中' => 0,
            '下' => 0,
        ];
        
        foreach ($bazis as $bazi) {
            $pattern = $bazi['pattern'] ?? [];
            $level = $pattern['pattern_level']['level'] ?? '';
            if (isset($distribution[$level])) {
                $distribution[$level]++;
            }
        }
        
        // 计算百分比
        $total = count($bazis);
        $percentage = [];
        if ($total > 0) {
            foreach ($distribution as $level => $count) {
                $percentage[$level] = round($count / $total * 100, 2);
            }
        }
        
        return [
            'distribution' => $distribution,
            'percentage' => $percentage,
            'total_count' => $total,
        ];
    }
    
    /**
     * 统计神煞质量分布
     */
    public function analyzeShenShaQualityDistribution(array $bazis): array
    {
        $distribution = [
            '吉' => 0,
            '中' => 0,
            '凶' => 0,
        ];
        
        foreach ($bazis as $bazi) {
            $pattern = $bazi['pattern'] ?? [];
            foreach ($pattern['shen_sha'] ?? [] as $shenSha) {
                $quality = $shenSha['quality'] ?? '';
                if (isset($distribution[$quality])) {
                    $distribution[$quality]++;
                }
            }
        }
        
        // 计算百分比
        $total = array_sum($distribution);
        $percentage = [];
        if ($total > 0) {
            foreach ($distribution as $quality => $count) {
                $percentage[$quality] = round($count / $total * 100, 2);
            }
        }
        
        return [
            'distribution' => $distribution,
            'percentage' => $percentage,
            'total_count' => $total,
        ];
    }
    
    /**
     * 统计日主分布
     */
    public function analyzeDayMasterDistribution(array $bazis): array
    {
        $distribution = [];
        
        foreach ($bazis as $bazi) {
            $dayMaster = $bazi['day']['gan'] ?? '';
            if ($dayMaster !== '') {
                if (!isset($distribution[$dayMaster])) {
                    $distribution[$dayMaster] = 0;
                }
                $distribution[$dayMaster]++;
            }
        }
        
        // 计算百分比
        $total = count($bazis);
        $percentage = [];
        if ($total > 0) {
            foreach ($distribution as $dayMaster => $count) {
                $percentage[$dayMaster] = round($count / $total * 100, 2);
            }
        }
        
        return [
            'distribution' => $distribution,
            'percentage' => $percentage,
            'total_count' => $total,
        ];
    }
    
    /**
     * 统计喜用神分布
     */
    public function analyzeYongshenDistribution(array $bazis): array
    {
        $distribution = [
            '金' => 0,
            '木' => 0,
            '水' => 0,
            '火' => 0,
            '土' => 0,
        ];
        
        foreach ($bazis as $bazi) {
            $yongshen = $bazi['yongshen'] ?? [];
            $shen = $yongshen['shen'] ?? '';
            if (isset($distribution[$shen])) {
                $distribution[$shen]++;
            }
        }
        
        // 计算百分比
        $total = count($bazis);
        $percentage = [];
        if ($total > 0) {
            foreach ($distribution as $shen => $count) {
                $percentage[$shen] = round($count / $total * 100, 2);
            }
        }
        
        return [
            'distribution' => $distribution,
            'percentage' => $percentage,
            'total_count' => $total,
        ];
    }
    
    /**
     * 统计格局准确率
     */
    public function analyzePatternAccuracy(array $bazis, array $feedback): array
    {
        $accuracy = [
            'total' => 0,
            'correct' => 0,
            'incorrect' => 0,
            'accuracy_rate' => 0,
        ];
        
        foreach ($feedback as $item) {
            $accuracy['total']++;
            if ($item['is_correct'] ?? false) {
                $accuracy['correct']++;
            } else {
                $accuracy['incorrect']++;
            }
        }
        
        if ($accuracy['total'] > 0) {
            $accuracy['accuracy_rate'] = round($accuracy['correct'] / $accuracy['total'] * 100, 2);
        }
        
        return $accuracy;
    }
    
    /**
     * 统计用户满意度
     */
    public function analyzeUserSatisfaction(array $feedback): array
    {
        $satisfaction = [
            'total' => 0,
            'average_score' => 0,
            'score_distribution' => [
                1 => 0,
                2 => 0,
                3 => 0,
                4 => 0,
                5 => 0,
            ],
        ];
        
        $totalScore = 0;
        
        foreach ($feedback as $item) {
            $score = $item['score'] ?? 0;
            if ($score >= 1 && $score <= 5) {
                $satisfaction['total']++;
                $satisfaction['score_distribution'][$score]++;
                $totalScore += $score;
            }
        }
        
        if ($satisfaction['total'] > 0) {
            $satisfaction['average_score'] = round($totalScore / $satisfaction['total'], 2);
        }
        
        return $satisfaction;
    }
    
    /**
     * 统计常见问题
     */
    public function analyzeCommonIssues(array $feedback): array
    {
        $issues = [];
        
        foreach ($feedback as $item) {
            $issueType = $item['issue_type'] ?? '其他';
            $issueDescription = $item['issue_description'] ?? '';
            
            if (!isset($issues[$issueType])) {
                $issues[$issueType] = [
                    'count' => 0,
                    'percentage' => 0,
                    'descriptions' => [],
                ];
            }
            
            $issues[$issueType]['count']++;
            
            // 收集具体问题描述（去重）
            if ($issueDescription !== '' && !in_array($issueDescription, $issues[$issueType]['descriptions'])) {
                $issues[$issueType]['descriptions'][] = $issueDescription;
            }
        }
        
        // 计算百分比
        $total = count($feedback);
        if ($total > 0) {
            foreach ($issues as $issueType => &$data) {
                $data['percentage'] = round($data['count'] / $total * 100, 2);
            }
        }
        
        // 按数量排序
        uasort($issues, fn($a, $b) => $b['count'] <=> $a['count']);
        
        return [
            'issues' => $issues,
            'total_count' => $total,
        ];
    }
    
    /**
     * 生成统计报告
     */
    public function generateStatisticsReport(array $bazis, array $feedback): array
    {
        $patternDistribution = $this->analyzePatternDistribution($bazis);
        $patternLevelDistribution = $this->analyzePatternLevelDistribution($bazis);
        $shenShaQualityDistribution = $this->analyzeShenShaQualityDistribution($bazis);
        $dayMasterDistribution = $this->analyzeDayMasterDistribution($bazis);
        $yongshenDistribution = $this->analyzeYongshenDistribution($bazis);
        $patternAccuracy = $this->analyzePatternAccuracy($bazis, $feedback);
        $userSatisfaction = $this->analyzeUserSatisfaction($feedback);
        $commonIssues = $this->analyzeCommonIssues($feedback);
        
        return [
            'summary' => [
                'total_bazis' => count($bazis),
                'total_feedback' => count($feedback),
                'pattern_accuracy_rate' => $patternAccuracy['accuracy_rate'],
                'user_satisfaction_score' => $userSatisfaction['average_score'],
            ],
            'pattern_distribution' => $patternDistribution,
            'pattern_level_distribution' => $patternLevelDistribution,
            'shen_sha_quality_distribution' => $shenShaQualityDistribution,
            'day_master_distribution' => $dayMasterDistribution,
            'yongshen_distribution' => $yongshenDistribution,
            'pattern_accuracy' => $patternAccuracy,
            'user_satisfaction' => $userSatisfaction,
            'common_issues' => $commonIssues,
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }
}
