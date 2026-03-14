<?php
declare(strict_types=1);

namespace app\service;

/**
 * 合规服务类
 * 
 * 提供内容合规检查和敏感词过滤功能
 * 确保平台内容符合国家法律法规要求
 */
class ComplianceService
{
    /**
     * 高风险敏感词列表
     */
    protected static array $highRiskWords = [
        // 绝对禁止的词汇
        '算命', '占卜', '看相', '风水', '改运', '转运',
        '开光', '灵验', '必准', '100%准确', '天机',
        '泄露天机', '逆天改命', '化解', '破煞', '驱邪',
        '作法', '法事', '符咒', '降头', '巫术',
        
        // 承诺性词汇
        '保证', '承诺', '必定', '必然', '肯定',
        '一定', '绝对', '毫无疑问',
        
        // 恐吓性词汇
        '不化解就', '灾祸', '血光之灾', '大凶',
        '必死', '绝症', '家破人亡',
    ];
    
    /**
     * 中风险敏感词列表
     */
    protected static array $mediumRiskWords = [
        '预测', '预知', '先知', '预见',
        '命运', '命理', '命盘', '命格',
        '运势预测', '运势分析',
        '八字算命', '塔罗占卜',
        '人生轨迹', '命中注定',
        '前世今生', '轮回',
    ];
    
    /**
     * 建议替换词对照表
     */
    protected static array $replacementMap = [
        '算命' => '性格分析',
        '占卜' => '趣味测试',
        '预测' => '参考',
        '预知' => '了解',
        '命运' => '性格',
        '命理' => '文化',
        '运势' => '指数',
        '吉凶' => '趋势',
        '改运' => '调整心态',
        '转运' => '积极改变',
        '趋吉避凶' => '生活参考',
        '八字算命' => '生日分析',
        '塔罗占卜' => '塔罗测试',
        '人生轨迹' => '个性特点',
        '灵验' => '有趣',
        '必准' => '娱乐',
    ];
    
    /**
     * 检查文本是否包含敏感词
     * 
     * @param string $text 待检查文本
     * @return array 检查结果 ['has_risk' => bool, 'risks' => array]
     */
    public static function checkText(string $text): array
    {
        $risks = [];
        $hasHighRisk = false;
        $hasMediumRisk = false;
        
        // 检查高风险词汇
        foreach (self::$highRiskWords as $word) {
            if (stripos($text, $word) !== false) {
                $risks[] = [
                    'word' => $word,
                    'level' => 'high',
                    'suggestion' => self::$replacementMap[$word] ?? '建议删除'
                ];
                $hasHighRisk = true;
            }
        }
        
        // 检查中风险词汇
        foreach (self::$mediumRiskWords as $word) {
            if (stripos($text, $word) !== false) {
                $risks[] = [
                    'word' => $word,
                    'level' => 'medium',
                    'suggestion' => self::$replacementMap[$word] ?? '建议替换'
                ];
                $hasMediumRisk = true;
            }
        }
        
        return [
            'has_risk' => $hasHighRisk || $hasMediumRisk,
            'has_high_risk' => $hasHighRisk,
            'has_medium_risk' => $hasMediumRisk,
            'risks' => $risks,
            'clean_text' => self::cleanText($text)
        ];
    }
    
    /**
     * 清洗文本中的敏感词
     * 
     * @param string $text 待清洗文本
     * @return string 清洗后的文本
     */
    public static function cleanText(string $text): string
    {
        $cleaned = $text;
        
        foreach (self::$replacementMap as $sensitive => $replacement) {
            $cleaned = str_ireplace($sensitive, $replacement, $cleaned);
        }
        
        return $cleaned;
    }
    
    /**
     * 检查用户生成内容
     * 
     * @param string $content 用户内容
     * @return array 检查结果
     */
    public static function checkUserContent(string $content): array
    {
        $result = self::checkText($content);
        
        // 额外检查用户内容的特殊规则
        $forbiddenPatterns = [
            '/加微信.*算命/i',
            '/付费.*预测/i',
            '/收费.*改运/i',
            '/联系.*大师/i',
            '/\d{11}.*算命/',  // 手机号+算命
        ];
        
        foreach ($forbiddenPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $result['has_risk'] = true;
                $result['has_high_risk'] = true;
                $result['risks'][] = [
                    'word' => '涉嫌违规推广',
                    'level' => 'high',
                    'suggestion' => '禁止发布联系方式进行算命推广'
                ];
                break;
            }
        }
        
        return $result;
    }
    
    /**
     * 生成合规的测试说明
     * 
     * @return string 标准免责声明
     */
    public static function getStandardDisclaimer(): string
    {
        return '本测试基于传统文化娱乐算法生成，仅供娱乐参考，不具有科学依据。请理性看待，切勿过度解读。人生道路由自己把握，测试结果不构成任何决策依据。';
    }
    
    /**
     * 获取页面底部固定声明
     * 
     * @return array 声明内容
     */
    public static function getFooterDisclaimer(): array
    {
        return [
            'title' => '免责声明',
            'lines' => [
                '本网站所有内容仅供娱乐参考，不构成任何专业建议',
                '测试结果基于传统文化和概率算法生成，不具有科学依据，请理性对待',
                '© 2024 太初文化 版权所有'
            ],
            'links' => [
                ['text' => '用户协议', 'url' => '/terms'],
                ['text' => '隐私政策', 'url' => '/privacy']
            ]
        ];
    }
    
    /**
     * 验证测试结果内容
     * 
     * @param string $result 测试结果文本
     * @return bool 是否合规
     */
    public static function validateResult(string $result): bool
    {
        $check = self::checkText($result);
        
        // 高风险内容不允许
        if ($check['has_high_risk']) {
            return false;
        }
        
        // 检查是否包含承诺性表述
        $promises = ['保证', '承诺', '肯定', '一定', '必定'];
        foreach ($promises as $word) {
            if (stripos($result, $word) !== false) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 获取中性描述词汇
     * 
     * @param string $type 描述类型
     * @return string 中性词汇
     */
    public static function getNeutralTerm(string $type): string
    {
        $terms = [
            'good' => ['上升', '积极', '顺利', '向好'],
            'bad' => ['稳健', '谨慎', '沉淀', '调整'],
            'neutral' => ['平稳', '持平', '常态', '一般'],
            'advice' => ['建议', '参考', '提醒', '提示']
        ];
        
        $list = $terms[$type] ?? $terms['neutral'];
        return $list[array_rand($list)];
    }
}