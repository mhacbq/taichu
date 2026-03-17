<?php

declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\PointsRecord;
use app\model\User;
use app\service\BaziCalculationService;
use app\service\ConfigService;
use app\service\DeepSeekService;
use app\service\LiuyaoService;
use app\service\SchemaInspector;
use think\Request;
use think\facade\Db;
use think\facade\Log;


/**
 * 六爻占卜控制器
 */
class Liuyao extends BaseController
{
    protected $middleware = [\app\middleware\Auth::class];

    private const LIUYAO_POINTS_COST = 15;
    private const VALID_GAN = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];
    private const VALID_ZHI = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];

    private static ?string $liuyaoRecordTable = null;

    /**
     * 获取六爻占卜定价
     */
    public function getPricing()
    {
        try {
            $userModel = $this->getCurrentUserModel();
            return $this->success($this->resolvePricing($userModel));
        } catch (\Throwable $e) {
            return $this->respondSystemException('获取六爻定价', $e, '获取定价失败，请稍后重试');
        }
    }

    /**
     * 前端占卜兼容接口
     */
    public function divination()
    {
        try {
            $data = $this->request->post();
            $question = trim((string) ($data['question'] ?? ''));
            if ($question === '') {
                return $this->error('请输入占卜问题', 400);
            }

            $data['question'] = $question;
            $userModel = $this->getCurrentUserModel();
            $pricing = $this->resolvePricing($userModel);
            $pointsCost = ($pricing['is_first_free'] || $pricing['is_vip_free']) ? 0 : (int) $pricing['cost'];

            if ($pointsCost > 0 && (int) ($userModel->points ?? 0) < $pointsCost) {
                return $this->error('积分不足，请先充值', 403);
            }

            $data['user_id'] = (int) $userModel->id;
            $coreResult = $this->buildDivinationCore($data);
            $interpretation = $this->buildInterpretation($coreResult);
            $aiContent = null;

            if (!empty($data['useAi'])) {
                try {
                    $aiContent = DeepSeekService::interpretLiuyao($this->buildAiPayload($coreResult));
                } catch (\Throwable $aiError) {
                    Log::warning('六爻 AI 解读降级', [
                        'user_id' => (int) $userModel->id,
                        'message' => $aiError->getMessage(),
                    ]);
                }
            }

            if ($pointsCost > 0) {
                $deducted = $userModel->deductPoints($pointsCost);
                if (!$deducted) {
                    return $this->error('积分不足，请先充值', 403);
                }

                try {
                    PointsRecord::record((int) $userModel->id, '六爻占卜', -$pointsCost, 'liuyao', 0, '六爻占卜消耗');
                } catch (\Throwable $pointsError) {
                    Log::warning('六爻积分流水写入失败', [
                        'user_id' => (int) $userModel->id,
                        'message' => $pointsError->getMessage(),
                    ]);
                }
            }

            $recordId = null;
            try {
                $recordId = $this->storeDivinationRecord($data, $coreResult, $interpretation, $aiContent, $pointsCost);
            } catch (\Throwable $recordError) {
                Log::warning('六爻占卜记录保存失败', [
                    'user_id' => (int) $userModel->id,
                    'message' => $recordError->getMessage(),
                ]);
            }

            return $this->success($this->buildFrontendDivinationResult(
                $recordId,
                $coreResult,
                $interpretation,
                $aiContent,
                $pointsCost,
                (int) ($userModel->points ?? 0),
                $pricing['is_first_free']
            ));
        } catch (\Throwable $e) {
            return $this->respondSystemException('执行六爻占卜', $e, '占卜失败，请稍后重试');
        }
    }

    /**
     * 获取当前用户的六爻历史
     */
    public function history()
    {
        try {
            $userModel = $this->getCurrentUserModel();
            $pagination = $this->getPaginationParams('page', 'page_size', 20, 100);
            $query = $this->applyRecordActiveFilter(
                Db::table($this->resolveLiuyaoRecordTable())
            )->where('user_id', (int) $userModel->id);

            $total = (clone $query)->count();

            $records = (clone $query)
                ->order('created_at', 'desc')
                ->page($pagination['page'], $pagination['pageSize'])
                ->select()
                ->toArray();

            $list = array_map(fn(array $record) => $this->formatRecordForFrontend($record), $records);

            return $this->success([
                'total' => $total,
                'page' => $pagination['page'],
                'page_size' => $pagination['pageSize'],
                'list' => $list,
            ]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('获取六爻历史', $e, '获取记录失败，请稍后重试');
        }
    }

    /**
     * 获取单条六爻历史详情
     */
    public function detail()
    {
        try {
            $record = $this->findOwnedRecord((int) $this->request->get('id', 0));
            return $this->success($this->formatRecordForFrontend($record));
        } catch (\InvalidArgumentException $e) {
            return $this->respondBusinessException($e, '获取六爻详情', '记录不存在', 404);
        } catch (\Throwable $e) {
            return $this->respondSystemException('获取六爻详情', $e, '获取记录详情失败，请稍后重试');
        }
    }

    /**
     * 删除六爻记录
     */
    public function delete()
    {
        try {
            $record = $this->findOwnedRecord((int) $this->request->post('id', 0));
            $table = $this->resolveLiuyaoRecordTable();

            if ($table === 'tc_liuyao_record') {
                Db::table($table)
                    ->where('id', (int) $record['id'])
                    ->update([
                        'status' => 0,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
            } else {
                Db::table($table)
                    ->where('id', (int) $record['id'])
                    ->delete();
            }

            return $this->success(null, '删除成功');

        } catch (\InvalidArgumentException $e) {
            return $this->respondBusinessException($e, '删除六爻记录', '记录不存在', 404);
        } catch (\Throwable $e) {
            return $this->respondSystemException('删除六爻记录', $e, '删除失败，请稍后重试');
        }
    }

    /**
     * 起卦 - 支持多种起卦方式
     */
    public function qiGua()
    {
        $data = $this->request->post();
        $method = trim((string) ($data['method'] ?? 'time'));

        try {
            $result = $this->buildDivinationCore($data);

            if (!empty($data['user_id'])) {
                $this->saveRecord($data, $result);
            }

            return $this->success($result);
        } catch (\InvalidArgumentException $e) {
            return $this->respondBusinessException($e, '执行六爻起卦', '起卦参数无效', 400, [
                'method' => $method,
                'user_id' => (int) ($data['user_id'] ?? 0),
            ]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('执行六爻起卦', $e, '起卦失败，请稍后重试', [
                'method' => $method,
                'user_id' => (int) ($data['user_id'] ?? 0),
            ]);
        }
    }
    
    /**
     * 时间起卦
     */
    private function qiGuaByTime(): array
    {
        $now = new \DateTimeImmutable('now', new \DateTimeZone('Asia/Shanghai'));
        return LiuyaoService::qiGuaByTime(
            (int)$now->format('Y'),
            (int)$now->format('n'),
            (int)$now->format('j'),
            (int)$now->format('G')
        );
    }
    
    /**
     * 数字起卦
     */
    private function qiGuaByNumber(array $data): array
    {
        if (empty($data['numbers'])) {
            throw new \Exception('请提供数字');
        }
        
        $numbers = $data['numbers'];
        if (is_string($numbers)) {
            $numbers = array_map('intval', explode(',', $numbers));
        }
        
        return LiuyaoService::qiGuaByNumber($numbers[0] ?? 1, $numbers[1] ?? null);
    }
    
    /**
     * 手动摇卦
     */
    private function qiGuaByManual(array $data): array
    {
        if (empty($data['yao_results']) || count($data['yao_results']) != 6) {
            throw new \Exception('请提供6次摇卦结果');
        }
        
        return LiuyaoService::qiGuaByManual($data['yao_results']);
    }
    
    /**
     * 获取卦辞信息
     */
    private function getGuaInfo(string $mainGua, string $bianGua): array
    {
        // 验证卦名格式，只允许中文字符
        $pattern = '/^[\x{4e00}-\x{9fa5}]+$/u';
        $mainInfo = null;
        $bianInfo = null;

        if ($mainGua !== '' && preg_match($pattern, $mainGua)) {
            $mainInfo = \think\facade\Db::table('gua_data')
                ->where('gua_name', $mainGua)
                ->find();
        }

        if ($bianGua !== '' && preg_match($pattern, $bianGua)) {
            $bianInfo = \think\facade\Db::table('gua_data')
                ->where('gua_name', $bianGua)
                ->find();
        }
        
        return [
            'main' => $mainInfo,
            'bian' => $bianInfo,
        ];
    }

    

    /**
     * 获取AI解读
     */
    public function aiInterpretation()
    {
        try {
            $data = $this->request->post();
            
            if (empty($data['yao_code']) || empty($data['question'])) {
                return $this->error('参数错误', 400);
            }
            
            // 构建提示词
            $prompt = $this->buildAiPrompt($data);
            
            // 调用DeepSeek API
            $interpretation = DeepSeekService::chat($prompt);
            
            // 更新记录
            if (!empty($data['record_id'])) {
                $table = $this->resolveLiuyaoRecordTable();
                $updateData = $table === 'tc_liuyao_record'
                    ? [
                        'ai_interpretation' => $interpretation,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]
                    : [
                        'ai_analysis' => $interpretation,
                        'is_ai_analysis' => 1,
                    ];

                \think\facade\Db::table($table)
                    ->where('id', $data['record_id'])
                    ->update($updateData);
            }

            
            return $this->success(['interpretation' => $interpretation]);
        } catch (\Exception $e) {
            // 记录详细错误日志，但返回通用错误消息
            \think\facade\Log::error('六爻AI解读失败: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('AI解读失败，请稍后重试', 500);
        }
    }
    
    /**
     * 构建AI提示词
     */
    private function buildAiPrompt(array $data): string
    {
        $mainGua = $data['main_gua'] ?? '';
        $bianGua = $data['bian_gua'] ?? '';
        $yaoCode = $data['yao_code'] ?? '';
        $question = $data['question'] ?? '';
        $yongShen = $data['yong_shen'] ?? '';
        
        return <<<PROMPT
你是一位专业的六爻占卜大师，请根据以下卦象为求测者进行详细解读：

【所问事项】
{$question}

【本卦】
{$mainGua}

【变卦】
{$bianGua}

【动爻】
{$yaoCode}

【用神】
{$yongShen}

请从以下几个方面进行解读：
1. 卦象总体分析
2. 用神状态分析
3. 动变分析（事情的发展变化）
4. 应期推断（事情发生的时间）
5. 具体建议

请用专业但易懂的语言进行解读。
PROMPT;
    }
    
    /**
     * 获取起卦记录列表
     */
    public function records()
    {
        try {
            $pagination = $this->getPaginationParams('page', 'page_size', 20, 100);
            $page = $pagination['page'];
            $pageSize = $pagination['pageSize'];
            $userId = $this->request->get('user_id');

            $query = $this->applyRecordActiveFilter(
                \think\facade\Db::table($this->resolveLiuyaoRecordTable())
            );


            if ($userId) {
                $query->where('user_id', $userId);
            }

            $total = $query->count();
            $list = $query->page($page, $pageSize)
                ->order('created_at', 'DESC')
                ->select();

            return $this->success([
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
                'list' => $list,
            ]);
        } catch (\Exception $e) {
            Log::error('获取六爻记录失败: ' . $e->getMessage());
            return $this->error('获取记录失败，请稍后重试', 500);
        }
    }

    
    /**
     * 获取记录详情
     */
    public function recordDetail(int $id)
    {
        try {
            $record = $this->applyRecordActiveFilter(
                \think\facade\Db::table($this->resolveLiuyaoRecordTable())
            )
                ->where('id', $id)
                ->find();

            
            if (!$record) {
                return $this->error('记录不存在', 404);
            }

            // 解析JSON字段
            $record['liuqin'] = json_decode($record['liuqin'], true);
            $record['liushen'] = json_decode($record['liushen'], true);

            return $this->success($record);
        } catch (\Exception $e) {
            Log::error('获取六爻记录详情失败: ' . $e->getMessage());
            return $this->error('获取记录详情失败，请稍后重试', 500);
        }
    }

    
    /**
     * 获取六十四卦列表
     */
    public function guaList()
    {
        try {
            $list = \think\facade\Db::table('gua_data')
                ->field('id, gua_name, gua_xuhao, wuxing, general_meaning')
                ->order('gua_xuhao', 'ASC')
                ->select();
            
            return $this->success($list);
        } catch (\Exception $e) {
            Log::error('获取卦象列表失败: ' . $e->getMessage());
            return $this->error('获取卦象列表失败，请稍后重试', 500);
        }
    }

    
    /**
     * 获取卦象详情
     */
    public function guaDetail(string $name)
    {
        try {
            $gua = \think\facade\Db::table('gua_data')
                ->where('gua_name', $name)
                ->find();
            
            if (!$gua) {
                return $this->error('卦象不存在', 404);
            }

            // 获取爻辞
            $yaoCi = \think\facade\Db::table('gua_yao_data')
                ->where('gua_id', $gua['id'])
                ->order('yao_position', 'ASC')
                ->select();

            $gua['yao_ci'] = $yaoCi;

            return $this->success($gua);
        } catch (\Exception $e) {
            Log::error('获取卦象详情失败: ' . $e->getMessage());
            return $this->error('获取卦象详情失败，请稍后重试', 500);
        }
    }

    
    /**
     * 构建六爻核心结果
     */
    private function buildDivinationCore(array $data): array
    {
        $method = $data['method'] ?? 'time';
        $result = match ($method) {
            'time' => $this->qiGuaByTime(),
            'number' => $this->qiGuaByNumber($data),
            'manual' => $this->qiGuaByManual($data),
            default => throw new \InvalidArgumentException('不支持的起卦方式'),
        };

        $result['bian_gua'] = LiuyaoService::getBianGua($result['yao_code']);
        $result['hu_gua'] = LiuyaoService::getHuGua($result['yao_code']);
        $result['gua_info'] = $this->getGuaInfo($result['main_gua'], $result['bian_gua']['bian_name']);

        [$riGan, $riZhi] = $this->resolveRiChen($data);
        $timeInfo = [
            'ri_gan' => $riGan,
            'ri_zhi' => $riZhi,
            'xunkong' => LiuyaoService::calculateXunKong($riGan, $riZhi),
        ];

        $result['question'] = trim((string) ($data['question'] ?? ''));
        $result['time_info'] = $timeInfo;
        $result['liu_shen'] = LiuyaoService::getLiuShen($riGan);

        $guaInfo = LiuyaoService::getGuaInfo($result['yao_code']);
        $result['gong'] = $guaInfo['gong'];
        $result['shi_ying'] = LiuyaoService::getShiYing($result['main_gua'], $result['yao_code']);

        $gongWuxing = LiuyaoService::BA_GUA_WUXING[$guaInfo['gong']] ?? '金';
        $result['liuqin'] = LiuyaoService::getLiuQin($result['main_gua'], $gongWuxing, $result['yao_code']);

        $questionType = $data['question_type'] ?? '其他';
        $gender = $data['gender'] ?? '男';
        $result['question_type'] = $questionType;
        $result['gender'] = $gender;
        $result['yong_shen'] = LiuyaoService::getYongShen(
            $questionType,
            $result['liuqin'],
            $result['shi_ying'],
            $result['yao_code'],
            $gender,
            $timeInfo
        );
        $result['method_label'] = $this->getMethodLabel($method);
        $result['clean_gua_code'] = str_replace(['0', '1', '2', '3'], ['0', '1', '0', '1'], $result['yao_code']);
        $result['yao_result'] = $this->normalizeYaoResult($result['yao_results'] ?? null, $result['yao_code']);
        $result['yao_names'] = array_map(fn(int $item) => $this->getYaoName($item), $result['yao_result']);

        return $result;
    }

    /**
     * 自动补齐日辰
     */
    private function resolveRiChen(array $data): array
    {
        $riGan = trim((string) ($data['ri_gan'] ?? ''));
        $riZhi = trim((string) ($data['ri_zhi'] ?? ''));

        if ($riGan === '' || $riZhi === '') {
            $today = new \DateTimeImmutable('now', new \DateTimeZone('Asia/Shanghai'));
            if ((int) $today->format('G') >= 23) {
                $today = $today->modify('+1 day');
            }

            $pillar = (new BaziCalculationService())->calculateDayPillar(
                (int) $today->format('Y'),
                (int) $today->format('n'),
                (int) $today->format('j')
            );

            if ($riGan === '') {
                $riGan = self::VALID_GAN[(int) $pillar['gan_index']];
            }
            if ($riZhi === '') {
                $riZhi = self::VALID_ZHI[(int) $pillar['zhi_index']];
            }
        }

        if (!in_array($riGan, self::VALID_GAN, true)) {
            throw new \InvalidArgumentException('日辰天干参数无效，必须是甲-癸之一');
        }
        if (!in_array($riZhi, self::VALID_ZHI, true)) {
            throw new \InvalidArgumentException('日辰地支参数无效，必须是子-亥之一');
        }

        return [$riGan, $riZhi];
    }

    /**
     * 解析并统一爻值编码（0 老阴 / 1 少阳 / 2 少阴 / 3 老阳）
     */
    private function normalizeYaoResult(mixed $value, string $fallbackYaoCode = ''): array
    {
        if (is_array($value) && $value !== []) {
            return array_values(array_map(fn(mixed $item) => $this->normalizeYaoCodeValue($item), $value));
        }

        if (is_string($value) && trim($value) !== '') {
            $trimmed = trim($value);
            $decoded = json_decode($trimmed, true);
            if (is_array($decoded)) {
                return array_values(array_map(fn(mixed $item) => $this->normalizeYaoCodeValue($item), $decoded));
            }

            if (str_contains($trimmed, ',')) {
                return array_values(array_map(fn(string $item) => $this->normalizeYaoCodeValue($item), explode(',', $trimmed)));
            }

            if (preg_match('/^[0-3]{6}$/', $trimmed) || preg_match('/^[6-9]{6}$/', $trimmed)) {
                return array_values(array_map(fn(string $item) => $this->normalizeYaoCodeValue($item), str_split($trimmed)));
            }
        }

        if ($fallbackYaoCode !== '' && preg_match('/^[0-3]{6}$/', $fallbackYaoCode)) {
            return array_values(array_map(fn(string $item) => $this->normalizeYaoCodeValue($item), str_split($fallbackYaoCode)));
        }

        return [];
    }

    /**
     * 单个爻值归一化
     */
    private function normalizeYaoCodeValue(mixed $value): int
    {
        $numeric = (int) $value;
        return match ($numeric) {
            6 => 0,
            7 => 1,
            8 => 2,
            9 => 3,
            default => max(0, min(3, $numeric)),
        };
    }

    /**
     * 获取爻名
     */
    private function getYaoName(int $value): string
    {
        return [0 => '老阴', 1 => '少阳', 2 => '少阴', 3 => '老阳'][$value] ?? '未知';
    }

    /**
     * 方法标签
     */
    private function getMethodLabel(string $method): string
    {
        return [
            'time' => '时间起卦',
            'number' => '数字起卦',
            'manual' => '手动摇卦',
        ][$method] ?? '时间起卦';
    }

    /**
     * 当前用户模型
     */
    private function getCurrentUserModel(): User
    {
        $userId = (int) ($this->request->user['sub'] ?? 0);
        $userModel = User::find($userId);
        if (!$userModel) {
            throw new \InvalidArgumentException('用户不存在');
        }

        return $userModel;
    }

    /**
     * 计算六爻前端展示定价
     */
    private function resolvePricing(User $userModel): array
    {
        $recordCount = (int) $this->applyRecordActiveFilter(
            Db::table($this->resolveLiuyaoRecordTable())
        )
            ->where('user_id', (int) $userModel->id)
            ->count();


        $isFirstFree = $recordCount === 0;
        $isVipFree = !$isFirstFree && !empty($userModel->is_vip);
        $basePoints = (int) ConfigService::get('points_cost_liuyao', self::LIUYAO_POINTS_COST);
        $costInfo = ConfigService::calculatePointsCost('liuyao', $basePoints, $isFirstFree);
        $cost = ($isFirstFree || $isVipFree) ? 0 : $costInfo['final'];

        return [
            'cost' => $cost,
            'original_cost' => $cost > 0 ? $costInfo['original'] : 0,
            'discount' => $cost > 0 ? $costInfo['discount'] : 0,
            'reason' => $isFirstFree ? '首次占卜免费' : ($isVipFree ? 'VIP 免费' : ($costInfo['reason'] ?? '')),
            'is_first_free' => $isFirstFree,
            'is_vip_free' => $isVipFree,
            'remaining_points' => (int) ($userModel->points ?? 0),
        ];
    }

    /**
     * 生成前端所需的占卜结果结构
     */
    private function buildFrontendDivinationResult(?int $recordId, array $coreResult, string $interpretation, ?string $aiContent, int $pointsCost, int $remainingPoints, bool $isFirst): array
    {
        $guaCi = $coreResult['gua_info']['main']['gua_ci'] ?? $coreResult['gua_info']['main']['general_meaning'] ?? '';
        $createdAt = date('Y-m-d H:i:s');

        return [
            'id' => $recordId,
            'question' => $coreResult['question'],
            'method' => $coreResult['method'] ?? '',
            'method_label' => $coreResult['method_label'] ?? '时间起卦',
            'time_info' => $coreResult['time_info'],
            'created_at' => $createdAt,
            'gua' => [
                'name' => $coreResult['main_gua'],
                'code' => $coreResult['clean_gua_code'] ?? '',
                'gua_ci' => $guaCi,
            ],
            'yao_result' => $coreResult['yao_result'],
            'yao_names' => $coreResult['yao_names'],
            'interpretation' => $interpretation,
            'ai_analysis' => $aiContent ? ['content' => $aiContent] : null,
            'points_cost' => $pointsCost,
            'remaining_points' => $remainingPoints,
            'is_first' => $isFirst,
            'fushen' => $this->buildFushenDisplay($coreResult),
        ];
    }


    /**
     * 构建简要解读
     */
    private function buildInterpretation(array $coreResult): string
    {
        $segments = [];
        $mainMeaning = trim((string) ($coreResult['gua_info']['main']['general_meaning'] ?? $coreResult['gua_info']['main']['gua_ci'] ?? ''));
        if ($mainMeaning !== '') {
            $segments[] = '本卦提示：' . $mainMeaning;
        }

        $segments[] = '本卦：' . ($coreResult['main_gua'] ?? '') . '，变卦：' . ($coreResult['bian_gua']['bian_name'] ?? '') . '，互卦：' . ($coreResult['hu_gua']['hu_name'] ?? '');

        if (!empty($coreResult['yong_shen']['description'])) {
            $segments[] = '用神分析：' . $coreResult['yong_shen']['description'];
        }

        if (!empty($coreResult['time_info']['xunkong'])) {
            $segments[] = '旬空参考：' . implode('、', (array) $coreResult['time_info']['xunkong']);
        }

        return implode(PHP_EOL . PHP_EOL, array_filter($segments));
    }

    /**
     * AI 提示数据
     */
    private function buildAiPayload(array $coreResult): array
    {
        $dongYao = $coreResult['bian_gua']['dong_yao'] ?? [];
        $yaoInfo = [];
        foreach ($coreResult['yao_result'] as $index => $value) {
            $position = $index + 1;
            $liuQin = $coreResult['liuqin'][$position] ?? '';
            $liuShen = $coreResult['liu_shen'][$position] ?? '';
            $yaoInfo[] = sprintf('第%s爻：%s（%s/%s）', $position, $this->getYaoName((int) $value), $liuQin, $liuShen);
        }

        return [
            'question' => $coreResult['question'] ?? '',
            'main_gua' => $coreResult['main_gua'] ?? '',
            'bian_gua' => $coreResult['bian_gua']['bian_name'] ?? '',
            'hu_gua' => $coreResult['hu_gua']['hu_name'] ?? '',
            'yao_info' => implode(PHP_EOL, $yaoInfo),
            'dong_yao' => empty($dongYao) ? '无动爻' : implode('、', $dongYao),
            'liu_shen' => $coreResult['liu_shen'] ?? [],
            'yong_shen' => $coreResult['yong_shen'] ?? [],
            'ri_chen' => ($coreResult['time_info']['ri_gan'] ?? '') . ($coreResult['time_info']['ri_zhi'] ?? ''),
            'yue_jian' => $coreResult['time_info']['xunkong'] ? '旬空：' . implode('、', (array) $coreResult['time_info']['xunkong']) : '未提供',
        ];
    }

    /**
     * 保存前端兼容结果
     */
    private function storeDivinationRecord(array $data, array $coreResult, string $interpretation, ?string $aiContent, int $pointsCost): int
    {
        $table = $this->resolveLiuyaoRecordTable();
        $createdAt = date('Y-m-d H:i:s');

        if ($table === 'liuyao_records') {
            $recordData = [
                'user_id' => (int) ($data['user_id'] ?? 0),
                'question' => $data['question'] ?? '',
                'yao_result' => $coreResult['yao_code'] ?? json_encode($coreResult['yao_result'], JSON_UNESCAPED_UNICODE),
                'gua_code' => substr((string) ($coreResult['clean_gua_code'] ?? ''), 0, 2),
                'gua_name' => $coreResult['main_gua'] ?? '',
                'gua_ci' => (string) ($coreResult['gua_info']['main']['gua_ci'] ?? $coreResult['gua_info']['main']['general_meaning'] ?? ''),
                'yao_ci' => '',
                'interpretation' => $interpretation,
                'ai_analysis' => $aiContent ?? '',
                'is_ai_analysis' => $aiContent ? 1 : 0,
                'consumed_points' => $pointsCost,
                'created_at' => $createdAt,
            ];
        } else {
            $recordData = [
                'user_id' => (int) ($data['user_id'] ?? 0),
                'question' => $data['question'] ?? '',
                'question_type' => $this->getQuestionTypeCode($data['question_type'] ?? '其他'),
                'method' => $this->getMethodCode($data['method'] ?? 'time'),
                'input_number' => !empty($data['numbers']) ? implode(',', (array) $data['numbers']) : '',
                'yao_result' => json_encode($coreResult['yao_result'], JSON_UNESCAPED_UNICODE),
                'yao_code' => $coreResult['yao_code'],
                'main_gua_name' => $coreResult['main_gua'],
                'main_gua_code' => $coreResult['clean_gua_code'] ?? '',
                'bian_gua_name' => $coreResult['bian_gua']['bian_name'] ?? '',
                'bian_gua_code' => $coreResult['bian_gua']['bian_code'] ?? '',
                'hu_gua_name' => $coreResult['hu_gua']['hu_name'] ?? '',
                'hu_gua_code' => $coreResult['hu_gua']['hu_code'] ?? '',
                'liuqin' => json_encode($coreResult['liuqin'] ?? [], JSON_UNESCAPED_UNICODE),
                'liushen' => json_encode($coreResult['liu_shen'] ?? [], JSON_UNESCAPED_UNICODE),
                'yongshen' => $coreResult['yong_shen']['liuqin'] ?? '',
                'liunian' => $coreResult['time_info']['ri_zhi'] ?? '',
                'yuejian' => implode('、', (array) ($coreResult['time_info']['xunkong'] ?? [])),
                'rigan' => ($coreResult['time_info']['ri_gan'] ?? '') . ($coreResult['time_info']['ri_zhi'] ?? ''),
                'interpretation' => $interpretation,
                'ai_interpretation' => $aiContent ?? '',
                'updated_at' => $createdAt,
                'created_at' => $createdAt,
            ];
        }

        return (int) Db::table($table)->insertGetId($recordData);
    }


    /**
     * 兼容旧 saveRecord 调用
     */
    private function saveRecord(array $data, array $result): void
    {
        $this->storeDivinationRecord(
            $data,
            $result,
            $result['interpretation'] ?? ($result['gua_info']['main']['general_meaning'] ?? ''),
            $result['ai_interpretation'] ?? null,
            0
        );
    }

    /**
     * 前端历史记录格式
     */
    private function formatRecordForFrontend(array $record): array
    {
        $mainGuaName = (string) ($record['main_gua_name'] ?? $record['gua_name'] ?? '');
        $mainGuaCode = (string) ($record['main_gua_code'] ?? $record['gua_code'] ?? '');
        $bianGuaName = (string) ($record['bian_gua_name'] ?? '');
        $yaoResult = $this->normalizeYaoResult($record['yao_result'] ?? '', (string) ($record['yao_code'] ?? ''));
        $guaInfo = $this->getGuaInfo($mainGuaName, $bianGuaName);
        $method = $this->resolveMethodByCode((int) ($record['method'] ?? 1));
        $aiContent = (string) ($record['ai_interpretation'] ?? $record['ai_analysis'] ?? '');

        return [
            'id' => (int) ($record['id'] ?? 0),
            'question' => (string) ($record['question'] ?? ''),
            'method' => $method,
            'method_label' => $this->getMethodLabel($method),
            'yao_result' => $yaoResult,
            'yao_names' => array_map(fn(int $item) => $this->getYaoName($item), $yaoResult),
            'gua_name' => $mainGuaName,
            'gua_code' => $mainGuaCode,
            'gua_ci' => (string) ($record['gua_ci'] ?? $guaInfo['main']['gua_ci'] ?? $guaInfo['main']['general_meaning'] ?? ''),
            'interpretation' => (string) ($record['interpretation'] ?? ''),
            'ai_analysis' => $aiContent !== '' ? ['content' => $aiContent] : null,
            'consumed_points' => (int) ($record['consumed_points'] ?? 0),
            'created_at' => (string) ($record['created_at'] ?? ''),
            'fushen' => null,
        ];
    }


    /**
     * 伏神展示结构
     */
    private function buildFushenDisplay(array $coreResult): ?array
    {
        $yongShen = $coreResult['yong_shen'] ?? [];
        if (empty($yongShen['is_fushen']) || empty($yongShen['fushen_info']['position'])) {
            return null;
        }

        $position = (int) $yongShen['fushen_info']['position'];
        if ($position < 1 || $position > 6) {
            return null;
        }

        $display = array_fill(0, 6, null);
        $display[$position - 1] = [
            'name' => $yongShen['liuqin'] ?? '伏神',
            'ganzhi' => $yongShen['fushen_info']['di_zhi'] ?? '',
        ];

        return $display;
    }

    /**
     * 解析当前环境可用的六爻记录表。
     */
    private function resolveLiuyaoRecordTable(): string
    {
        if (self::$liuyaoRecordTable !== null) {
            return self::$liuyaoRecordTable;
        }

        foreach (['tc_liuyao_record', 'liuyao_records'] as $table) {
            if (SchemaInspector::tableExists($table)) {
                self::$liuyaoRecordTable = $table;
                return $table;
            }
        }

        self::$liuyaoRecordTable = 'tc_liuyao_record';
        return self::$liuyaoRecordTable;
    }

    /**
     * 仅在新表结构下追加 status=1 过滤。
     */
    private function applyRecordActiveFilter($query)
    {
        if ($this->resolveLiuyaoRecordTable() === 'tc_liuyao_record') {
            $query->where('status', 1);
        }

        return $query;
    }

    /**
     * 根据代码反解起卦方式
     */

    private function resolveMethodByCode(int $methodCode): string
    {
        return [1 => 'time', 2 => 'number', 3 => 'manual'][$methodCode] ?? 'time';
    }

    /**
     * 查询当前用户自己的记录
     */
    private function findOwnedRecord(int $id): array
    {
        if ($id <= 0) {
            throw new \InvalidArgumentException('记录不存在');
        }

        $userModel = $this->getCurrentUserModel();
        $record = $this->applyRecordActiveFilter(
            Db::table($this->resolveLiuyaoRecordTable())
        )
            ->where('id', $id)
            ->where('user_id', (int) $userModel->id)
            ->find();

        if (!$record) {
            throw new \InvalidArgumentException('记录不存在');
        }

        return $record;
    }


    /**
     * 转换问事类型为代码
     */
    private function getQuestionTypeCode(string $type): int
    {
        $map = [
            '求财' => 1,
            '感情' => 2,
            '事业' => 3,
            '健康' => 4,
            '学业' => 5,
            '出行' => 6,
            '其他' => 7,
        ];
        return $map[$type] ?? 7;
    }
    
    /**
     * 转换起卦方式为代码
     */
    private function getMethodCode(string $method): int
    {
        $map = [
            'time' => 1,
            'number' => 2,
            'manual' => 3,
        ];
        return $map[$method] ?? 1;
    }
}
