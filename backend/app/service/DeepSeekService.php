<?php

namespace app\service;

use think\facade\Env;

/**
 * DeepSeek AI服务类
 * 用于六爻占卜、八字解读等AI智能分析
 */
class DeepSeekService
{
    const API_URL = 'https://api.deepseek.com/v1/chat/completions';
    const MODEL = 'deepseek-chat';
    
    /**
     * 发送聊天请求
     * 
     * @param string $prompt 提示词
     * @param array $options 额外选项
     * @return string AI回复内容
     * @throws \Exception
     */
    public static function chat(string $prompt, array $options = []): string
    {
        $apiKey = Env::get('DEEPSEEK_API_KEY');
        if (empty($apiKey)) {
            throw new \Exception('DeepSeek API密钥未配置');
        }
        
        $data = [
            'model' => $options['model'] ?? self::MODEL,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $options['system_prompt'] ?? '你是一位专业的命理占卜大师，精通六爻、八字、塔罗等占卜术。请用专业但易懂的语言进行解读。',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens' => $options['max_tokens'] ?? 2000,
            'stream' => false,
        ];
        
        $response = self::sendRequest($data, $apiKey);
        
        if (isset($response['choices'][0]['message']['content'])) {
            return $response['choices'][0]['message']['content'];
        }
        
        throw new \Exception('AI响应格式错误');
    }
    
    /**
     * 流式聊天（用于实时显示）
     * 
     * @param string $prompt 提示词
     * @param callable $callback 回调函数
     * @param array $options 额外选项
     * @return void
     */
    public static function chatStream(string $prompt, callable $callback, array $options = []): void
    {
        $apiKey = Env::get('DEEPSEEK_API_KEY');
        if (empty($apiKey)) {
            throw new \Exception('DeepSeek API密钥未配置');
        }
        
        $data = [
            'model' => $options['model'] ?? self::MODEL,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $options['system_prompt'] ?? '你是一位专业的命理占卜大师。',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => $options['temperature'] ?? 0.7,
            'max_tokens' => $options['max_tokens'] ?? 2000,
            'stream' => true,
        ];
        
        self::sendStreamRequest($data, $apiKey, $callback);
    }
    
    /**
     * 六爻专用解读
     * 
     * @param array $guaData 卦象数据
     * @return string 解读内容
     */
    public static function interpretLiuyao(array $guaData): string
    {
        $systemPrompt = <<<PROMPT
你是一位精通六爻占卜的大师，擅长《增删卜易》、《卜筮正宗》等经典。
请根据卦象进行专业解读，包括：
1. 卦象分析（本卦、变卦、互卦）
2. 用神分析（用神旺衰、有无伤克）
3. 动变分析（动爻变化、事情发展）
4. 应期推断（事情发生时间）
5. 具体建议

请用专业但易懂的语言，避免过于晦涩的术语。
PROMPT;
        
        $prompt = self::buildLiuyaoPrompt($guaData);
        
        return self::chat($prompt, [
            'system_prompt' => $systemPrompt,
            'temperature' => 0.8,
            'max_tokens' => 2500,
        ]);
    }
    
    /**
     * 八字专用解读
     * 
     * @param array $baziData 八字数据
     * @return string 解读内容
     */
    public static function interpretBazi(array $baziData): string
    {
        $systemPrompt = <<<PROMPT
你是一位精通八字命理的大师，擅长《滴天髓》、《子平真诠》等经典。
请根据八字进行专业解读，包括：
1. 命局分析（日主强弱、五行喜忌）
2. 性格分析
3. 事业财运
4. 感情婚姻
5. 健康提醒
6. 大运流年简析

【写作风格要求 - 巴纳姆效应技巧】
- 大量使用"你"开头，增强代入感
- 描述内心矛盾：先说优点，再说"但有时候你也会..."，让人觉得被深度理解
- 加入"潜力暗示"：你有未被完全发掘的潜力
- 使用"往往"、"可能"、"有时候"等模糊词，增加普遍适用性
- 将缺点转化为"成长空间"或"需要注意的地方"
- 加入"这一点你自己心里其实清楚"类的共鸣句

请用专业但易懂的语言，让每一句话都让人觉得"说的就是我"。
PROMPT;
        
        $prompt = self::buildBaziPrompt($baziData);
        
        return self::chat($prompt, [
            'system_prompt' => $systemPrompt,
            'temperature' => 0.8,
            'max_tokens' => 3000,
        ]);
    }
    
    /**
     * 塔罗专用解读
     * 
     * @param array $tarotData 塔罗数据
     * @return string 解读内容
     */
    public static function interpretTarot(array $tarotData): string
    {
        $systemPrompt = <<<PROMPT
你是一位精通韦特体系的塔罗解读者。
请根据牌阵进行专业解读，包括：
1. 单张牌义解读
2. 牌阵整体分析
3. 牌与牌之间的关联
4. 具体建议

要求：
- 只使用中文术语，不要输出 Enemy Dignity、Mutual Dignity、Neutral Dignity 等英文表达。
- 若需讨论四元素尊严，请使用“友好尊严 / 敌对尊严 / 中性尊严”等中文表述。
- 综合结论必须紧扣提问、牌位与正逆位，不要使用“接受命运的指引”等空泛套话。
- 语气可以温暖，但不要鸡汤化。
PROMPT;

        
        $prompt = self::buildTarotPrompt($tarotData);
        
        return self::chat($prompt, [
            'system_prompt' => $systemPrompt,
            'temperature' => 0.9,
            'max_tokens' => 2500,
        ]);
    }
    
    /**
     * 构建六爻提示词
     */
    private static function buildLiuyaoPrompt(array $data): string
    {
        $liuShenText = self::formatLiuShen($data['liu_shen'] ?? '');
        $yongShenText = self::formatYongShen($data['yong_shen'] ?? '');

        return <<<PROMPT
【所问事项】
{$data['question']}

【本卦】{$data['main_gua']}
【变卦】{$data['bian_gua']}
【互卦】{$data['hu_gua']}

【六爻】（从下到上）
{$data['yao_info']}

【动爻】第{$data['dong_yao']}爻

【六神】（从初爻到上爻）
{$liuShenText}

【用神】
{$yongShenText}

【日辰月建】
日辰：{$data['ri_chen']}
月建：{$data['yue_jian']}
旬空：{$data['xun_kong'] ?? '未提供'}

请进行详细解读。

PROMPT;
    }

    /**
     * 格式化六神信息。
     */
    private static function formatLiuShen($liuShen): string
    {
        if (!is_array($liuShen)) {
            return (string)$liuShen;
        }

        $positionMap = [1 => '初爻', 2 => '二爻', 3 => '三爻', 4 => '四爻', 5 => '五爻', 6 => '上爻'];
        $lines = [];
        foreach ($liuShen as $position => $name) {
            $displayName = $name === '螣蛇' ? '螣蛇（腾蛇）' : $name;
            $lines[] = ($positionMap[$position] ?? "第{$position}爻") . '：' . $displayName;
        }

        return implode("\n", $lines);
    }

    /**
     * 格式化用神信息。
     */
    private static function formatYongShen($yongShen): string
    {
        if (!is_array($yongShen)) {
            return (string)$yongShen;
        }

        $parts = [];
        if (!empty($yongShen['liuqin'])) {
            $parts[] = '六亲：' . $yongShen['liuqin'];
        }
        if (!empty($yongShen['position'])) {
            $parts[] = '爻位：第' . $yongShen['position'] . '爻';
        }
        if (!empty($yongShen['status'])) {
            $parts[] = '状态：' . $yongShen['status'];
        }
        if (!empty($yongShen['description'])) {
            $parts[] = '说明：' . $yongShen['description'];
        }

        return empty($parts) ? json_encode($yongShen, JSON_UNESCAPED_UNICODE) : implode("\n", $parts);
    }
    
    /**
     * 构建八字提示词
     */
    private static function buildBaziPrompt(array $data): string
    {
        return <<<PROMPT
【八字信息】
年柱：{$data['year_zhu']}
月柱：{$data['month_zhu']}
日柱：{$data['day_zhu']}
时柱：{$data['hour_zhu']}

【日主】{$data['day_master']}

【十神】
{$data['shishen']}

【五行力量】
{$data['wuxing_power']}

【当前大运】{$data['dayun']}

请进行详细解读。
PROMPT;
    }
    
    /**
     * 构建塔罗提示词
     */
    private static function buildTarotPrompt(array $data): string
    {
        $cards = is_array($data['cards'] ?? null) ? $data['cards'] : [];
        $spreadName = $data['spread_name'] ?? self::guessTarotSpreadName(count($cards));
        $cardsInfo = '';

        foreach ($cards as $index => $card) {
            $normalized = self::normalizeTarotPromptCard((array)$card, $index, count($cards));
            $position = $index + 1;
            $cardsInfo .= "【第{$position}张】{$normalized['name']}（{$normalized['position']}）\n";
            $cardsInfo .= "正逆位：{$normalized['orientation']}\n";
            $cardsInfo .= "元素：{$normalized['element']}\n";
            $cardsInfo .= "关键词：{$normalized['keywords']}\n";
            if ($normalized['orientation_meaning'] !== '') {
                $cardsInfo .= "位态释义：{$normalized['orientation_meaning']}\n";
            }
            $cardsInfo .= "\n";
        }
        
        return <<<PROMPT
【所问事项】
{$data['question']}

【牌阵】{$spreadName}

{$cardsInfo}

请结合牌位、元素互动与正逆位差异进行详细解读。
仅使用中文表述；涉及元素尊严时请使用“友好尊严 / 敌对尊严 / 中性尊严”；综合结论务必回答用户问题本身，不要落到泛泛鼓励语。
PROMPT;

    }

    /**
     * 规范塔罗提示词所需字段，兼容当前控制器直接返回的抽牌结构。
     */
    private static function normalizeTarotPromptCard(array $card, int $index, int $totalCards): array
    {
        $isReversed = (bool)($card['reversed'] ?? false);
        $orientation = $card['orientation'] ?? ($isReversed ? '逆位' : '正位');
        $orientationMeaning = $card['orientation_meaning']
            ?? ($isReversed ? ($card['reversed_meaning'] ?? '') : ($card['meaning'] ?? ''));
        $keywords = $card['keywords'] ?? str_replace('，', '、', $orientationMeaning !== '' ? $orientationMeaning : ($card['meaning'] ?? ''));

        $element = TarotElementService::resolveCardElement($card);

        return [
            'name' => $card['name'] ?? ('第' . ($index + 1) . '张牌'),
            'position' => $card['position'] ?? self::guessTarotPositionLabel($totalCards, $index),
            'orientation' => $orientation,
            'orientation_meaning' => $orientationMeaning,
            'keywords' => $keywords,
            'element' => $element !== '' ? $element : '未知',
        ];

    }

    /**
     * 按抽牌数量推断牌阵名称。
     */
    private static function guessTarotSpreadName(int $cardCount): string
    {
        return match($cardCount) {
            1 => '单张牌',
            3 => '三牌阵',
            10 => '凯尔特十字',
            default => '通用牌阵',
        };
    }

    /**
     * 按牌阵推断标准牌位名称。
     */
    private static function guessTarotPositionLabel(int $cardCount, int $index): string
    {
        $positions = match($cardCount) {
            1 => ['今日指引'],
            3 => ['过去', '现在', '未来'],
            10 => ['当前状态', '障碍/挑战', '潜意识/基础', '过去影响', '目标可能', '近期发展', '你的态度', '外部环境', '希望/恐惧', '最终走向'],
            default => [],
        };

        return $positions[$index] ?? ('第' . ($index + 1) . '张');
    }
    
    /**
     * 发送HTTP请求
     */
    private static function sendRequest(array $data, string $apiKey): array
    {
        $ch = curl_init(self::API_URL);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new \Exception('请求失败：' . $error);
        }
        
        if ($httpCode != 200) {
            throw new \Exception('API返回错误：HTTP ' . $httpCode);
        }
        
        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('JSON解析失败');
        }
        
        return $result;
    }
    
    /**
     * 发送流式请求
     */
    private static function sendStreamRequest(array $data, string $apiKey, callable $callback): void
    {
        $ch = curl_init(self::API_URL);
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey,
            'Accept: text/event-stream',
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) use ($callback) {
            $lines = explode("\n", $data);
            foreach ($lines as $line) {
                $line = trim($line);
                if (strpos($line, 'data: ') === 0) {
                    $json = substr($line, 6);
                    if ($json === '[DONE]') {
                        return strlen($data);
                    }
                    
                    $decoded = json_decode($json, true);
                    if (isset($decoded['choices'][0]['delta']['content'])) {
                        $content = $decoded['choices'][0]['delta']['content'];
                        $callback($content);
                    }
                }
            }
            return strlen($data);
        });
        
        curl_exec($ch);
        curl_close($ch);
    }
}
