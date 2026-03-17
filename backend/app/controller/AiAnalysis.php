<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\model\AiPrompt;
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
     * 获取提示词
     */
    private function getPrompt(string $type, string $promptKey = null): ?AiPrompt
    {
        if ($promptKey) {
            return AiPrompt::getByKey($promptKey);
        }
        return AiPrompt::getDefault($type);
    }

    /**
     * 兼容旧版AI解盘路由
     */
    public function analyze(Request $request)
    {
        return $this->analyzeBazi($request);
    }

    /**
     * 兼容旧版流式AI解盘路由
     */
    public function analyzeStream(Request $request)
    {
        return $this->analyzeBaziStream($request);
    }

    /**
     * 获取AI分析历史（兼容旧版接口）
     */
    public function history()
    {
        return $this->success([
            'list' => [],
            'total' => 0,
        ], '暂无历史记录');
    }

    /**
     * AI八字解盘
     * 将八字数据发送到AI API进行分析
     */


    public function analyzeBazi(Request $request)
    {
        $baziData = $request->param('bazi');
        $promptKey = $request->param('prompt_key', '');
        $customPrompt = $request->param('prompt', '');
        
        if (empty($baziData)) {
            return $this->error('八字数据不能为空', 400);
        }
        
        if (!is_array($baziData)) {
            return $this->error('八字数据格式错误，应为数组类型', 400);
        }
        
        // 限制八字数据大小，防止过大请求
        if (count($baziData) > 100 || strlen(json_encode($baziData)) > 10000) {
            return $this->error('八字数据过大', 400);
        }
        
        // 限制自定义提示词长度
        if (!empty($customPrompt) && mb_strlen($customPrompt) > 2000) {
            return $this->error('自定义提示词不能超过2000字符', 400);
        }

        $config = $this->getAiConfig();
        
        // 检查配置
        if (empty($config['api_key']) || empty($config['api_url'])) {
            return $this->error('AI解盘服务未配置', 500);
        }

        // 获取提示词配置
        $prompt = $this->getPrompt('bazi', $promptKey);
        
        if (!$prompt) {
            // 使用默认构建的提示词
            $systemPrompt = $this->buildBaziSystemPrompt($baziData);
            $userPrompt = $customPrompt ?: '请为我详细解读这个八字命盘';
        } else {
            // 使用配置的提示词
            $systemPrompt = $prompt->system_prompt;
            $variables = $this->buildBaziVariables($baziData);
            $userPrompt = $prompt->renderUserPrompt($variables);
            
            if ($customPrompt) {
                $userPrompt .= "\n\n用户额外问题：" . $customPrompt;
            }
            
            // 增加使用次数
            $prompt->incrementUsage();
        }

        try {
            // 调用AI API
            $response = $this->callAiApi($systemPrompt, $userPrompt, $config);
            
            return $this->success([
                'analysis' => $response,
                'model' => $config['model'] ?? 'DeepSeek-V3.2',
                'prompt_used' => $prompt ? $prompt->name : 'default'
            ], 'AI解盘成功');
        } catch (\Exception $e) {
            Log::error('AI八字解盘失败: ' . $e->getMessage(), [
                'prompt_key' => $promptKey,
                'trace' => $e->getTraceAsString(),
            ]);
            return $this->error('AI解盘失败，请稍后重试', 500);
        }

    }

    /**
     * 流式AI解盘
     * 支持SSE流式返回
     */
    public function analyzeBaziStream(Request $request)
    {
        $baziData = $request->param('bazi');
        $promptKey = $request->param('prompt_key', '');
        $customPrompt = $request->param('prompt', '');
        
        if (empty($baziData)) {
            return $this->error('八字数据不能为空', 400);
        }
        
        if (!is_array($baziData)) {
            return $this->error('八字数据格式错误，应为数组类型', 400);
        }
        
        // 限制八字数据大小，防止过大请求
        if (count($baziData) > 100 || strlen(json_encode($baziData)) > 10000) {
            return $this->error('八字数据过大', 400);
        }
        
        // 限制自定义提示词长度
        if (!empty($customPrompt) && mb_strlen($customPrompt) > 2000) {
            return $this->error('自定义提示词不能超过2000字符', 400);
        }

        $config = $this->getAiConfig();
        
        if (empty($config['api_key']) || empty($config['api_url'])) {
            return $this->error('AI解盘服务未配置', 500);
        }

        // 获取提示词配置
        $prompt = $this->getPrompt('bazi', $promptKey);
        
        if (!$prompt) {
            $systemPrompt = $this->buildBaziSystemPrompt($baziData);
            $userPrompt = $customPrompt ?: '请为我详细解读这个八字命盘';
        } else {
            $systemPrompt = $prompt->system_prompt;
            $variables = $this->buildBaziVariables($baziData);
            $userPrompt = $prompt->renderUserPrompt($variables);
            
            if ($customPrompt) {
                $userPrompt .= "\n\n用户额外问题：" . $customPrompt;
            }
            
            $prompt->incrementUsage();
        }

        $this->prepareSseResponse();

        try {
            $this->callAiApiStream($systemPrompt, $userPrompt, $config);
            $this->emitSseDone();
        } catch (\Exception $e) {
            Log::error('AI流式解盘失败: ' . $e->getMessage(), [
                'prompt_key' => $promptKey,
                'trace' => $e->getTraceAsString(),
            ]);
            $this->emitSseError('AI流式解盘失败，请稍后重试');
        }

        exit;

    }

    /**
     * 构建八字变量
     */
    private function buildBaziVariables(array $baziData): array
    {
        return [
            'year_gan' => $baziData['year']['gan'] ?? '',
            'year_zhi' => $baziData['year']['zhi'] ?? '',
            'year_nayin' => $baziData['year']['nayin'] ?? '',
            'year_shishen' => $baziData['year']['shishen'] ?? '',
            'month_gan' => $baziData['month']['gan'] ?? '',
            'month_zhi' => $baziData['month']['zhi'] ?? '',
            'month_nayin' => $baziData['month']['nayin'] ?? '',
            'month_shishen' => $baziData['month']['shishen'] ?? '',
            'day_gan' => $baziData['day']['gan'] ?? '',
            'day_zhi' => $baziData['day']['zhi'] ?? '',
            'day_nayin' => $baziData['day']['nayin'] ?? '',
            'day_master' => $baziData['day_master'] ?? '',
            'day_master_wuxing' => $baziData['day_master_wuxing'] ?? '',
            'hour_gan' => $baziData['hour']['gan'] ?? '',
            'hour_zhi' => $baziData['hour']['zhi'] ?? '',
            'hour_nayin' => $baziData['hour']['nayin'] ?? '',
            'hour_shishen' => $baziData['hour']['shishen'] ?? '',
            'wuxing_jin' => $baziData['wuxing_stats']['金'] ?? 0,
            'wuxing_mu' => $baziData['wuxing_stats']['木'] ?? 0,
            'wuxing_shui' => $baziData['wuxing_stats']['水'] ?? 0,
            'wuxing_huo' => $baziData['wuxing_stats']['火'] ?? 0,
            'wuxing_tu' => $baziData['wuxing_stats']['土'] ?? 0,
        ];
    }

    /**
     * 构建八字系统提示（默认提示词）
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
        // SSL验证配置，防止中间人攻击
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

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

        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($payloadJson === false) {
            throw new \Exception('流式请求参数编码失败');
        }

        ignore_user_abort(false);
        @set_time_limit(0);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        // 启用SSL验证，防止中间人攻击
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $data) {
            if (connection_aborted()) {
                return 0;
            }

            echo $data;
            if (function_exists('ob_flush')) {
                @ob_flush();
            }
            flush();
            return strlen($data);
        });

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (connection_aborted()) {
            curl_close($ch);
            return;
        }

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception('请求失败：' . $error);
        }

        curl_close($ch);

        if ($httpCode !== 200) {
            throw new \Exception('流式API返回错误：HTTP ' . $httpCode);
        }
    }


    /**
     * 准备SSE响应头
     */
    private function prepareSseResponse(): void
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache, no-transform');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no');
    }

    /**
     * 输出SSE错误事件
     */
    private function emitSseError(string $message): void
    {
        $payload = json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
        if ($payload === false) {
            $payload = '{"error":"AI流式解盘失败，请稍后重试"}';
        }

        echo "data: {$payload}\n\n";
        $this->emitSseDone();
    }

    /**
     * 输出SSE结束标记
     */
    private function emitSseDone(): void
    {
        echo "data: [DONE]\n\n";
        if (function_exists('ob_flush')) {
            @ob_flush();
        }
        flush();
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
        
        return $this->success($config, '获取成功');
    }

    /**
     * 保存AI配置（后台使用）
     */
    public function saveConfig(Request $request)
    {
        $config = $request->param();
        
        if (empty($config['api_url']) || empty($config['model'])) {
            return $this->error('API地址和模型名称不能为空', 400);
        }

        try {
            $config['api_url'] = $this->normalizeExternalApiUrl((string) $config['api_url']);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        }
        
        // 获取旧配置
        $oldConfig = $this->getAiConfig();
        
        // 如果API Key未提供或为空，保留原值
        if (!isset($config['api_key']) || empty($config['api_key'])) {
            $config['api_key'] = $oldConfig['api_key'] ?? '';
        }
        // 如果API Key是掩码格式，不更新
        elseif (strpos($config['api_key'], '****') !== false) {
            $config['api_key'] = $oldConfig['api_key'] ?? '';
        }
        
        // 保存到配置文件（实际项目中应该保存到数据库或安全存储）
        // 这里简化处理，实际应该使用数据库表来存储

        $responseConfig = $config;
        if (!empty($responseConfig['api_key'])) {
            $responseConfig['api_key'] = substr($responseConfig['api_key'], 0, 10) . '****' . substr($responseConfig['api_key'], -4);
        }
        
        return $this->success($responseConfig, '保存成功');
    }

    /**
     * 测试AI连接
     */
    public function testConnection(Request $request)
    {
        $config = $request->param('config', []);
        
        try {
            $validatedConfig = $this->validateTestConnectionConfig($config);
            $payload = [
                'model' => $validatedConfig['model'],
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
            curl_setopt($ch, CURLOPT_URL, $validatedConfig['api_url']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $validatedConfig['api_key'],
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 0);
            // 启用SSL验证，防止中间人攻击
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            if (defined('CURLOPT_PROTOCOLS') && defined('CURLPROTO_HTTPS')) {
                curl_setopt($ch, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);
            }

            $response = curl_exec($ch);
            if ($response === false) {
                $errorMessage = curl_error($ch) ?: '未知网络错误';
                curl_close($ch);
                throw new \RuntimeException('AI连接测试请求失败: ' . $errorMessage);
            }

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                $data = json_decode($response, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $this->success([
                        'model' => $data['model'] ?? $validatedConfig['model'],
                        'status' => 'ok'
                    ], '连接成功');
                }
            }

            return $this->error('连接失败，请检查配置', 500);
        } catch (\InvalidArgumentException $e) {
            return $this->respondBusinessException($e, 'ai_test_connection_validate', '测试配置无效', 400, [
                'api_host' => $apiHost,
                'model' => $modelInput,
            ]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('ai_test_connection', $e, '连接失败，请检查配置后重试', [
                'api_host' => $apiHost,
                'model' => $modelInput,
            ]);
        }

    }

    /**
     * 验证AI连接测试参数
     */
    private function validateTestConnectionConfig(mixed $config): array
    {
        if (!is_array($config)) {
            throw new \InvalidArgumentException('测试配置格式无效');
        }

        $apiUrl = trim((string) ($config['api_url'] ?? ''));
        $apiKey = trim((string) ($config['api_key'] ?? ''));
        $model = trim((string) ($config['model'] ?? ''));

        if ($apiUrl === '') {
            throw new \InvalidArgumentException('API地址不能为空');
        }

        if ($model === '') {
            throw new \InvalidArgumentException('模型名称不能为空');
        }

        if ($apiKey === '') {
            throw new \InvalidArgumentException('API Key不能为空');
        }

        return [
            'api_url' => $this->normalizeExternalApiUrl($apiUrl),
            'api_key' => $apiKey,
            'model' => $model,
        ];
    }

    /**
     * 规范并校验外部AI接口地址，阻断明显的SSRF目标
     */
    private function normalizeExternalApiUrl(string $apiUrl): string
    {
        if (!filter_var($apiUrl, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('API地址格式无效');
        }

        $parts = parse_url($apiUrl);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($scheme !== 'https') {
            throw new \InvalidArgumentException('API地址必须使用HTTPS');
        }

        if ($host === '') {
            throw new \InvalidArgumentException('API地址缺少主机名');
        }

        if ($this->isForbiddenAiHost($host)) {
            throw new \InvalidArgumentException('API地址指向受限主机，请使用公网HTTPS接口');
        }

        return $apiUrl;
    }

    /**
     * 判断主机是否为内网/本机/保留地址
     */
    private function isForbiddenAiHost(string $host): bool
    {
        $normalizedHost = trim($host, '[]');

        if (in_array($normalizedHost, ['localhost', '0.0.0.0'], true) || str_ends_with($normalizedHost, '.local')) {
            return true;
        }

        if (filter_var($normalizedHost, FILTER_VALIDATE_IP)) {
            return $this->isPrivateOrReservedIp($normalizedHost);
        }

        foreach ($this->resolveHostIpv4List($normalizedHost) as $ip) {
            if ($this->isPrivateOrReservedIp($ip)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 解析主机对应的 IPv4 地址列表
     */
    private function resolveHostIpv4List(string $host): array
    {
        $ips = @gethostbynamel($host);
        if ($ips === false) {
            return [];
        }

        return array_values(array_unique(array_filter($ips, static fn ($ip) => is_string($ip) && $ip !== '')));
    }

    /**
     * 判断IP是否属于私网或保留地址段
     */
    private function isPrivateOrReservedIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}

