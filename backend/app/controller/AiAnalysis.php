<?php
declare (strict_types = 1);

namespace app\controller;

use app\BaseController;
use app\model\SystemConfig;
use app\model\AiPrompt;
use app\model\BaziRecord;
use think\Request;
use think\facade\Config;
use think\facade\Log;



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
        // 优先从数据库获取配置
        try {
            $dbConfig = [];
            $keys = ['ai_api_key', 'ai_api_url', 'ai_model', 'ai_enable_thinking', 'ai_enable_streaming'];
            foreach ($keys as $key) {
                $value = SystemConfig::getByKey($key);
                if ($value !== null) {
                    $configKey = str_replace('ai_', '', $key);
                    $dbConfig[$configKey] = $value;
                }
            }
            
            if (!empty($dbConfig)) {
                return array_merge(Config::get('ai', []), $dbConfig);
            }
        } catch (\Exception $e) {
            // 数据库配置获取失败，降级使用文件配置
        }

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

    private function buildAiAnalysisLogContext(array $baziData, string $promptKey, string $customPrompt, array $config, array $extra = []): array
    {
        $context = array_filter([
            'prompt_key' => $promptKey,
            'prompt_key_provided' => $promptKey !== '',
            'has_custom_prompt' => $customPrompt !== '',
            'custom_prompt_length' => $customPrompt !== '' ? mb_strlen($customPrompt) : 0,
            'bazi_sections' => array_values(array_intersect(['year', 'month', 'day', 'hour', 'wuxing_stats'], array_keys($baziData))),
            'bazi_field_count' => count($baziData),
            'day_master' => (string) ($baziData['day_master'] ?? ''),
            'model' => (string) ($config['model'] ?? 'DeepSeek-V3.2'),
            'api_host' => $this->extractAiApiHost((string) ($config['api_url'] ?? '')),
        ], static fn ($value) => !($value === '' || $value === 0 || $value === false || $value === null || $value === []));

        return array_merge($context, $extra);
    }

    private function extractAiApiHost(string $apiUrl): string
    {
        $host = (string) parse_url($apiUrl, PHP_URL_HOST);
        return $host !== '' ? $host : '';
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
     * 获取排盘记录的 AI 分析缓存
     * 前端排盘后可直接读取已缓存的 AI 结果，无需重复调用 AI
     */
    public function getRecord(Request $request)
    {
        $recordId = (int) $request->param('id', 0);
        $user = $request->user;

        if ($recordId <= 0) {
            return $this->error('记录ID不能为空', 400);
        }

        $record = BaziRecord::getByIdWithAuth($recordId, (int) $user['sub']);
        if (!$record) {
            return $this->error('记录不存在或无权访问', 404);
        }

        return $this->success([
            'id' => $recordId,
            'ai_analysis' => $record->getAttr('ai_analysis') ?: null,
            'dayun_scores' => $record->getDayunScoresValue(),
        ], '获取成功');
    }

    /**
     * AI大运评分（排盘时同步调用）
     * 根据八字喜忌，让AI对每步大运评分，返回JSON格式评分
     */
    public function scoreDayun(Request $request)
    {
        $baziData = $request->param('bazi');
        $dayunData = $request->param('dayun', []);
        $recordId = (int) $request->param('record_id', 0);

        if (empty($baziData) || !is_array($baziData)) {
            return $this->error('八字数据不能为空', 400);
        }

        if (empty($dayunData) || !is_array($dayunData)) {
            return $this->error('大运数据不能为空', 400);
        }

        $config = $this->getAiConfig();
        if (empty($config['api_key']) || empty($config['api_url'])) {
            return $this->error('AI服务未配置', 500);
        }

        $systemPrompt = $this->buildDayunScoringPrompt($baziData, $dayunData);
        $userPrompt = '请根据命主的喜忌用神，对以上每步大运进行评分，严格按照要求的JSON格式输出。';

        try {
            $response = $this->callAiApi($systemPrompt, $userPrompt, $config);
            // 从AI回复中提取JSON评分
            $scores = $this->extractDayunScores($response, $dayunData);

            // 回写评分到数据库记录
            if ($recordId > 0 && !empty($scores)) {
                $user = $request->user;
                BaziRecord::saveDayunScores($recordId, (int) $user['sub'], $scores);
            }

            return $this->success(['scores' => $scores], 'AI大运评分成功');
        } catch (\Exception $e) {
            return $this->respondSystemException('ai_score_dayun', $e, 'AI大运评分失败，请稍后重试');
        }
    }

    /**
     * 构建大运评分专用 Prompt
     */
    private function buildDayunScoringPrompt(array $baziData, array $dayunData): string
    {
        $year = $baziData['year'] ?? [];
        $month = $baziData['month'] ?? [];
        $day = $baziData['day'] ?? [];
        $hour = $baziData['hour'] ?? [];
        $dayMaster = $baziData['day_master'] ?? '';
        $dayMasterWuxing = $baziData['day_master_wuxing'] ?? '';
        $wuxingStats = $baziData['wuxing_stats'] ?? [];
        $yongshen = $baziData['yongshen'] ?? '';
        $xishen = $baziData['xishen'] ?? '';

        $prompt = "你是一位专业的八字命理师。请根据命主的八字信息，对每步大运进行综合评分（0-100分）。\n\n";
        $prompt .= "【命主八字】\n";
        $prompt .= "年柱：{$year['gan']}{$year['zhi']}（{$year['nayin']}）\n";
        $prompt .= "月柱：{$month['gan']}{$month['zhi']}（{$month['nayin']}）\n";
        $prompt .= "日柱：{$day['gan']}{$day['zhi']}（{$day['nayin']}）- 日主：{$dayMaster}（{$dayMasterWuxing}）\n";
        $prompt .= "时柱：{$hour['gan']}{$hour['zhi']}（{$hour['nayin']}）\n\n";

        $prompt .= "【五行分布】\n";
        foreach ($wuxingStats as $wx => $count) {
            $prompt .= "{$wx}：{$count}个  ";
        }
        $prompt .= "\n";

        if ($yongshen) {
            $prompt .= "喜用神：{$yongshen}";
            if ($xishen) $prompt .= "、{$xishen}";
            $prompt .= "\n";
        }

        $prompt .= "\n【大运列表】\n";
        foreach ($dayunData as $i => $yun) {
            $gan = $yun['gan'] ?? '';
            $zhi = $yun['zhi'] ?? '';
            $ageStart = $yun['age_start'] ?? $yun['start_age'] ?? '';
            $ageEnd = $yun['age_end'] ?? $yun['end_age'] ?? '';
            $shishen = $yun['shishen'] ?? '';
            $nayin = $yun['nayin'] ?? '';
            $prompt .= "第" . ($i + 1) . "步（{$ageStart}-{$ageEnd}岁）：{$gan}{$zhi}（{$nayin}），十神：{$shishen}\n";
        }

        $prompt .= "\n【评分要求】\n";
        $prompt .= "请根据每步大运的天干地支与命主喜忌的关系，给出综合评分（0-100分）。\n";
        $prompt .= "评分标准：喜用神旺盛→高分（75-95），中性→中分（50-74），忌神旺盛→低分（20-49）。\n";
        $prompt .= "必须严格按以下JSON格式输出，不要有任何其他文字：\n";
        $prompt .= '{"scores":[{"index":0,"score":85},{"index":1,"score":62},{"index":2,"score":45}]}' . "\n";
        $prompt .= "index从0开始，与大运列表顺序对应，共" . count($dayunData) . "步。";

        return $prompt;
    }

    /**
     * 从AI回复中提取大运评分JSON
     */
    private function extractDayunScores(string $aiResponse, array $dayunData): array
    {
        // 尝试从回复中提取JSON
        if (preg_match('/\{[\s\S]*"scores"[\s\S]*\}/m', $aiResponse, $matches)) {
            $json = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE && isset($json['scores'])) {
                $result = [];
                foreach ($json['scores'] as $item) {
                    $idx = (int)($item['index'] ?? -1);
                    $score = (int)($item['score'] ?? 50);
                    $score = max(20, min(95, $score));
                    if ($idx >= 0 && $idx < count($dayunData)) {
                        $result[$idx] = $score;
                    }
                }
                return $result;
            }
        }

        // 解析失败，返回空数组（前端保留本地评分）
        return [];
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
        $dayunData = $request->param('dayun', []);
        $promptKey = $request->param('prompt_key', '');
        $customPrompt = $request->param('prompt', '');
        $recordId = (int) $request->param('record_id', 0);
        
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
            // 使用默认构建的提示词（含大运数据）
            $systemPrompt = $this->buildBaziSystemPrompt($baziData, is_array($dayunData) ? $dayunData : []);
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

            // 回写 AI 解盘结果到数据库
            if ($recordId > 0) {
                $user = $request->user;
                BaziRecord::saveAiAnalysis($recordId, (int) $user['sub'], $response);
            }

            return $this->success([
                'analysis' => $response,
                'model' => $config['model'] ?? 'DeepSeek-V3.2',
                'prompt_used' => $prompt ? $prompt->name : 'default'
            ], 'AI解盘成功');
        } catch (\Exception $e) {
            return $this->respondSystemException(
                'ai_bazi_analyze',
                $e,
                'AI解盘失败，请稍后重试',
                $this->buildAiAnalysisLogContext($baziData, $promptKey, $customPrompt, $config, [
                    'mode' => 'sync',
                    'prompt_source' => $prompt ? 'preset' : 'default',
                ])
            );
        }


    }

    /**
     * 流式AI解盘
     * 支持SSE流式返回
     */
    public function analyzeBaziStream(Request $request)
    {
        $baziData = $request->param('bazi');
        $dayunData = $request->param('dayun', []);
        $promptKey = $request->param('prompt_key', '');
        $customPrompt = $request->param('prompt', '');
        $recordId = (int) $request->param('record_id', 0);
        
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
            $systemPrompt = $this->buildBaziSystemPrompt($baziData, is_array($dayunData) ? $dayunData : []);
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

        $fullContent = '';
        try {
            $fullContent = $this->callAiApiStreamCapture($systemPrompt, $userPrompt, $config);
            $this->emitSseDone();

            // 流式完成后回写 AI 分析结果到数据库
            if ($recordId > 0 && $fullContent !== '') {
                try {
                    $user = $request->user;
                    BaziRecord::saveAiAnalysis($recordId, (int) $user['sub'], $fullContent);
                } catch (\Throwable $saveEx) {
                    Log::warning('AI分析结果回写失败', ['record_id' => $recordId, 'error' => $saveEx->getMessage()]);
                }
            }
        } catch (\Exception $e) {
            $this->logControllerException(
                'ai_bazi_stream',
                $e,
                $this->buildAiAnalysisLogContext($baziData, $promptKey, $customPrompt, $config, [
                    'mode' => 'stream',
                    'prompt_source' => $prompt ? 'preset' : 'default',
                ]),
                'error'
            );
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
    private function buildBaziSystemPrompt(array $baziData, array $dayunData = []): string
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
        
        // 加入大运数据（本地算法排出，稳定可靠）
        if (!empty($dayunData)) {
            $prompt .= "【大运排盘（本地算法计算，共" . count($dayunData) . "步大运）】\n";
            foreach ($dayunData as $yun) {
                $gan = $yun['gan'] ?? '';
                $zhi = $yun['zhi'] ?? '';
                $ageStart = $yun['age_start'] ?? $yun['start_age'] ?? '';
                $ageEnd = $yun['age_end'] ?? $yun['end_age'] ?? '';
                $shishen = $yun['shishen'] ?? '';
                $score = $yun['score'] ?? '';
                $trend = $yun['trend'] ?? '';
                $nayin = $yun['nayin'] ?? '';
                $scoreStr = $score !== '' ? "，运势评分{$score}分" : '';
                $prompt .= "{$ageStart}-{$ageEnd}岁：{$gan}{$zhi}（{$nayin}），十神{$shishen}，{$trend}运{$scoreStr}\n";
            }
            $prompt .= "\n请在解读中结合以上大运数据，对每步大运给出具体的人生阶段建议。\n\n";
        }
        
        $prompt .= "请从以下几个方面进行详细解读：\n";
        $prompt .= "1. 日主特性和性格分析\n";
        $prompt .= "2. 五行旺衰与喜用神分析\n";
        $prompt .= "3. 事业财运分析\n";
        $prompt .= "4. 感情婚姻分析\n";
        $prompt .= "5. 健康状况分析\n";
        $prompt .= "6. 大运走势与人生阶段建议\n\n";
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
            'stream' => false
        ];

        // 仅在明确启用thinking时添加extra_body
        if (!empty($config['enable_thinking'])) {
             $payload['extra_body'] = [
                'enable_thinking' => true,
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
            ];
        }

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
            'stream' => true
        ];
        
        // 仅在明确启用thinking时添加extra_body
        if (!empty($config['enable_thinking'])) {
             $payload['extra_body'] = [
                'enable_thinking' => true,
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
            ];
        }

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
     * 调用AI API（流式输出 + 捕获完整内容）
     * 在向客户端流式输出的同时，捕获完整内容用于数据库缓存
     */
    private function callAiApiStreamCapture(string $systemPrompt, string $userPrompt, array $config): string
    {
        $apiKey = $config['api_key'];
        $apiUrl = $config['api_url'];
        $model = $config['model'] ?? 'DeepSeek-V3.2';

        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'stream' => true,
        ];

        if (!empty($config['enable_thinking'])) {
            $payload['extra_body'] = [
                'enable_thinking' => true,
                'provider' => ['only' => [], 'order' => [], 'sort' => null, 'input_price_range' => [], 'output_price_range' => [], 'input_length_range' => [], 'output_length_range' => [], 'throughput_range' => [], 'latency_range' => []],
            ];
        }

        $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($payloadJson === false) {
            throw new \Exception('流式请求参数编码失败');
        }

        ignore_user_abort(false);
        @set_time_limit(0);

        $capturedContent = '';
        $buffer = '';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_WRITEFUNCTION, function ($ch, $data) use (&$capturedContent, &$buffer) {
            if (connection_aborted()) {
                return 0;
            }

            // 向客户端输出
            echo $data;
            if (function_exists('ob_flush')) {
                @ob_flush();
            }
            flush();

            // 同时解析 SSE 内容，捕获文本
            $buffer .= $data;
            $lines = explode("\n", $buffer);
            // 保留最后一行（可能不完整）
            $buffer = array_pop($lines);
            foreach ($lines as $line) {
                $line = trim($line);
                if (!str_starts_with($line, 'data: ')) {
                    continue;
                }
                $jsonStr = substr($line, 6);
                if ($jsonStr === '[DONE]') {
                    continue;
                }
                $parsed = json_decode($jsonStr, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $content = $parsed['choices'][0]['delta']['content'] ?? '';
                    if ($content !== '') {
                        $capturedContent .= $content;
                    }
                }
            }

            return strlen($data);
        });

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (connection_aborted()) {
            curl_close($ch);
            return $capturedContent;
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

        return $capturedContent;
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

        // 移除API Key掩码处理，如果提交的是掩码，则不更新
        if (isset($config['api_key']) && strpos($config['api_key'], '****') !== false) {
            unset($config['api_key']);
        }

        try {
            // 允许本地地址，但在保存前进行格式检查
            $config['api_url'] = $this->normalizeExternalApiUrl((string) $config['api_url'], true);
        } catch (\InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 400);
        }

        // 保存到数据库
        $saveData = [];
        if (isset($config['api_url'])) $saveData['ai_api_url'] = $config['api_url'];
        if (isset($config['api_key'])) $saveData['ai_api_key'] = $config['api_key']; // 注意：最好加密存储
        if (isset($config['model'])) $saveData['ai_model'] = $config['model'];
        if (isset($config['enable_thinking'])) $saveData['ai_enable_thinking'] = $config['enable_thinking'];
        
        foreach ($saveData as $key => $value) {
            // 尝试更新现有配置，如果不存在则创建
            $existing = SystemConfig::where('config_key', $key)->find();
            if ($existing) {
                $existing->config_value = (string)$value;
                $existing->save();
            } else {
                SystemConfig::create([
                    'config_key' => $key,
                    'config_value' => (string)$value,
                    'config_type' => 'string',
                    'category' => 'ai',
                    'description' => 'AI Configuration ' . $key,
                    'is_editable' => 1
                ]);
            }
        }
        
        // 重新获取完整配置返回
        $newConfig = $this->getAiConfig();
        
        $responseConfig = $newConfig;
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
    private function normalizeExternalApiUrl(string $apiUrl, bool $allowLocal = false): string
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

        if (!$allowLocal && $this->isForbiddenAiHost($host)) {
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

