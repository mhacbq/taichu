<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use think\Request;
use think\facade\Config;

/**
 * AI解盘控制器
 * 调用外部AI API进行八字分析
 */
class AiAnalysis extends BaseController
{
    /**
     * AI解盘配置缓存
     */
    private function getAiConfig(): array
    {
        return Config::get('ai', []);
    }

    /**
     * AI八字解盘
     * 将八字数据发送到AI API进行分析
     */
    public function analyzeBazi(Request $request)
    {
        $baziData = $request->param('bazi');
        $prompt = $request->param('prompt', '');
        
        if (empty($baziData)) {
            return json(['code' => 400, 'message' => '八字数据不能为空']);
        }

        $config = $this->getAiConfig();
        
        // 检查配置
        if (empty($config['api_key']) || empty($config['api_url'])) {
            return json(['code' => 500, 'message' => 'AI解盘服务未配置']);
        }

        // 构建八字信息的系统提示
        $systemPrompt = $this->buildBaziSystemPrompt($baziData);
        
        // 如果用户提供了自定义提示，则使用自定义提示
        $userPrompt = $prompt ?: '请为我详细解读这个八字命盘';

        try {
            // 调用AI API
            $response = $this->callAiApi($systemPrompt, $userPrompt, $config);
            
            return json([
                'code' => 0,
                'message' => 'success',
                'data' => [
                    'analysis' => $response,
                    'model' => $config['model'] ?? 'DeepSeek-V3.2'
                ]
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => 'AI解盘失败：' . $e->getMessage()
            ]);
        }
    }

    /**
     * 流式AI解盘
     * 支持SSE流式返回
     */
    public function analyzeBaziStream(Request $request)
    {
        $baziData = $request->param('bazi');
        $prompt = $request->param('prompt', '');
        
        if (empty($baziData)) {
            return json(['code' => 400, 'message' => '八字数据不能为空']);
        }

        $config = $this->getAiConfig();
        
        if (empty($config['api_key']) || empty($config['api_url'])) {
            return json(['code' => 500, 'message' => 'AI解盘服务未配置']);
        }

        $systemPrompt = $this->buildBaziSystemPrompt($baziData);
        $userPrompt = $prompt ?: '请为我详细解读这个八字命盘';

        // 设置SSE响应头
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');

        try {
            $this->callAiApiStream($systemPrompt, $userPrompt, $config);
        } catch (\Exception $e) {
            echo "data: " . json_encode(['error' => $e->getMessage()]) . "\n\n";
        }

        exit;
    }

    /**
     * 构建八字系统提示
     */
    private function buildBaziSystemPrompt(array $baziData): string
    {
        $year = $baziData['year'] ?? [];
        $month = $baziData['month'] ?? [];
        $day = $baziData['day'] ?? [];
        $hour = $baziData['hour'] ?? [];
        $dayMaster = $baziData['day_master'] ?? '';
        $wuxingStats = $baziData['wuxing_stats'] ?? [];
        
        $prompt = "你是一位资深的八字命理大师，拥有20年以上的命理研究经验。请基于以下八字信息进行专业解读：\n\n";
        $prompt .= "【八字排盘】\n";
        $prompt .= "年柱：{$year['gan']}{$year['zhi']}（{$year['nayin']}）\n";
        $prompt .= "月柱：{$month['gan']}{$month['zhi']}（{$month['nayin']}）\n";
        $prompt .= "日柱：{$day['gan']}{$day['zhi']}（{$day['nayin']}）- 日主：{$dayMaster}\n";
        $prompt .= "时柱：{$hour['gan']}{$hour['zhi']}（{$hour['nayin']}）\n\n";
        
        $prompt .= "【五行分布】\n";
        foreach ($wuxingStats as $wx => $count) {
            $prompt .= "{$wx}：{$count}个\n";
        }
        $prompt .= "\n";
        
        $prompt .= "【十神配置】\n";
        $prompt .= "年干十神：{$year['shishen']}，月干十神：{$month['shishen']}，时干十神：{$hour['shishen']}\n\n";
        
        $prompt .= "请从以下几个方面进行详细解读：\n";
        $prompt .= "1. 日主特性和性格分析\n";
        $prompt .= "2. 五行旺衰与喜用神分析\n";
        $prompt .= "3. 事业财运分析\n";
        $prompt .= "4. 感情婚姻分析\n";
        $prompt .= "5. 健康状况分析\n";
        $prompt .= "6. 近期运势建议\n\n";
        $prompt .= "请以专业、易懂、温暖的方式进行分析，给出具体可行的建议。";
        
        return $prompt;
    }

    /**
     * 调用AI API（非流式）
     */
    private function callAiApi(string $systemPrompt, string $userPrompt, array $config): string
    {
        $apiKey = $config['api_key'];
        $apiUrl = $config['api_url'];
        $model = $config['model'] ?? 'DeepSeek-V3.2';

        $payload = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemPrompt
                ],
                [
                    'role' => 'user',
                    'content' => $userPrompt
                ]
            ],
            'stream' => false,
            'extra_body' => [
                'enable_thinking' => $config['enable_thinking'] ?? false,
                'provider' => [
                    'only' => [],
                    'order' => [],
                    'sort' => null,
                    'input_price_range' => [],
                    'output_price_range' => [],
                    'input_length_range' => [],
                    'output_length_range' => [],
                    'throughput_range' => [],
                    'latency_range' => []
                ]
            ]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new \Exception('请求失败：' . curl_error($ch));
        }
        
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception('API返回错误：HTTP ' . $httpCode);
        }

        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('解析响应失败');
        }

        // 根据API响应格式提取内容
        if (isset($data['choices'][0]['message']['content'])) {
            return $data['choices'][0]['message']['content'];
        } elseif (isset($data['content'])) {
            return $data['content'];
        } else {
            throw new \Exception('无法获取AI响应内容');
        }
    }

    /**
     * 调用AI API（流式）
     */
    private function callAiApiStream(string $systemPrompt, string $userPrompt, array $config): void
    {
        $apiKey = $config['api_key'];
        $apiUrl = $config['api_url'];
        $model = $config['model'] ?? 'DeepSeek-V3.2';

        $payload = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $systemPrompt
                ],
                [
                    'role' => 'user',
                    'content' => $userPrompt
                ]
            ],
            'stream' => true,
            'extra_body' => [
                'enable_thinking' => $config['enable_thinking'] ?? false,
                'provider' => [
                    'only' => [],
                    'order' => [],
                    'sort' => null,
                    'input_price_range' => [],
                    'output_price_range' => [],
                    'input_length_range' => [],
                    'output_length_range' => [],
                    'throughput_range' => [],
                    'latency_range' => []
                ]
            ]
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) {
            echo $data;
            flush();
            return strlen($data);
        });

        curl_exec($ch);
        
        if (curl_errno($ch)) {
            throw new \Exception('请求失败：' . curl_error($ch));
        }
        
        curl_close($ch);
    }

    /**
     * 获取AI配置信息（后台使用）
     */
    public function getConfig()
    {
        $config = $this->getAiConfig();
        
        // 隐藏完整的API Key
        if (!empty($config['api_key'])) {
            $config['api_key'] = substr($config['api_key'], 0, 10) . '****' . substr($config['api_key'], -4);
        }
        
        return json([
            'code' => 0,
            'message' => 'success',
            'data' => $config
        ]);
    }

    /**
     * 保存AI配置（后台使用）
     */
    public function saveConfig(Request $request)
    {
        $config = $request->param();
        
        // 验证必要字段
        if (empty($config['api_url']) || empty($config['model'])) {
            return json(['code' => 400, 'message' => 'API地址和模型名称不能为空']);
        }
        
        // 如果API Key是掩码格式，不更新
        if (strpos($config['api_key'], '****') !== false) {
            $oldConfig = $this->getAiConfig();
            $config['api_key'] = $oldConfig['api_key'] ?? '';
        }
        
        // 保存到配置文件（实际项目中应该保存到数据库或安全存储）
        // 这里简化处理，实际应该使用数据库表来存储
        
        return json([
            'code' => 0,
            'message' => '保存成功',
            'data' => $config
        ]);
    }

    /**
     * 测试AI连接
     */
    public function testConnection(Request $request)
    {
        $config = $request->param('config', []);
        
        try {
            $payload = [
                'model' => $config['model'] ?? 'DeepSeek-V3.2',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => 'Hello'
                    ]
                ],
                'stream' => false,
                'extra_body' => [
                    'enable_thinking' => false,
                    'provider' => [
                        'only' => [],
                        'order' => [],
                        'sort' => null,
                        'input_price_range' => [],
                        'output_price_range' => [],
                        'input_length_range' => [],
                        'output_length_range' => [],
                        'throughput_range' => [],
                        'latency_range' => []
                    ]
                ]
            ];

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $config['api_url']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $config['api_key'],
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                $data = json_decode($response, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return json([
                        'code' => 0,
                        'message' => '连接成功',
                        'data' => [
                            'model' => $data['model'] ?? $config['model'],
                            'status' => 'ok'
                        ]
                    ]);
                }
            }

            return json([
                'code' => 500,
                'message' => '连接失败，请检查配置'
            ]);
        } catch (\Exception $e) {
            return json([
                'code' => 500,
                'message' => '连接失败：' . $e->getMessage()
            ]);
        }
    }
}
