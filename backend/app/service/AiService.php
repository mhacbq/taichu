<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Http;
use think\facade\Log;

/**
 * AI分析服务
 *
 * 支持多种AI模型：DeepSeek、OpenAI、Claude等
 */
class AiService
{
    // AI提供商配置
    const PROVIDER_DEEPSEEK = 'deepseek';
    const PROVIDER_OPENAI = 'openai';
    const PROVIDER_CLAUDE = 'claude';
    const SYSTEM_PROMPT = '你是一位资深的八字命理大师，精通子平八字、紫微斗数等传统命理学。你的分析专业、客观、有深度，同时用通俗易懂的语言表达。';

    // 默认提供商
    protected $defaultProvider;

    // API配置
    protected $config;

    public function __construct(string $provider = null)
    {
        $this->defaultProvider = $provider ?? ConfigService::get('ai_provider', self::PROVIDER_DEEPSEEK);
        $this->loadConfig();
    }

    /**
     * 加载AI配置
     */
    protected function loadConfig(): void
    {
        $this->config = [
            self::PROVIDER_DEEPSEEK => [
                'api_key' => ConfigService::get('ai_deepseek_api_key', ''),
                'base_url' => ConfigService::get('ai_deepseek_base_url', 'https://api.deepseek.com'),
                'model' => ConfigService::get('ai_deepseek_model', 'deepseek-chat'),
                'max_tokens' => 2000,
                'temperature' => 0.7,
            ],
            self::PROVIDER_OPENAI => [
                'api_key' => ConfigService::get('ai_openai_api_key', ''),
                'base_url' => ConfigService::get('ai_openai_base_url', 'https://api.openai.com'),
                'model' => ConfigService::get('ai_openai_model', 'gpt-3.5-turbo'),
                'max_tokens' => 2000,
                'temperature' => 0.7,
            ],
            self::PROVIDER_CLAUDE => [
                'api_key' => ConfigService::get('ai_claude_api_key', ''),
                'base_url' => ConfigService::get('ai_claude_base_url', 'https://api.anthropic.com'),
                'model' => ConfigService::get('ai_claude_model', 'claude-3-sonnet-20240229'),
                'max_tokens' => 2000,
                'temperature' => 0.7,
            ],
        ];
    }

    /**
     * 八字合婚AI分析
     *
     * @param array $hehunResult 合婚结果
     * @param array $maleBazi 男方八字
     * @param array $femaleBazi 女方八字
     * @param string $maleName 男方姓名
     * @param string $femaleName 女方姓名
     * @return array|null
     */
    public function analyzeHehun(
        array $hehunResult,
        array $maleBazi,
        array $femaleBazi,
        string $maleName,
        string $femaleName
    ): ?array {
        try {
            $prompt = $this->buildHehunPrompt($hehunResult, $maleBazi, $femaleBazi, $maleName, $femaleName);
            $response = $this->callAiApi($prompt);

            if (!$response) {
                return null;
            }

            return $this->parseHehunResponse($response);
        } catch (\Throwable $e) {
            $this->logAnalysisFailure('hehun', $e);
            return null;
        }
    }

    /**
     * 八字排盘AI解读
     *
     * @param array $bazi 八字数据
     * @param string $gender 性别
     * @return array|null
     */
    public function analyzeBazi(array $bazi, string $gender): ?array
    {
        try {
            $prompt = $this->buildBaziPrompt($bazi, $gender);
            $response = $this->callAiApi($prompt);

            if (!$response) {
                return null;
            }

            return $this->parseBaziResponse($response);
        } catch (\Throwable $e) {
            $this->logAnalysisFailure('bazi', $e);
            return null;
        }
    }

    /**
     * 构建合婚分析提示词
     */
    protected function buildHehunPrompt(
        array $hehunResult,
        array $maleBazi,
        array $femaleBazi,
        string $maleName,
        string $femaleName
    ): string {
        $score = $hehunResult['score'];
        $level = $hehunResult['level_text'];

        $maleBaziStr = sprintf(
            "%s%s %s%s %s%s %s%s",
            $maleBazi['year']['gan'], $maleBazi['year']['zhi'],
            $maleBazi['month']['gan'], $maleBazi['month']['zhi'],
            $maleBazi['day']['gan'], $maleBazi['day']['zhi'],
            $maleBazi['hour']['gan'], $maleBazi['hour']['zhi']
        );

        $femaleBaziStr = sprintf(
            "%s%s %s%s %s%s %s%s",
            $femaleBazi['year']['gan'], $femaleBazi['year']['zhi'],
            $femaleBazi['month']['gan'], $femaleBazi['month']['zhi'],
            $femaleBazi['day']['gan'], $femaleBazi['day']['zhi'],
            $femaleBazi['hour']['gan'], $femaleBazi['hour']['zhi']
        );

        return <<<PROMPT
你是一位资深的八字命理大师，拥有30年的命理研究经验。请对以下八字合婚进行专业分析。

【基本信息】
男方：{$maleName}
八字：{$maleBaziStr}
日主：{$maleBazi['day']['gan']}（{$maleBazi['day_master_wuxing']}）

女方：{$femaleName}
八字：{$femaleBaziStr}
日主：{$femaleBazi['day']['gan']}（{$femaleBazi['day_master_wuxing']}）

【系统评分】
合婚总分：{$score}/100
合婚等级：{$level}

五维度评分：
- 生肖配对：{$hehunResult['scores']['year']}/15
- 日主配合：{$hehunResult['scores']['day']}/30
- 五行互补：{$hehunResult['scores']['wuxing']}/20
- 干支合冲：{$hehunResult['scores']['hechong']}/20
- 纳音五行：{$hehunResult['scores']['nayin']}/15

请从以下方面进行详细分析，用专业但通俗的语言：

1. 【性格契合度分析】
分析双方性格特点、相处模式、潜在冲突点

2. 【婚姻前景预测】
根据八字配合情况，预测婚姻稳定性、可能面临的挑战

3. 【事业财运配合】
分析双方事业发展的互补性、财运配合度

4. 【子女缘分析】
根据八字推测子女缘分、建议的生育时机

5. 【专业建议】
针对八字中的冲克，给出具体的化解建议（风水、择日、相处之道等）

6. 【总结】
用200字左右总结这段姻缘的整体评价

请用JSON格式返回，字段如下：
{
  "summary": "整体评价（200字）",
  "personality_match": {
    "male_personality": "男方性格特点",
    "female_personality": "女方性格特点",
    "match_analysis": "性格匹配分析"
  },
  "marriage_prospect": "婚姻前景分析",
  "career_wealth": "事业财运配合分析",
  "children_fate": "子女缘分析",
  "suggestions": ["建议1", "建议2", "建议3"],
  "auspicious_info": {
    "best_years": ["适合结婚的年份"],
    "auspicious_months": ["适合结婚的月份"],
    "notes": "择日注意事项"
  }
}
PROMPT;
    }

    /**
     * 构建八字解读提示词
     */
    protected function buildBaziPrompt(array $bazi, string $gender): string
    {
        $genderText = $gender === 'male' ? '男' : '女';
        $baziStr = sprintf(
            "%s%s %s%s %s%s %s%s",
            $bazi['year']['gan'], $bazi['year']['zhi'],
            $bazi['month']['gan'], $bazi['month']['zhi'],
            $bazi['day']['gan'], $bazi['day']['zhi'],
            $bazi['hour']['gan'], $bazi['hour']['zhi']
        );

        return <<<PROMPT
你是一位资深的八字命理大师。请对以下八字进行专业解读。

【八字信息】
性别：{$genderText}
八字：{$baziStr}
日主：{$bazi['day']['gan']}（{$bazi['day_master_wuxing']}）

请从以下方面进行分析：
1. 日主特性与性格分析
2. 事业发展方向建议
3. 财运分析
4. 感情婚姻分析
5. 健康注意事项
6. 近期运势提醒

请用通俗易懂的语言，给出具体实用的建议。
PROMPT;
    }

    /**
     * 调用AI API
     */
    protected function callAiApi(string $prompt): ?string
    {
        $config = $this->config[$this->defaultProvider] ?? null;

        if (!$config || empty($config['api_key'])) {
            $this->logProviderConfigMissing();
            return null;
        }

        return match ($this->defaultProvider) {
            self::PROVIDER_DEEPSEEK => $this->sendChatCompletionRequest(
                self::PROVIDER_DEEPSEEK,
                rtrim((string) $config['base_url'], '/') . '/chat/completions',
                [
                    'Authorization' => 'Bearer ' . $config['api_key'],
                    'Content-Type' => 'application/json',
                ],
                $config,
                $prompt
            ),
            self::PROVIDER_OPENAI => $this->sendChatCompletionRequest(
                self::PROVIDER_OPENAI,
                rtrim((string) $config['base_url'], '/') . '/v1/chat/completions',
                [
                    'Authorization' => 'Bearer ' . $config['api_key'],
                    'Content-Type' => 'application/json',
                ],
                $config,
                $prompt
            ),
            self::PROVIDER_CLAUDE => $this->sendClaudeMessageRequest($config, $prompt),
            default => $this->logUnsupportedProvider(),
        };
    }

    /**
     * 统一请求 OpenAI / DeepSeek 兼容的 chat completions 接口
     */
    protected function sendChatCompletionRequest(
        string $provider,
        string $endpoint,
        array $headers,
        array $config,
        string $prompt
    ): ?string {
        return $this->sendRequest(
            $provider,
            $endpoint,
            $headers,
            [
                'model' => $config['model'],
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => self::SYSTEM_PROMPT,
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'max_tokens' => $config['max_tokens'],
                'temperature' => $config['temperature'],
            ],
            function (array $data): ?string {
                return $data['choices'][0]['message']['content'] ?? null;
            }
        );
    }

    /**
     * 调用 Claude messages 接口
     */
    protected function sendClaudeMessageRequest(array $config, string $prompt): ?string
    {
        return $this->sendRequest(
            self::PROVIDER_CLAUDE,
            rtrim((string) $config['base_url'], '/') . '/v1/messages',
            [
                'x-api-key' => $config['api_key'],
                'Content-Type' => 'application/json',
                'anthropic-version' => '2023-06-01',
            ],
            [
                'model' => $config['model'],
                'max_tokens' => $config['max_tokens'],
                'temperature' => $config['temperature'],
                'system' => self::SYSTEM_PROMPT,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
            ],
            function (array $data): ?string {
                return $data['content'][0]['text'] ?? null;
            }
        );
    }

    /**
     * 统一发送第三方请求
     */
    protected function sendRequest(
        string $provider,
        string $endpoint,
        array $headers,
        array $payload,
        callable $extractor
    ): ?string {
        try {
            $response = Http::withHeaders($headers)->post($endpoint, $payload);

            if (!$response->ok()) {
                $this->logProviderFailure($provider, $response);
                return null;
            }

            $data = $response->json();
            $content = $extractor(is_array($data) ? $data : []);
            if (!is_string($content) || trim($content) === '') {
                Log::warning('AI 提供商返回空内容', [
                    'provider' => $provider,
                    'status' => method_exists($response, 'getCode') ? $response->getCode() : null,
                ]);
                return null;
            }

            return $content;
        } catch (\Throwable $e) {
            $this->logProviderException($provider, $e);
            return null;
        }
    }

    /**
     * 解析合婚AI响应
     */
    protected function parseHehunResponse(string $response): array
    {
        // 尝试提取JSON
        if (preg_match('/\{[\s\S]*\}/', $response, $matches)) {
            $jsonStr = $matches[0];
            $data = json_decode($jsonStr, true);

            if ($data) {
                return [
                    'summary' => $data['summary'] ?? '',
                    'personality_match' => $data['personality_match'] ?? [],
                    'marriage_prospect' => $data['marriage_prospect'] ?? '',
                    'career_wealth' => $data['career_wealth'] ?? '',
                    'children_fate' => $data['children_fate'] ?? '',
                    'suggestions' => $data['suggestions'] ?? [],
                    'auspicious_info' => $data['auspicious_info'] ?? [],
                    'raw_response' => $response,
                    'is_ai_generated' => true,
                ];
            }
        }

        // 如果无法解析JSON，返回原始文本
        return [
            'summary' => $response,
            'raw_response' => $response,
            'is_ai_generated' => true,
            'parse_error' => true,
        ];
    }

    /**
     * 解析八字解读响应
     */
    protected function parseBaziResponse(string $response): array
    {
        return [
            'analysis' => $response,
            'raw_response' => $response,
            'is_ai_generated' => true,
        ];
    }

    /**
     * 记录业务分析失败
     */
    protected function logAnalysisFailure(string $scene, \Throwable $e): void
    {
        Log::error('AI 业务分析失败', [
            'scene' => $scene,
            'provider' => $this->defaultProvider,
            'exception' => get_class($e),
            'message' => $this->truncateLogValue($e->getMessage()),
        ]);
    }

    /**
     * 记录配置缺失
     */
    protected function logProviderConfigMissing(): void
    {
        Log::error('AI 配置缺失或 API Key 为空', [
            'provider' => $this->defaultProvider,
        ]);
    }

    /**
     * 记录未支持的提供商
     */
    protected function logUnsupportedProvider(): ?string
    {
        Log::warning('AI 提供商未支持', [
            'provider' => $this->defaultProvider,
        ]);

        return null;
    }

    /**
     * 记录第三方接口返回失败
     */
    protected function logProviderFailure(string $provider, $response): void
    {
        $body = (string) $response->body();
        $data = $response->json();
        $parsed = is_array($data) ? $data : [];

        Log::error('AI 提供商调用失败', [
            'provider' => $provider,
            'status' => method_exists($response, 'getCode') ? $response->getCode() : null,
            'error_code' => $this->extractProviderErrorCode($parsed),
            'error_message' => $this->extractProviderErrorMessage($parsed) ?: '第三方接口返回异常',
            'response_hash' => $body !== '' ? substr(sha1($body), 0, 16) : null,
        ]);
    }

    /**
     * 记录第三方接口异常
     */
    protected function logProviderException(string $provider, \Throwable $e): void
    {
        Log::error('AI 提供商请求异常', [
            'provider' => $provider,
            'exception' => get_class($e),
            'message' => $this->truncateLogValue($e->getMessage()),
        ]);
    }

    /**
     * 提取第三方错误消息
     */
    protected function extractProviderErrorMessage(array $data): string
    {
        $candidates = [
            $data['error']['message'] ?? null,
            $data['message'] ?? null,
            $data['msg'] ?? null,
            is_string($data['error'] ?? null) ? $data['error'] : null,
        ];

        foreach ($candidates as $candidate) {
            if (is_scalar($candidate) && trim((string) $candidate) !== '') {
                return $this->truncateLogValue((string) $candidate);
            }
        }

        return '';
    }

    /**
     * 提取第三方错误码
     */
    protected function extractProviderErrorCode(array $data): string
    {
        $candidates = [
            $data['error']['code'] ?? null,
            $data['code'] ?? null,
            $data['error_code'] ?? null,
            $data['type'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_scalar($candidate) && trim((string) $candidate) !== '') {
                return $this->truncateLogValue((string) $candidate, 80);
            }
        }

        return '';
    }

    /**
     * 裁剪日志文本，避免落过长原文
     */
    protected function truncateLogValue(string $value, int $limit = 200): string
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            return '';
        }

        return mb_strlen($trimmed) > $limit
            ? mb_substr($trimmed, 0, $limit) . '...'
            : $trimmed;
    }

    /**
     * 检查AI服务是否可用
     */
    public function isAvailable(): bool
    {
        $config = $this->config[$this->defaultProvider] ?? null;
        return $config && !empty($config['api_key']);
    }

    /**
     * 获取当前提供商
     */
    public function getProvider(): string
    {
        return $this->defaultProvider;
    }
}
