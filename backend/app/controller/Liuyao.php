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
            
            // 六爻统一为 AI 分析版，直接获取定价
            $pricing = $this->resolvePricing($userModel);
            $pointsCost = ($pricing['is_first_free'] || $pricing['is_vip_free']) ? 0 : (int) $pricing['cost'];

            // 积分检查移至 persistDivinationOutcome 方法中的事务内部执行
            // 避免在事务外检查积分导致竞态条件

            $data['user_id'] = (int) $userModel->id;
            $coreResult = $this->buildDivinationCore($data);
            $interpretation = $this->buildInterpretation($coreResult);
            /** @var array|null $aiContent */
            $aiContent = null;

            if (!empty($data['useAi'])) {
                try {
                    // 构建更详细的AI分析载荷，包含更多专业信息
                    $aiPayload = $this->buildEnhancedAiPayload($coreResult, $data);
                    $aiContent = DeepSeekService::interpretLiuyao($aiPayload);

                    // 记录AI分析的使用情况，用于优化积分消耗
                    $this->logAiAnalysisUsage($userModel->id, $aiContent);
                } catch (\Throwable $aiError) {
                    Log::warning('六爻 AI 解读降级', [
                        'user_id' => (int) $userModel->id,
                        'message' => $aiError->getMessage(),
                    ]);

                    // 降级方案：生成基础结构化分析
                    $aiContent = $this->generateFallbackAiAnalysis($coreResult, $data);
                }
            }

            [$recordId, $remainingPoints] = $this->persistDivinationOutcome(
                $userModel,
                $data,
                $coreResult,
                $interpretation,
                $aiContent,
                $pointsCost
            );

            return $this->success($this->buildFrontendDivinationResult(
                $recordId,
                $coreResult,
                $interpretation,
                $aiContent,
                $pointsCost,
                $remainingPoints,
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
            $columns = $this->getLiuyaoRecordColumns();

            if (isset($columns['status'])) {
                $deletePayload = ['status' => 0];
                if (isset($columns['updated_at'])) {
                    $deletePayload['updated_at'] = date('Y-m-d H:i:s');
                }

                Db::table($table)
                    ->where('id', (int) $record['id'])
                    ->update($deletePayload);
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
        $table = $this->resolveGuaDataTable();
        if ($table === '') {
            return [
                'main' => null,
                'bian' => null,
            ];
        }

        // 验证卦名格式，只允许中文字符
        $pattern = '/^[\x{4e00}-\x{9fa5}]+$/u';
        $mainInfo = null;
        $bianInfo = null;

        if ($mainGua !== '' && preg_match($pattern, $mainGua)) {
            $mainInfo = Db::table($table)
                ->where('gua_name', $mainGua)
                ->find();
        }

        if ($bianGua !== '' && preg_match($pattern, $bianGua)) {
            $bianInfo = Db::table($table)
                ->where('gua_name', $bianGua)
                ->find();
        }

        return [
            'main' => $this->normalizeGuaInfoRow($mainInfo),
            'bian' => $this->normalizeGuaInfoRow($bianInfo),
        ];
    }

    private function resolveGuaDataTable(): string
    {
        foreach (['gua_data', 'liuyao_gua'] as $table) {
            if (SchemaInspector::tableExists($table)) {
                return $table;
            }
        }

        return '';
    }

    private function resolveGuaYaoDataTable(): string
    {
        foreach (['gua_yao_data'] as $table) {
            if (SchemaInspector::tableExists($table)) {
                return $table;
            }
        }

        return '';
    }

    private function normalizeGuaInfoRow(?array $row): ?array
    {
        if (!$row) {
            return null;
        }

        if (!isset($row['gua_ci'])) {
            $row['gua_ci'] = '';
        }
        if (empty($row['general_meaning'])) {
            $row['general_meaning'] = (string) ($row['description'] ?? $row['gua_ci_meaning'] ?? $row['gua_ci'] ?? '');
        }

        return $row;
    }


    

    /**
     * AI 深度分析（路由别名，供 POST liuyao/ai-analysis 调用）
     * 内部复用 aiInterpretation 逻辑
     */
    public function aiAnalysis()
    {
        return $this->aiInterpretation();
    }

    /**
     * 获取AI解读（结构化 JSON 版本）
     */
    public function aiInterpretation()
    {
        try {
            $data = $this->request->post();

            if (empty($data['question'])) {
                return $this->error('参数错误', 400);
            }

            // 构建 interpretLiuyao 所需的载荷
            $aiPayload = $this->buildEnhancedAiPayload(
                $this->buildDivinationCoreFromRequest($data),
                $data
            );

            // 调用结构化 AI 解读
            $aiContent = DeepSeekService::interpretLiuyao($aiPayload);

            // 更新记录
            if (!empty($data['record_id'])) {
                $table = $this->resolveLiuyaoRecordTable();
                $columns = $this->getLiuyaoRecordColumns();
                $updateData = [];
                $serialized = json_encode($aiContent, JSON_UNESCAPED_UNICODE);

                if (isset($columns['ai_interpretation'])) {
                    $updateData['ai_interpretation'] = $serialized;
                }
                if (isset($columns['ai_analysis'])) {
                    $updateData['ai_analysis'] = $serialized;
                }
                if (isset($columns['is_ai_analysis'])) {
                    $updateData['is_ai_analysis'] = 1;
                }
                if (isset($columns['updated_at'])) {
                    $updateData['updated_at'] = date('Y-m-d H:i:s');
                }

                if ($updateData !== []) {
                    Db::table($table)
                        ->where('id', $data['record_id'])
                        ->update($updateData);
                }
            }

            return $this->success(['ai_content' => $aiContent]);
        } catch (\Exception $e) {
            return $this->respondSystemException('六爻 AI 解读', $e, 'AI解读失败，请稍后重试', [
                'record_id' => (int) ($data['record_id'] ?? 0),
                'question_length' => mb_strlen((string) ($data['question'] ?? '')),
            ]);
        }
    }

    /**
     * 从请求数据中构建卦象核心数据（供 aiInterpretation 复用）
     */
    private function buildDivinationCoreFromRequest(array $data): array
    {
        return [
            'main_gua'  => $data['main_gua'] ?? '',
            'bian_gua'  => $data['bian_gua'] ?? '',
            'hu_gua'    => $data['hu_gua'] ?? '',
            'yao_info'  => $data['yao_info'] ?? ($data['yao_code'] ?? ''),
            'dong_yao'  => $data['dong_yao'] ?? '',
            'liu_shen'  => $data['liu_shen'] ?? '',
            'yong_shen' => $data['yong_shen'] ?? '',
            'ri_chen'   => $data['ri_chen'] ?? '',
            'yue_jian'  => $data['yue_jian'] ?? '',
            'xun_kong'  => $data['xun_kong'] ?? '',
        ];
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
            return $this->respondSystemException('获取六爻记录列表', $e, '获取记录失败，请稍后重试', [
                'page' => $page,
                'page_size' => $pageSize,
                'user_id' => $userId !== null && $userId !== '' ? (int) $userId : null,
            ]);
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
            return $this->respondSystemException('获取六爻记录详情', $e, '获取记录详情失败，请稍后重试', [
                'record_id' => $id,
            ]);
        }
    }

    
    /**
     * 获取六十四卦列表
     */
    public function guaList()
    {
        try {
            $table = $this->resolveGuaDataTable();
            if ($table === '') {
                return $this->success([]);
            }

            $field = $table === 'gua_data'
                ? 'id, gua_name, gua_xuhao, wuxing, general_meaning, gua_ci'
                : 'id, gua_name, gua_code, wuxing, description, gua_ci';
            $orderField = $table === 'gua_data' ? 'gua_xuhao' : 'id';
            $list = Db::table($table)
                ->field($field)
                ->order($orderField, 'ASC')
                ->select()
                ->toArray();

            $list = array_map(fn(array $item) => $this->normalizeGuaInfoRow($item) ?? $item, $list);

            return $this->success($list);
        } catch (\Exception $e) {
            return $this->respondSystemException('获取六爻卦象列表', $e, '获取卦象列表失败，请稍后重试');
        }
    }


    
    /**
     * 获取卦象详情
     */
    public function guaDetail(string $name)
    {
        try {
            $guaTable = $this->resolveGuaDataTable();
            if ($guaTable === '') {
                return $this->error('卦象基础数据未部署', 503);
            }

            $gua = Db::table($guaTable)
                ->where('gua_name', $name)
                ->find();
            
            if (!$gua) {
                return $this->error('卦象不存在', 404);
            }

            $gua = $this->normalizeGuaInfoRow($gua) ?? $gua;

            // 获取爻辞
            $gua['yao_ci'] = [];
            $yaoTable = $this->resolveGuaYaoDataTable();
            if ($yaoTable !== '' && !empty($gua['id'])) {
                $gua['yao_ci'] = Db::table($yaoTable)
                    ->where('gua_id', $gua['id'])
                    ->order('yao_position', 'ASC')
                    ->select()
                    ->toArray();
            }

            return $this->success($gua);
        } catch (\Exception $e) {
            return $this->respondSystemException('获取六爻卦象详情', $e, '获取卦象详情失败，请稍后重试', [
                'gua_name' => $name,
            ]);
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
        
        // 处理农历数据访问问题
        if (isset($result['lunar_info'])) {
            $lunarInfo = $result['lunar_info'];
            $result['year_zhi_index'] = $lunarInfo['year_num'] ?? 1;
            $result['lunar_month'] = $lunarInfo['month_num'] ?? 1;
            $result['lunar_day'] = $lunarInfo['day_num'] ?? 1;
        }

        $result['bian_gua'] = LiuyaoService::getBianGua($result['yao_code']);
        $result['hu_gua'] = LiuyaoService::getHuGua($result['yao_code']);
        $result['gua_info'] = $this->getGuaInfo($result['main_gua'], $result['bian_gua']['bian_name']);

        $referenceMoment = $this->resolveDivinationMoment();
        [$riGan, $riZhi] = $this->resolveRiChen($data, $referenceMoment);
        $timeInfo = [
            'ri_gan' => $riGan,
            'ri_zhi' => $riZhi,
            'ri_chen' => $riGan . $riZhi,
            'yue_jian' => $this->resolveYueJian($referenceMoment),
            'divination_at' => $referenceMoment->format('Y-m-d H:i:s'),
            'xunkong' => LiuyaoService::calculateXunKong($riGan, $riZhi),
        ];


        $result['question'] = trim((string) ($data['question'] ?? ''));
        $result['time_info'] = $timeInfo;
        $result['liu_shen'] = LiuyaoService::getLiuShen($riGan);
        if (count($result['liu_shen']) !== 6) {
            throw new \InvalidArgumentException('日辰天干解析失败，无法排定六神');
        }

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
     * 统一六爻所使用的起卦时刻。
     * 晚子时按次日处理，以保持与八字日柱口径一致。
     */
    private function resolveDivinationMoment(): \DateTimeImmutable
    {
        $moment = new \DateTimeImmutable('now', new \DateTimeZone('Asia/Shanghai'));
        if ((int) $moment->format('G') >= 23) {
            $moment = $moment->modify('+1 day');
        }

        return $moment;
    }

    /**
     * 自动补齐日辰
     */
    private function resolveRiChen(array $data, \DateTimeImmutable $referenceMoment): array
    {
        $riGan = trim((string) ($data['ri_gan'] ?? ''));
        $riZhi = trim((string) ($data['ri_zhi'] ?? ''));

        if (($riGan === '') xor ($riZhi === '')) {
            throw new \InvalidArgumentException('日辰天干与地支必须同时提供，或同时留空由系统自动推算');
        }

        if ($riGan === '' && $riZhi === '') {
            $pillar = (new BaziCalculationService())->calculateDayPillar(
                (int) $referenceMoment->format('Y'),
                (int) $referenceMoment->format('n'),
                (int) $referenceMoment->format('j')
            );

            $riGan = self::VALID_GAN[(int) $pillar['gan_index']];
            $riZhi = self::VALID_ZHI[(int) $pillar['zhi_index']];
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
     * 推导当前起卦时刻的月建。
     * 以八字统一节气口径取月支，六爻展示时追加“月”字。
     */
    private function resolveYueJian(\DateTimeImmutable $referenceMoment): string
    {
        $bazi = (new BaziCalculationService())->calculateBazi($referenceMoment->format('Y-m-d H:i:s'), '男');
        $monthZhi = trim((string) ($bazi['month']['zhi'] ?? ''));

        return $monthZhi !== '' ? $monthZhi . '月' : '';
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
        
        // 统一走 ConfigService 的 liuyao_ai 配置路径（points_cost_liuyao_ai）
        $costInfo = ConfigService::calculatePointsCost('liuyao_ai');
        $cost = ($isFirstFree || $isVipFree) ? 0 : $costInfo['final'];

        return [
            'cost' => $cost,
            'original_cost' => $cost > 0 ? $costInfo['original'] : 0,
            'discount' => $cost > 0 ? $costInfo['discount'] : 0,
            'reason' => $isFirstFree ? '首次占卜免费' : ($isVipFree ? 'VIP 免费' : ($costInfo['reason'] ?? '')),
            'is_first_free' => $isFirstFree,
            'is_vip_free' => $isVipFree,
            'remaining_points' => (int) ($userModel->points ?? 0),
            'professional_cost' => $costInfo['final'],
        ];
    }

    /**
     * 统一处理六爻结果持久化。
     * 付费场景下，记录落库与积分扣减必须同成同败，避免“已扣费却没有历史记录”。
     *
     * @return array{0:int,1:int}
     */
    private function persistDivinationOutcome(User $userModel, array $data, array $coreResult, string $interpretation, ?array $aiContent, int $pointsCost): array
    {
        if ($pointsCost <= 0) {
            $recordId = $this->storeDivinationRecord($data, $coreResult, $interpretation, $aiContent, $pointsCost);
            if ($recordId <= 0) {
                throw new \RuntimeException('六爻占卜记录保存失败');
            }

            return [$recordId, (int) ($userModel->points ?? 0)];
        }


        Db::startTrans();
        try {
            $recordId = $this->storeDivinationRecord($data, $coreResult, $interpretation, $aiContent, $pointsCost);
            if ($recordId <= 0) {
                throw new \RuntimeException('六爻占卜记录保存失败');
            }

            $lockedUser = Db::name('tc_user')
                ->where('id', (int) $userModel->id)
                ->where('status', 1)
                ->lock(true)
                ->find();

            if (!$lockedUser) {
                throw new \RuntimeException('用户不存在');
            }

            $currentPoints = (int) ($lockedUser['points'] ?? 0);
            if ($currentPoints < $pointsCost) {
                throw new \RuntimeException('积分不足，需要' . $pointsCost . '积分', 403);
            }

            $updateResult = Db::name('tc_user')
                ->where('id', (int) $userModel->id)
                ->dec('points', $pointsCost)
                ->update();

            if ($updateResult === 0) {
                throw new \RuntimeException('积分扣除失败，请重试');
            }

            $remainingPoints = $currentPoints - $pointsCost;
            $pointsPayload = PointsRecord::buildRecordPayload(
                (int) $userModel->id,
                '六爻占卜',
                -$pointsCost,
                'liuyao',
                $recordId,
                '六爻占卜消耗',
                ['balance' => $remainingPoints]
            );
            Db::name('tc_points_record')->insert($pointsPayload);

            Db::commit();
            $userModel->points = $remainingPoints;

            return [$recordId, $remainingPoints];
        } catch (\Throwable $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 生成前端所需的占卜结果结构
     */
    private function buildFrontendDivinationResult(?int $recordId, array $coreResult, string $interpretation, ?array $aiContent, int $pointsCost, int $remainingPoints, bool $isFirst): array
    {
        $guaCi = $coreResult['gua_info']['main']['gua_ci'] ?? $coreResult['gua_info']['main']['general_meaning'] ?? '';
        $createdAt = date('Y-m-d H:i:s');
        $lineDetails = $this->buildLineSnapshots($coreResult);
        $fushenDisplay = $this->buildFushenDisplay($coreResult);

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
            'bian_gua' => [
                'name' => $coreResult['bian_gua']['bian_name'] ?? '',
                'code' => $coreResult['bian_gua']['bian_code'] ?? '',
                'dong_yao' => $coreResult['bian_gua']['dong_yao'] ?? [],
            ],
            'hu_gua' => [
                'name' => $coreResult['hu_gua']['hu_name'] ?? '',
                'code' => $coreResult['hu_gua']['hu_code'] ?? '',
            ],
            'gong' => $coreResult['gong'] ?? '',
            'shi_ying' => $coreResult['shi_ying'] ?? [],
            'liuqin' => $coreResult['liuqin'] ?? [],
            'liushen' => $coreResult['liu_shen'] ?? [],
            'yong_shen' => $coreResult['yong_shen'] ?? [],
            'line_details' => $lineDetails,
            'moving_line_details' => $this->buildMovingLineDetails($lineDetails),
            'yao_result' => $coreResult['yao_result'],
            'yao_names' => $coreResult['yao_names'],
            'interpretation' => $interpretation,
            'ai_analysis' => $aiContent ?: null,
            'points_cost' => $pointsCost,
            'remaining_points' => $remainingPoints,
            'is_first' => $isFirst,
            'fushen' => $fushenDisplay,
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

        if (!empty($coreResult['time_info']['yue_jian'])) {
            $segments[] = '月建参考：' . $coreResult['time_info']['yue_jian'];
        }

        if (!empty($coreResult['time_info']['xunkong'])) {
            $segments[] = '旬空参考：' . implode('、', (array) $coreResult['time_info']['xunkong']);
        }

        return implode(PHP_EOL . PHP_EOL, array_filter($segments));
    }


    /**
     * 构建增强的AI提示词
     */
    private function buildEnhancedAiPayload(array $coreResult, array $data): array
    {
        return [
            'question' => $coreResult['question'] ?? '',
            'main_gua' => $coreResult['main_gua'] ?? '',
            'bian_gua' => $coreResult['bian_gua']['bian_name'] ?? '',
            'hu_gua' => $coreResult['hu_gua']['hu_name'] ?? '',
            'yao_code' => $coreResult['yao_code'] ?? '',
            'dong_yao' => $coreResult['bian_gua']['dong_yao'] ?? [],
            'liuqin' => $coreResult['liuqin'] ?? [],
            'liushen' => $coreResult['liu_shen'] ?? [],
            'yong_shen' => $coreResult['yong_shen'] ?? [],
            'ri_chen' => $coreResult['time_info']['ri_chen'] ?? '',
            'yue_jian' => $coreResult['time_info']['yue_jian'] ?? '',
            'xunkong' => $coreResult['time_info']['xunkong'] ?? [],
            'shi_ying' => $coreResult['shi_ying'] ?? [],
            'question_type' => $data['question_type'] ?? '其他',
            'gender' => $data['gender'] ?? '男',
        ];
    }

    /**
     * 记录AI分析使用情况
     */
    private function logAiAnalysisUsage(int $userId, ?array $aiContent): void
    {
        try {
            Log::info('六爻AI分析使用', [
                'user_id' => $userId,
                'ai_content_length' => $aiContent ? mb_strlen(json_encode($aiContent)) : 0,
                'has_content' => !empty($aiContent),
            ]);
        } catch (\Throwable $e) {
            Log::warning('记录AI分析使用失败', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 生成降级AI分析（结构化数组）
     */
    private function generateFallbackAiAnalysis(array $coreResult, array $data): array
    {
        $mainGua  = $coreResult['main_gua'] ?? '未知';
        $bianGua  = $coreResult['bian_gua']['bian_name'] ?? '未知';
        $huGua    = $coreResult['hu_gua']['hu_name'] ?? '未知';
        $yongShen = $coreResult['yong_shen'] ?? [];
        $riChen   = $coreResult['time_info']['ri_chen'] ?? '未知';
        $yueJian  = $coreResult['time_info']['yue_jian'] ?? '未知';

        $summary = sprintf('本卦为《%s》，变卦为《%s》，互卦为《%s》。', $mainGua, $bianGua, $huGua);

        $yongShenText = '';
        if (!empty($yongShen['liuqin'])) {
            $yongShenText = sprintf('用神为%s', $yongShen['liuqin']);
            if (!empty($yongShen['description'])) {
                $yongShenText .= '，' . $yongShen['description'];
            }
        }

        $movingAnalysis = [];
        if (!empty($coreResult['bian_gua']['dong_yao'])) {
            foreach ($coreResult['bian_gua']['dong_yao'] as $yaoPos) {
                $liuqin = $coreResult['liuqin'][$yaoPos] ?? '';
                $movingAnalysis[] = [
                    'yao'     => "第{$yaoPos}爻",
                    'liuqin'  => $liuqin,
                    'change'  => '',
                    'meaning' => "第{$yaoPos}爻动，事情正在发生变化，请结合用神状态综合判断。",
                ];
            }
        }

        return [
            'summary'            => $summary,
            'gua_analysis'       => sprintf('本卦《%s》变《%s》，互卦为《%s》，日辰%s，月建%s。当前AI服务繁忙，以上为基础卦象信息，建议稍后重试获取深度解读。', $mainGua, $bianGua, $huGua, $riChen, $yueJian),
            'yong_shen_analysis' => $yongShenText,
            'moving_analysis'    => $movingAnalysis,
            'qi_period'          => '',
            'suggestion'         => '当前AI服务暂时繁忙，以上为基础卦象分析，建议稍后重试获取更详细的AI深度解读。',
            'warning'            => '',
        ];
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
            'ri_chen' => $coreResult['time_info']['ri_chen'] ?? (($coreResult['time_info']['ri_gan'] ?? '') . ($coreResult['time_info']['ri_zhi'] ?? '')),
            'yue_jian' => $coreResult['time_info']['yue_jian'] ?? '未提供',
            'xun_kong' => $coreResult['time_info']['xunkong'] ? implode('、', (array) $coreResult['time_info']['xunkong']) : '未提供',
        ];
    }


    /**
     * 保存前端兼容结果
     */
    private function storeDivinationRecord(array $data, array $coreResult, string $interpretation, ?array $aiContent, int $pointsCost): int
    {
        $table = $this->resolveLiuyaoRecordTable();
        $columns = $this->getLiuyaoRecordColumns();
        $createdAt = date('Y-m-d H:i:s');
        $recordData = [];

        if (isset($columns['hexagram_original']) || isset($columns['analysis']) || isset($columns['points_used'])) {
            $recordData = [
                'user_id' => (int) ($data['user_id'] ?? 0),
                'question' => (string) ($data['question'] ?? ''),
                'method' => (string) ($data['method'] ?? 'time'),
                'hexagram_original' => json_encode($this->buildOriginalHexagramSnapshot($coreResult), JSON_UNESCAPED_UNICODE),
                'hexagram_changed' => json_encode([
                    'name' => $coreResult['bian_gua']['bian_name'] ?? '',
                    'code' => $coreResult['bian_gua']['bian_code'] ?? '',
                ], JSON_UNESCAPED_UNICODE),
                'hexagram_mutual' => json_encode([
                    'name' => $coreResult['hu_gua']['hu_name'] ?? '',
                    'code' => $coreResult['hu_gua']['hu_code'] ?? '',
                ], JSON_UNESCAPED_UNICODE),
                'lines' => json_encode($this->buildLineSnapshots($coreResult), JSON_UNESCAPED_UNICODE),
                'moving_lines' => json_encode($coreResult['bian_gua']['dong_yao'] ?? [], JSON_UNESCAPED_UNICODE),
                'analysis' => $interpretation,
                'ai_analysis' => $aiContent ? json_encode($aiContent, JSON_UNESCAPED_UNICODE) : '',
                'points_used' => $pointsCost,
                'is_free' => $pointsCost === 0 ? 1 : 0,
                'is_public' => 0,
                'share_code' => '',
                'client_ip' => (string) $this->request->ip(),
                'created_at' => $createdAt,
            ];
        } elseif (isset($columns['question_type']) || isset($columns['main_gua_name']) || isset($columns['yao_code'])) {
            $recordData = [
                'user_id' => (int) ($data['user_id'] ?? 0),
                'question' => (string) ($data['question'] ?? ''),
                'question_type' => $this->getQuestionTypeCode((string) ($data['question_type'] ?? '其他')),
                'method' => $this->getMethodCode((string) ($data['method'] ?? 'time')),
                'input_number' => !empty($data['numbers']) ? implode(',', (array) $data['numbers']) : '',
                'yao_result' => json_encode($coreResult['yao_result'] ?? [], JSON_UNESCAPED_UNICODE),
                'yao_code' => (string) ($coreResult['yao_code'] ?? ''),
                'main_gua_name' => (string) ($coreResult['main_gua'] ?? ''),
                'main_gua_code' => (string) ($coreResult['clean_gua_code'] ?? ''),
                'bian_gua_name' => (string) ($coreResult['bian_gua']['bian_name'] ?? ''),
                'bian_gua_code' => (string) ($coreResult['bian_gua']['bian_code'] ?? ''),
                'hu_gua_name' => (string) ($coreResult['hu_gua']['hu_name'] ?? ''),
                'hu_gua_code' => (string) ($coreResult['hu_gua']['hu_code'] ?? ''),
                'liuqin' => json_encode($coreResult['liuqin'] ?? [], JSON_UNESCAPED_UNICODE),
                'liushen' => json_encode($coreResult['liu_shen'] ?? [], JSON_UNESCAPED_UNICODE),
                'yongshen' => (string) ($coreResult['yong_shen']['liuqin'] ?? ''),
                'liunian' => (string) ($coreResult['time_info']['ri_zhi'] ?? ''),
                'yuejian' => (string) ($coreResult['time_info']['yue_jian'] ?? ''),
                'rigan' => (string) ($coreResult['time_info']['ri_chen'] ?? (($coreResult['time_info']['ri_gan'] ?? '') . ($coreResult['time_info']['ri_zhi'] ?? ''))),
                'interpretation' => $interpretation,
                'ai_interpretation' => $aiContent ? json_encode($aiContent, JSON_UNESCAPED_UNICODE) : '',
                'updated_at' => $createdAt,
                'created_at' => $createdAt,
            ];
        } else {
            $recordData = [
                'user_id' => (int) ($data['user_id'] ?? 0),
                'question' => (string) ($data['question'] ?? ''),
                'method' => (string) ($data['method'] ?? 'time'),
                'yao_result' => (string) ($coreResult['yao_code'] ?? json_encode($coreResult['yao_result'] ?? [], JSON_UNESCAPED_UNICODE)),
                'gua_code' => $this->buildLegacyGuaCode($coreResult),
                'gua_name' => (string) ($coreResult['main_gua'] ?? ''),
                'gua_ci' => (string) ($coreResult['gua_info']['main']['gua_ci'] ?? $coreResult['gua_info']['main']['general_meaning'] ?? ''),
                'yao_ci' => '',
                'interpretation' => $interpretation,
                'ai_analysis' => $aiContent ? json_encode($aiContent, JSON_UNESCAPED_UNICODE) : '',
                'is_ai_analysis' => $aiContent ? 1 : 0,
                'consumed_points' => $pointsCost,
                'created_at' => $createdAt,
            ];
        }


        $recordData = $this->filterRecordDataByColumns($recordData, $columns);

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
        $originalGua = $this->decodeJsonField($record['hexagram_original'] ?? null);
        $changedGua = $this->decodeJsonField($record['hexagram_changed'] ?? null);
        $mutualGua = $this->decodeJsonField($record['hexagram_mutual'] ?? null);
        $storedLines = $this->decodeJsonField($record['lines'] ?? null);
        if ($storedLines === [] && is_array($originalGua['line_details'] ?? null)) {
            $storedLines = $originalGua['line_details'];
        }

        $mainGuaName = (string) ($record['main_gua_name'] ?? $record['gua_name'] ?? $originalGua['name'] ?? '');
        $mainGuaCode = (string) ($record['main_gua_code'] ?? $record['gua_code'] ?? $originalGua['code'] ?? '');
        $bianGuaName = (string) ($record['bian_gua_name'] ?? $changedGua['name'] ?? '');
        $yaoSeed = $record['yao_result'] ?? ($originalGua['yao_result'] ?? '');
        $legacyYaoCode = is_string($yaoSeed) && preg_match('/^[0-3]{6}$/', trim($yaoSeed)) ? trim($yaoSeed) : '';
        $yaoCode = (string) ($record['yao_code'] ?? $originalGua['yao_code'] ?? $legacyYaoCode);
        $yaoResult = $this->normalizeYaoResult($yaoSeed, $yaoCode);
        if (strlen($mainGuaCode) <= 2 && $yaoCode !== '') {
            $mainGuaCode = strtr($yaoCode, ['0' => '0', '1' => '1', '2' => '0', '3' => '1']);
        }

        $guaInfo = $this->getGuaInfo($mainGuaName, $bianGuaName);
        $method = $this->resolveMethodByCode($record['method'] ?? 1);
        // 从数据库读取时反序列化 JSON 字符串为结构化数组
        $aiRaw = (string) ($record['ai_interpretation'] ?? $record['ai_analysis'] ?? '');
        $aiContent = null;
        if ($aiRaw !== '') {
            $decoded = json_decode($aiRaw, true);
            $aiContent = is_array($decoded) ? $decoded : ['content' => $aiRaw];
        }
        $guaCi = (string) ($record['gua_ci'] ?? $originalGua['gua_ci'] ?? $guaInfo['main']['gua_ci'] ?? $guaInfo['main']['general_meaning'] ?? '');
        $interpretation = (string) ($record['interpretation'] ?? $record['analysis'] ?? '');
        $consumedPoints = (int) ($record['consumed_points'] ?? $record['points_used'] ?? 0);
        $createdAt = (string) ($record['created_at'] ?? '');
        $storedTimeInfo = is_array($originalGua['time_info'] ?? null) ? $originalGua['time_info'] : [];
        if (!empty($record['rigan'])) {
            $storedTimeInfo['ri_chen'] = (string) $record['rigan'];
        }
        if (!empty($record['yuejian'])) {
            $storedTimeInfo['yue_jian'] = (string) $record['yuejian'];
        }
        $recordXunkong = $this->decodeJsonField($record['xunkong'] ?? $record['xun_kong'] ?? null);
        if ($recordXunkong !== []) {
            $storedTimeInfo['xunkong'] = $recordXunkong;
        }
        $timeInfo = $this->hydrateHistoricalTimeInfo($storedTimeInfo, $createdAt);
        $riGan = $timeInfo['ri_gan'];
        $riZhi = $timeInfo['ri_zhi'];

        $liuqinMap = $this->normalizePositionMap($this->decodeJsonField($record['liuqin'] ?? null));
        if ($liuqinMap === []) {
            $liuqinMap = $this->normalizePositionMap(is_array($originalGua['liuqin'] ?? null) ? $originalGua['liuqin'] : []);
        }
        $liushenMap = $this->normalizePositionMap($this->decodeJsonField($record['liushen'] ?? null));
        if ($liushenMap === []) {
            $liushenMap = $this->normalizePositionMap(is_array($originalGua['liushen'] ?? null) ? $originalGua['liushen'] : []);
        }
        $dongYao = array_values(array_map('intval', $this->decodeJsonField($record['moving_lines'] ?? null)));
        if ($dongYao === [] && is_array($originalGua['moving_lines'] ?? null)) {
            $dongYao = array_values(array_map('intval', $originalGua['moving_lines']));
        }

        $gong = (string) ($originalGua['gong'] ?? '');
        $shiYing = is_array($originalGua['shi_ying'] ?? null) ? $originalGua['shi_ying'] : [];
        $bianGuaCode = (string) ($record['bian_gua_code'] ?? $changedGua['code'] ?? '');
        $huGuaName = (string) ($record['hu_gua_name'] ?? $mutualGua['name'] ?? '');
        $huGuaCode = (string) ($record['hu_gua_code'] ?? $mutualGua['code'] ?? '');

        if ($yaoCode !== '') {
            try {
                $bianData = LiuyaoService::getBianGua($yaoCode);
                $huData = LiuyaoService::getHuGua($yaoCode);
                $guaMeta = LiuyaoService::getGuaInfo($yaoCode);
                $computedShiYing = LiuyaoService::getShiYing($mainGuaName, $yaoCode);
                $shiYing = $computedShiYing !== [] ? $computedShiYing : $shiYing;
                $dongYao = $dongYao !== [] ? $dongYao : array_values(array_map('intval', (array) ($bianData['dong_yao'] ?? [])));
                $bianGuaName = $bianGuaName !== '' ? $bianGuaName : (string) ($bianData['bian_name'] ?? '');
                $bianGuaCode = $bianGuaCode !== '' ? $bianGuaCode : (string) ($bianData['bian_code'] ?? '');
                $huGuaName = $huGuaName !== '' ? $huGuaName : (string) ($huData['hu_name'] ?? '');
                $huGuaCode = $huGuaCode !== '' ? $huGuaCode : (string) ($huData['hu_code'] ?? '');
                $gong = $gong !== '' ? $gong : (string) ($guaMeta['gong'] ?? '');
            } catch (\Throwable $exception) {
                $shiYing = $shiYing ?: [];
            }
        }

        if ($liuqinMap === [] && $yaoCode !== '' && $mainGuaName !== '' && $gong !== '') {
            try {
                $gongWuxing = LiuyaoService::BA_GUA_WUXING[$gong] ?? '金';
                $liuqinMap = $this->normalizePositionMap(LiuyaoService::getLiuQin($mainGuaName, $gongWuxing, $yaoCode));
            } catch (\Throwable $exception) {
                $liuqinMap = [];
            }
        }
        if ($liushenMap === [] && $riGan !== '') {
            try {
                $liushenMap = $this->normalizePositionMap(LiuyaoService::getLiuShen($riGan));
            } catch (\Throwable $exception) {
                $liushenMap = [];
            }
        }

        $storedYongShen = is_array($originalGua['yong_shen'] ?? null) ? $originalGua['yong_shen'] : [];
        if (($storedYongShen['liuqin'] ?? '') === '' && !empty($record['yongshen'])) {
            $storedYongShen['liuqin'] = (string) $record['yongshen'];
        }
        $fushenDisplay = $storedYongShen !== [] ? $this->buildFushenDisplay(['yong_shen' => $storedYongShen]) : null;
        $lineDetails = $this->normalizeStoredLineDetails(
            $storedLines,
            $yaoCode,
            $yaoResult,
            $liuqinMap,
            $liushenMap,
            $shiYing,
            $dongYao,
            $this->buildFushenPositionMap($storedYongShen)
        );

        return [
            'id' => (int) ($record['id'] ?? 0),
            'question' => (string) ($record['question'] ?? ''),
            'method' => $method,
            'method_label' => $this->getMethodLabel($method),
            'time_info' => $timeInfo,
            'yao_result' => $yaoResult,
            'yao_names' => array_map(fn(int $item) => $this->getYaoName($item), $yaoResult),
            'gua' => [
                'name' => $mainGuaName,
                'code' => $mainGuaCode,
                'gua_ci' => $guaCi,
            ],
            'gua_name' => $mainGuaName,
            'gua_code' => $mainGuaCode,
            'gua_ci' => $guaCi,
            'bian_gua' => [
                'name' => $bianGuaName,
                'code' => $bianGuaCode,
                'dong_yao' => $dongYao,
            ],
            'hu_gua' => [
                'name' => $huGuaName,
                'code' => $huGuaCode,
            ],
            'gong' => $gong,
            'shi_ying' => $shiYing,
            'liuqin' => $liuqinMap,
            'liushen' => $liushenMap,
            'yong_shen' => $storedYongShen !== [] ? $storedYongShen : ['liuqin' => ''],
            'line_details' => $lineDetails,
            'moving_line_details' => $this->buildMovingLineDetails($lineDetails),
            'interpretation' => $interpretation,
            'ai_analysis' => $aiContent,
            'consumed_points' => $consumedPoints,
            'created_at' => $createdAt,
            'fushen' => $fushenDisplay,
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
            $columns = SchemaInspector::getTableColumns($table);
            if (!empty($columns) && isset($columns['user_id']) && isset($columns['question'])) {
                self::$liuyaoRecordTable = $table;
                return $table;
            }
        }

        self::$liuyaoRecordTable = 'tc_liuyao_record';
        return self::$liuyaoRecordTable;
    }

    private function getLiuyaoRecordColumns(): array
    {
        return SchemaInspector::getTableColumns($this->resolveLiuyaoRecordTable());
    }

    private function filterRecordDataByColumns(array $data, array $columns): array
    {
        return array_filter(
            $data,
            static fn($value, string $key): bool => isset($columns[$key]),
            ARRAY_FILTER_USE_BOTH
        );
    }

    private function buildOriginalHexagramSnapshot(array $coreResult): array
    {
        $lineDetails = $this->buildLineSnapshots($coreResult);

        return [
            'name' => $coreResult['main_gua'] ?? '',
            'code' => $coreResult['clean_gua_code'] ?? '',
            'gua_ci' => (string) ($coreResult['gua_info']['main']['gua_ci'] ?? $coreResult['gua_info']['main']['general_meaning'] ?? ''),
            'yao_code' => $coreResult['yao_code'] ?? '',
            'yao_result' => $coreResult['yao_result'] ?? [],
            'time_info' => $coreResult['time_info'] ?? [],
            'gong' => (string) ($coreResult['gong'] ?? ''),
            'shi_ying' => $coreResult['shi_ying'] ?? [],
            'liuqin' => $coreResult['liuqin'] ?? [],
            'liushen' => $coreResult['liu_shen'] ?? [],
            'yong_shen' => $coreResult['yong_shen'] ?? [],
            'moving_lines' => array_map('intval', (array) ($coreResult['bian_gua']['dong_yao'] ?? [])),
            'moving_line_details' => $this->buildMovingLineDetails($lineDetails),
            'line_details' => $lineDetails,
        ];
    }

    private function buildLineSnapshots(array $coreResult): array
    {
        return $this->normalizeStoredLineDetails(
            [],
            (string) ($coreResult['yao_code'] ?? ''),
            $coreResult['yao_result'] ?? [],
            $coreResult['liuqin'] ?? [],
            $coreResult['liu_shen'] ?? [],
            $coreResult['shi_ying'] ?? [],
            array_map('intval', (array) ($coreResult['bian_gua']['dong_yao'] ?? [])),
            $this->buildFushenPositionMap($coreResult['yong_shen'] ?? [])
        );
    }

    private function buildMovingLineDetails(array $lineDetails): array
    {
        return array_values(array_filter(
            $lineDetails,
            static fn(array $line): bool => !empty($line['is_moving'])
        ));
    }

    private function buildFushenPositionMap(array $yongShen): array
    {
        if (empty($yongShen['is_fushen']) || empty($yongShen['fushen_info']['position'])) {
            return [];
        }

        $position = (int) $yongShen['fushen_info']['position'];
        if ($position < 1 || $position > 6) {
            return [];
        }

        return [
            $position => [
                'name' => $yongShen['liuqin'] ?? '伏神',
                'ganzhi' => $yongShen['fushen_info']['di_zhi'] ?? '',
            ],
        ];
    }

    private function isYangYaoValue(int $value): bool
    {
        return in_array($value, [1, 3], true);
    }

    private function getYinYangLabel(int $value): string
    {
        return $this->isYangYaoValue($value) ? '阳爻' : '阴爻';
    }

    private function resolveChangedYaoValue(int $value, bool $isMoving): int
    {
        if (!$isMoving) {
            return $value;
        }

        return match ($value) {
            0 => 1,
            3 => 2,
            default => $value,
        };
    }

    private function buildLineChangeSummary(int $value, int $changedValue, bool $isMoving): string
    {
        if (!$isMoving) {
            return '静爻不变';
        }

        return $this->getYaoName($value) . ' → ' . $this->getYaoName($changedValue);
    }

    private function normalizePositionMap(array $value): array
    {
        $normalized = [];
        foreach ($value as $key => $item) {
            $position = (int) $key;
            if ($position <= 0) {
                continue;
            }
            $normalized[$position] = is_scalar($item) ? (string) $item : '';
        }

        return $normalized;
    }

    private function parseStoredMoment(string $value): ?\DateTimeImmutable
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($value, new \DateTimeZone('Asia/Shanghai'));
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function hydrateHistoricalTimeInfo(array $timeInfo, string $createdAt): array
    {
        $riGan = trim((string) ($timeInfo['ri_gan'] ?? ''));
        $riZhi = trim((string) ($timeInfo['ri_zhi'] ?? ''));
        $riChen = trim((string) ($timeInfo['ri_chen'] ?? ''));
        if ($riChen !== '' && ($riGan === '' || $riZhi === '')) {
            $riGan = mb_substr($riChen, 0, 1);
            $riZhi = mb_substr($riChen, 1);
        }

        $moment = $this->parseStoredMoment((string) ($timeInfo['divination_at'] ?? $createdAt));
        if (($riGan === '' || $riZhi === '') && $moment !== null) {
            try {
                [$riGan, $riZhi] = $this->resolveRiChen([], $moment);
            } catch (\Throwable $exception) {
                $riGan = $riGan ?: '';
                $riZhi = $riZhi ?: '';
            }
        }

        if ($riGan !== '' && $riZhi !== '' && $riChen === '') {
            $riChen = $riGan . $riZhi;
        }

        $yueJian = trim((string) ($timeInfo['yue_jian'] ?? ''));
        if ($yueJian === '' && $moment !== null) {
            try {
                $yueJian = $this->resolveYueJian($moment);
            } catch (\Throwable $exception) {
                $yueJian = '';
            }
        }

        $xunkong = array_values(array_filter(array_map('strval', (array) ($timeInfo['xunkong'] ?? []))));
        if ($xunkong === [] && $riGan !== '' && $riZhi !== '') {
            try {
                $xunkong = LiuyaoService::calculateXunKong($riGan, $riZhi);
            } catch (\Throwable $exception) {
                $xunkong = [];
            }
        }

        return [
            'ri_gan' => $riGan,
            'ri_zhi' => $riZhi,
            'ri_chen' => $riChen,
            'yue_jian' => $yueJian,
            'divination_at' => trim((string) ($timeInfo['divination_at'] ?? $createdAt)),
            'xunkong' => $xunkong,
        ];
    }

    private function normalizeStoredLineDetails(array $storedLines, string $yaoCode, array $yaoResult, array $liuqinMap, array $liushenMap, array $shiYing, array $dongYao, array $fushenMap = []): array
    {
        $lineDetails = [];
        $shiPosition = (int) ($shiYing['shi'] ?? 0);
        $yingPosition = (int) ($shiYing['ying'] ?? 0);
        $movingLines = array_map('intval', $dongYao);
        $diZhiMap = $yaoCode !== '' ? LiuyaoService::getYaoDiZhiMap($yaoCode) : [];
        $storedByPosition = [];

        foreach ($storedLines as $line) {
            if (!is_array($line)) {
                continue;
            }
            $position = (int) ($line['position'] ?? 0);
            if ($position <= 0) {
                continue;
            }
            $storedByPosition[$position] = $line;
        }

        $maxStoredPosition = $storedByPosition !== [] ? max(array_keys($storedByPosition)) : 0;
        $lineCount = max(count($yaoResult), $maxStoredPosition, 6);

        for ($position = 1; $position <= $lineCount; $position++) {
            if ($position > count($yaoResult) && !isset($storedByPosition[$position])) {
                continue;
            }

            $stored = $storedByPosition[$position] ?? [];
            $value = array_key_exists('value', $stored)
                ? (int) $stored['value']
                : (int) ($yaoResult[$position - 1] ?? 1);
            $isMoving = array_key_exists('is_moving', $stored)
                ? (bool) $stored['is_moving']
                : (in_array($position, $movingLines, true) || in_array($value, [0, 3], true));
            $changedValue = array_key_exists('bian_value', $stored)
                ? (int) $stored['bian_value']
                : $this->resolveChangedYaoValue($value, $isMoving);
            $fushen = $stored['fushen'] ?? ($fushenMap[$position] ?? null);
            if (!is_array($fushen) || (($fushen['name'] ?? '') === '' && ($fushen['ganzhi'] ?? '') === '')) {
                $fushen = null;
            }

            $lineDetails[] = [
                'position' => $position,
                'value' => $value,
                'name' => (string) ($stored['name'] ?? $this->getYaoName($value)),
                'yin_yang' => (string) ($stored['yin_yang'] ?? $this->getYinYangLabel($value)),
                'is_yang' => array_key_exists('is_yang', $stored) ? (bool) $stored['is_yang'] : $this->isYangYaoValue($value),
                'liuqin' => (string) ($stored['liuqin'] ?? ($liuqinMap[$position] ?? '')),
                'liushen' => (string) ($stored['liushen'] ?? ($liushenMap[$position] ?? '')),
                'di_zhi' => (string) ($stored['di_zhi'] ?? ($diZhiMap[$position] ?? '')),
                'bian_value' => $changedValue,
                'bian_name' => (string) ($stored['bian_name'] ?? $this->getYaoName($changedValue)),
                'bian_yin_yang' => (string) ($stored['bian_yin_yang'] ?? $this->getYinYangLabel($changedValue)),
                'bian_is_yang' => array_key_exists('bian_is_yang', $stored) ? (bool) $stored['bian_is_yang'] : $this->isYangYaoValue($changedValue),
                'change_summary' => (string) ($stored['change_summary'] ?? $this->buildLineChangeSummary($value, $changedValue, $isMoving)),
                'is_moving' => $isMoving,
                'is_shi' => array_key_exists('is_shi', $stored) ? (bool) $stored['is_shi'] : $position === $shiPosition,
                'is_ying' => array_key_exists('is_ying', $stored) ? (bool) $stored['is_ying'] : $position === $yingPosition,
                'fushen' => $fushen,
            ];
        }

        return $lineDetails;
    }


    private function decodeJsonField($value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * 兼容旧版 liuyao_records 两位卦码结构。
     */
    private function buildLegacyGuaCode(array $coreResult): string
    {
        $cleanCode = (string) ($coreResult['clean_gua_code'] ?? '');
        if (strlen($cleanCode) !== 6) {
            return '';
        }

        $trigramMap = [
            '000' => '0', // 坤
            '100' => '1', // 震
            '010' => '2', // 坎
            '110' => '3', // 兑
            '011' => '4', // 巽
            '111' => '5', // 乾
            '101' => '6', // 离
            '001' => '7', // 艮
        ];

        $lower = substr($cleanCode, 0, 3);
        $upper = substr($cleanCode, 3, 3);

        return ($trigramMap[$upper] ?? '') . ($trigramMap[$lower] ?? '');
    }


    /**
     * 仅在存在软删字段时追加过滤。
     */
    private function applyRecordActiveFilter($query)
    {
        $columns = $this->getLiuyaoRecordColumns();
        if (isset($columns['status'])) {
            $query->where('status', 1);
        }
        if (isset($columns['is_deleted'])) {
            $query->where('is_deleted', 0);
        }

        return $query;
    }

    /**
     * 根据代码反解起卦方式
     */
    private function resolveMethodByCode($methodCode): string
    {
        if (is_string($methodCode)) {
            $normalized = strtolower(trim($methodCode));
            $stringMap = [
                'time' => 'time',
                'number' => 'number',
                'manual' => 'manual',
                'coin' => 'manual',
                'random' => 'time',
            ];
            if (isset($stringMap[$normalized])) {
                return $stringMap[$normalized];
            }

            if (is_numeric($normalized)) {
                $methodCode = (int) $normalized;
            } else {
                return 'time';
            }
        }

        return [1 => 'time', 2 => 'number', 3 => 'manual'][(int) $methodCode] ?? 'time';
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