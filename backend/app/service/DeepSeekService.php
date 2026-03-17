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

请用专业但易懂的语言。
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
你是一位精通塔罗占卜的大师。
请根据牌阵进行专业解读，包括：
1. 单张牌义解读
2. 牌阵整体分析
3. 牌与牌之间的关联
4. 具体建议

请用温暖、鼓励的语言风格。
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
        $cardsInfo = '';
        foreach ($data['cards'] as $index => $card) {
            $position = $index + 1;
            $element = $card['element'] ?? '未知';
            $orientationMeaning = $card['orientation_meaning'] ?? ($card['reversed_meaning'] ?? '');
            $cardsInfo .= "【第{$position}张】{$card['name']}（{$card['position']}）\n";
            $cardsInfo .= "正逆位：{$card['orientation']}\n";
            $cardsInfo .= "元素：{$element}\n";
            $cardsInfo .= "关键词：{$card['keywords']}\n";
            if ($orientationMeaning !== '') {
                $cardsInfo .= "位态释义：{$orientationMeaning}\n";
            }
            $cardsInfo .= "\n";
        }
        
        return <<<PROMPT
【所问事项】
{$data['question']}

【牌阵】{$data['spread_name']}

{$cardsInfo}

请结合牌位、元素互动与正逆位差异进行详细解读。
PROMPT;
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
