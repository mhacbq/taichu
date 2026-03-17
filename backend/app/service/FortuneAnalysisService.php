<?php
declare(strict_types=1);

namespace app\service;

/**
 * 运势分析服务
 *
 * 提供大运流年运势分析
 */
class FortuneAnalysisService
{
    /**
     * 分析大运流年运势
     *
     * @param array $bazi 八字数据
     * @param array $daYun 大运数据
     * @param array $liuNian 流年数据
     * @return array 运势分析结果
     */
    public function analyzeDaYunLiuNian(array $bazi, array $daYun, array $liuNian): array
    {
        $dayGan = $bazi['day']['gan'] ?? '';
        $dayZhi = $bazi['day']['zhi'] ?? '';

        $analysis = [];

        // 分析每个流年。旧实现误把数组键 0~4 当成公历年份，
        // 导致前端看到 0/1/2/3/4，最佳/最差流年也失去实际应期。
        foreach ($liuNian as $yearKey => $yearData) {
            $yearGan = $yearData['gan'] ?? '';
            $yearZhi = $yearData['zhi'] ?? '';
            $actualYear = (int)($yearData['year'] ?? 0);

            if ($actualYear <= 0 && is_numeric($yearKey)) {
                $actualYear = (int)$yearKey;
            }

            $analysis[] = [
                'year' => $actualYear,
                'ganzhi' => $yearGan . $yearZhi,
                'overall' => $this->calculateYearFortune($dayGan, $dayZhi, $yearGan, $yearZhi),
                'career' => $this->analyzeCareerFortune($dayGan, $yearGan, $yearZhi),
                'wealth' => $this->analyzeWealthFortune($dayGan, $yearGan, $yearZhi),
                'relationship' => $this->analyzeRelationshipFortune($dayGan, $yearGan, $yearZhi),
                'health' => $this->analyzeHealthFortune($dayZhi, $yearZhi),
            ];
        }

        usort($analysis, static fn(array $left, array $right): int => ($left['year'] ?? 0) <=> ($right['year'] ?? 0));

        return [
            'years' => $analysis,
            'summary' => $this->generateSummary($analysis),
        ];
    }

    /**
     * 计算流年总体运势
     */
    protected function calculateYearFortune(string $dayGan, string $dayZhi, string $yearGan, string $yearZhi): string
    {
        $score = 50; // 基础分

        // 天干十神关系
        $ganRelation = $this->getShiShenRelation($dayGan, $yearGan);
        $score += $this->getShiShenScore($ganRelation);

        // 地支刑冲合害
        $zhiRelation = $this->getZhiRelation($dayZhi, $yearZhi);
        $score += $this->getZhiRelationScore($zhiRelation);

        // 限制分数范围
        $score = max(20, min(95, $score));

        if ($score >= 80) {
            return '大吉';
        } elseif ($score >= 65) {
            return '吉';
        } elseif ($score >= 45) {
            return '平';
        } elseif ($score >= 30) {
            return '凶';
        }

        return '大凶';
    }

    /**
     * 分析事业运势
     */
    protected function analyzeCareerFortune(string $dayGan, string $yearGan, string $yearZhi): array
    {
        $ganRelation = $this->getShiShenRelation($dayGan, $yearGan);

        $careerFortunes = [
            '正官' => ['trend' => '上升', 'advice' => '适合升职、谋求管理职位'],
            '七杀' => ['trend' => '波动', 'advice' => '有压力但也有机会，需谨慎行事'],
            '正印' => ['trend' => '稳定', 'advice' => '适合学习进修、考取证书'],
            '偏印' => ['trend' => '变化', 'advice' => '思维活跃，适合创新工作'],
            '比肩' => ['trend' => '平稳', 'advice' => '有合作机会，但竞争激烈'],
            '劫财' => ['trend' => '下降', 'advice' => '注意职场竞争，避免冲动决策'],
            '食神' => ['trend' => '上升', 'advice' => '创意丰富，适合展示才华'],
            '伤官' => ['trend' => '波动', 'advice' => '有表现机会，但注意言行'],
            '正财' => ['trend' => '稳定', 'advice' => '踏实工作会有回报'],
            '偏财' => ['trend' => '上升', 'advice' => '有意外机会，适合拓展业务'],
        ];

        return $careerFortunes[$ganRelation] ?? ['trend' => '平稳', 'advice' => '保持现状，稳步发展'];
    }

    /**
     * 分析财富运势
     */
    protected function analyzeWealthFortune(string $dayGan, string $yearGan, string $yearZhi): array
    {
        $ganRelation = $this->getShiShenRelation($dayGan, $yearGan);

        $wealthFortunes = [
            '正财' => ['trend' => '稳定', 'advice' => '正财运佳，适合稳健理财'],
            '偏财' => ['trend' => '上升', 'advice' => '偏财运旺，可适当投资'],
            '食神' => ['trend' => '上升', 'advice' => '财源广进，有意外之喜'],
            '伤官' => ['trend' => '波动', 'advice' => '财来财去，需谨慎理财'],
            '劫财' => ['trend' => '下降', 'advice' => '注意破财，避免借贷'],
            '比肩' => ['trend' => '平稳', 'advice' => '财运一般，量入为出'],
            '正官' => ['trend' => '稳定', 'advice' => '财从正道来，避免投机'],
            '七杀' => ['trend' => '波动', 'advice' => '财有波折，见好就收'],
            '正印' => ['trend' => '平稳', 'advice' => '稳定收入，不宜冒险'],
            '偏印' => ['trend' => '变化', 'advice' => '财运多变，保持警惕'],
        ];

        return $wealthFortunes[$ganRelation] ?? ['trend' => '平稳', 'advice' => '理财稳健，保守为宜'];
    }

    /**
     * 分析感情运势
     */
    protected function analyzeRelationshipFortune(string $dayGan, string $yearGan, string $yearZhi): array
    {
        $ganRelation = $this->getShiShenRelation($dayGan, $yearGan);

        $relationshipFortunes = [
            '正财' => ['status' => '桃花旺', 'advice' => '异性缘佳，把握机会'],
            '偏财' => ['status' => '机会多', 'advice' => '社交活跃，注意选择'],
            '正官' => ['status' => '稳定', 'advice' => '感情稳定，适合谈婚论嫁'],
            '七杀' => ['status' => '波动', 'advice' => '感情多变，需谨慎处理'],
            '食神' => ['status' => '和谐', 'advice' => '感情甜蜜，享受生活'],
            '伤官' => ['status' => '口舌', 'advice' => '易有口角，多沟通理解'],
            '正印' => ['status' => '平淡', 'advice' => '感情平稳，细水长流'],
            '偏印' => ['status' => '冷淡', 'advice' => '感情冷淡，需主动维护'],
            '比肩' => ['status' => '竞争', 'advice' => '有竞争者出现，需用心经营'],
            '劫财' => ['status' => '波折', 'advice' => '感情受阻，需耐心等待'],
        ];

        return $relationshipFortunes[$ganRelation] ?? ['status' => '平稳', 'advice' => '感情平顺，顺其自然'];
    }

    /**
     * 分析健康运势
     */
    protected function analyzeHealthFortune(string $dayZhi, string $yearZhi): array
    {
        $relation = $this->getZhiRelation($dayZhi, $yearZhi);

        $healthFortunes = [
            '相合' => ['status' => '良好', 'advice' => '身体状况良好，保持锻炼'],
            '相冲' => ['status' => '注意', 'advice' => '注意交通安全，避免剧烈运动'],
            '相刑' => ['status' => '小恙', 'advice' => '易有小病，注意饮食卫生'],
            '相害' => ['status' => '疲劳', 'advice' => '注意休息，避免过度劳累'],
            '无特殊' => ['status' => '平稳', 'advice' => '健康状况平稳'],
        ];

        return $healthFortunes[$relation] ?? ['status' => '平稳', 'advice' => '保持健康作息'];
    }

    /**
     * 获取十神关系
     */
    protected function getShiShenRelation(string $dayGan, string $targetGan): string
    {
        // 天干五行
        $ganWuxing = [
            '甲' => '木', '乙' => '木',
            '丙' => '火', '丁' => '火',
            '戊' => '土', '己' => '土',
            '庚' => '金', '辛' => '金',
            '壬' => '水', '癸' => '水',
        ];

        // 天干阴阳
        $ganYinyang = [
            '甲' => '阳', '乙' => '阴',
            '丙' => '阳', '丁' => '阴',
            '戊' => '阳', '己' => '阴',
            '庚' => '阳', '辛' => '阴',
            '壬' => '阳', '癸' => '阴',
        ];

        $dayWuxing = $ganWuxing[$dayGan] ?? '';
        $targetWuxing = $ganWuxing[$targetGan] ?? '';
        $dayYinYang = $ganYinyang[$dayGan] ?? '';
        $targetYinYang = $ganYinyang[$targetGan] ?? '';

        // 五行生克关系
        $shengke = [
            '金' => ['生' => '水', '克' => '木', '被生' => '土', '被克' => '火'],
            '木' => ['生' => '火', '克' => '土', '被生' => '水', '被克' => '金'],
            '水' => ['生' => '木', '克' => '火', '被生' => '金', '被克' => '土'],
            '火' => ['生' => '土', '克' => '金', '被生' => '木', '被克' => '水'],
            '土' => ['生' => '金', '克' => '水', '被生' => '火', '被克' => '木'],
        ];

        if ($dayWuxing === $targetWuxing) {
            return $dayYinYang === $targetYinYang ? '比肩' : '劫财';
        }

        if ($shengke[$dayWuxing]['生'] === $targetWuxing) {
            return $dayYinYang === $targetYinYang ? '食神' : '伤官';
        }

        if ($shengke[$dayWuxing]['克'] === $targetWuxing) {
            return $dayYinYang === $targetYinYang ? '偏财' : '正财';
        }

        if ($shengke[$dayWuxing]['被生'] === $targetWuxing) {
            return $dayYinYang === $targetYinYang ? '偏印' : '正印';
        }

        if ($shengke[$dayWuxing]['被克'] === $targetWuxing) {
            return $dayYinYang === $targetYinYang ? '七杀' : '正官';
        }

        return '比肩';
    }

    /**
     * 获取十神评分
     */
    protected function getShiShenScore(string $shiShen): int
    {
        $scores = [
            '正官' => 15,
            '七杀' => 5,
            '正印' => 12,
            '偏印' => 8,
            '比肩' => 5,
            '劫财' => -5,
            '食神' => 18,
            '伤官' => 8,
            '正财' => 15,
            '偏财' => 10,
        ];

        return $scores[$shiShen] ?? 0;
    }

    /**
     * 获取地支关系
     */
    protected function getZhiRelation(string $dayZhi, string $yearZhi): string
    {
        if ($dayZhi === $yearZhi) {
            return '相合';
        }

        // 六冲
        $chong = [
            '子' => '午', '午' => '子',
            '丑' => '未', '未' => '丑',
            '寅' => '申', '申' => '寅',
            '卯' => '酉', '酉' => '卯',
            '辰' => '戌', '戌' => '辰',
            '巳' => '亥', '亥' => '巳',
        ];

        if (isset($chong[$dayZhi]) && $chong[$dayZhi] === $yearZhi) {
            return '相冲';
        }

        // 六合
        $liuhe = [
            '子' => '丑', '丑' => '子',
            '寅' => '亥', '亥' => '寅',
            '卯' => '戌', '戌' => '卯',
            '辰' => '酉', '酉' => '辰',
            '巳' => '申', '申' => '巳',
            '午' => '未', '未' => '午',
        ];

        if (isset($liuhe[$dayZhi]) && $liuhe[$dayZhi] === $yearZhi) {
            return '相合';
        }

        return '无特殊';
    }

    /**
     * 获取地支关系评分
     */
    protected function getZhiRelationScore(string $relation): int
    {
        $scores = [
            '相合' => 10,
            '相冲' => -15,
            '相刑' => -10,
            '相害' => -8,
            '无特殊' => 0,
        ];

        return $scores[$relation] ?? 0;
    }

    /**
     * 生成运势总结
     */
    protected function generateSummary(array $analysis): array
    {
        if (empty($analysis)) {
            return [
                'best_year' => '',
                'best_fortune' => '',
                'worst_year' => '',
                'worst_fortune' => '',
                'overall_trend' => '相对平稳',
            ];
        }

        $bestYear = '';
        $worstYear = '';
        $bestFortune = '';
        $worstFortune = '';
        $bestScore = -999;
        $worstScore = 999;

        foreach ($analysis as $data) {
            $score = $this->fortuneToScore((string)($data['overall'] ?? '平'));
            $year = (string)($data['year'] ?? '');

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestYear = $year;
                $bestFortune = (string)($data['overall'] ?? '');
            }

            if ($score < $worstScore) {
                $worstScore = $score;
                $worstYear = $year;
                $worstFortune = (string)($data['overall'] ?? '');
            }
        }

        return [
            'best_year' => $bestYear,
            'best_fortune' => $bestFortune,
            'worst_year' => $worstYear,
            'worst_fortune' => $worstFortune,
            'overall_trend' => $bestScore > $worstScore + 20 ? '先苦后甜' : ($bestScore < $worstScore - 20 ? '需谨慎' : '相对平稳'),
        ];
    }

    /**
     * 运势转分数
     */
    protected function fortuneToScore(string $fortune): int
    {
        $scores = [
            '大吉' => 90,
            '吉' => 75,
            '平' => 50,
            '凶' => 30,
            '大凶' => 15,
        ];

        return $scores[$fortune] ?? 50;
    }
}
