<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\BaziRecord;
use app\service\YearlyFortuneService;
use app\service\DayunFortuneService;
use app\service\CacheService;
use think\facade\Log;
use think\Request;

/**
 * 运势分析控制器
 * 提供流年运势、大运评分、运势K线图等功能
 */
class Fortune extends BaseController
{
    protected $middleware = [\app\middleware\Auth::class];
    
    /**
     * @var YearlyFortuneService
     */
    protected $yearlyService;
    
    /**
     * @var DayunFortuneService
     */
    protected $dayunService;
    
    public function __construct(\think\App $app)
    {
        parent::__construct($app);
        $this->yearlyService = new YearlyFortuneService();
        $this->dayunService = new DayunFortuneService();
    }
    
    /**
     * 获取流年运势分析
     * 
     * @param Request $request
     * @return \think\Response
     */
    public function yearlyFortune(Request $request)
    {
        $baziId = $request->param('bazi_id', 0);
        $year = (int)$request->param('year', date('Y'));
        $user = $request->user;
        $userId = (int) $user['sub'];

        // 如果未提供 bazi_id，尝试自动查找或基于用户出生信息创建
        if (!$baziId) {
            // 优先使用前端直接传入的出生日期时间（birthDateTime 参数）
            $birthDateTime = trim((string) $request->param('birthDateTime', ''));
            $genderParam = trim((string) $request->param('gender', ''));

            if ($birthDateTime !== '') {
                // 前端直接传入了出生日期，自动排盘并创建八字记录
                $genderText = in_array(strtolower($genderParam), ['female', '女', '2'], true) ? '女' : '男';
                try {
                    $autoResult = $this->autoCreateBaziRecord($userId, $birthDateTime, $genderText, '');
                } catch (\Throwable $e) {
                    Log::error('流年运势自动排盘失败: ' . $e->getMessage(), [
                        'user_id' => $userId,
                        'birth_date' => $birthDateTime,
                    ]);
                    return $this->error('自动排盘失败，请前往八字排盘页面手动排盘后再查看流年运势', 500);
                }
                $baziId = $autoResult['bazi_id'];
            } else {
                // 没有传入出生日期，尝试从用户历史记录或 profile 中获取
                $autoResult = $this->resolveOrCreateBaziRecord($userId);
                if ($autoResult['error'] ?? false) {
                    return $this->error($autoResult['message'], $autoResult['code'] ?? 400);
                }
                $baziId = $autoResult['bazi_id'];
            }
        }

        // 获取八字记录
        $record = BaziRecord::where('id', $baziId)
            ->where('user_id', $userId)
            ->find();

        if (!$record) {
            return $this->error('八字记录不存在', 404);
        }

        $bazi = $this->buildBaziPayload($record);
        $gender = $this->resolveRecordGender($record);

        try {
            $result = $this->yearlyService->getYearlyFortune(
                $bazi,
                $gender,
                $year,
                $userId,
                (int) $baziId
            );
            
            // 返回时附带 bazi_id，方便前端后续请求复用
            $result['bazi_id'] = (int) $baziId;
            
            return $this->success($result);
        } catch (\Throwable $e) {
            return $this->handleFortuneException(
                '获取流年运势',
                $e,
                ['bazi_id' => (int) $baziId, 'year' => $year],
                '积分不足，解锁流年运势需要' . YearlyFortuneService::YEARLY_FORTUNE_POINTS_COST . '积分'
            );
        }
    }
    
    /**
     * 获取多年运势趋势（用于K线图）
     * 
     * @param Request $request
     * @return \think\Response
     */
    public function yearlyTrend(Request $request)
    {
        $baziId = $request->param('bazi_id', 0);
        $startYear = (int)$request->param('start_year', date('Y') - 5);
        $endYear = (int)$request->param('end_year', date('Y') + 5);
        $user = $request->user;
        $userId = (int) $user['sub'];
        
        // 如果未提供 bazi_id，尝试自动查找或基于用户出生信息创建
        if (!$baziId) {
            $autoResult = $this->resolveOrCreateBaziRecord($userId);
            if ($autoResult['error'] ?? false) {
                return $this->error($autoResult['message'], $autoResult['code'] ?? 400);
            }
            $baziId = $autoResult['bazi_id'];
        }
        
        // 限制查询范围
        if ($endYear - $startYear > 20) {
            return $this->error('查询年份范围不能超过20年');
        }
        
        // 获取八字记录
        $record = BaziRecord::where('id', $baziId)
            ->where('user_id', $userId)
            ->find();
        
        if (!$record) {
            return $this->error('八字记录不存在', 404);
        }
        
        // 构建八字数据
        $bazi = [
            'year' => [
                'gan' => $record->year_gan,
                'zhi' => $record->year_zhi,
            ],
            'month' => [
                'gan' => $record->month_gan,
                'zhi' => $record->month_zhi,
            ],
            'day' => [
                'gan' => $record->day_gan,
                'zhi' => $record->day_zhi,
            ],
            'hour' => [
                'gan' => $record->hour_gan,
                'zhi' => $record->hour_zhi,
            ],
        ];
        
        // 检查缓存
        $cacheKey = 'yearly_trend:' . $baziId . ':' . $startYear . ':' . $endYear;
        $cached = CacheService::get($cacheKey);
        if ($cached) {
            return $this->success($cached);
        }
        
        $results = $this->yearlyService->getYearlyFortuneRange(
            $bazi,
            $record->gender,
            $startYear,
            $endYear
        );
        
        $response = [
            'years' => $results,
            'summary' => $this->generateTrendSummary($results),
        ];
        
        // 缓存结果（较短时间）
        CacheService::set($cacheKey, $response, CacheService::TTL_NORMAL, CacheService::TAG_BAZI);
        
        return $this->success($response);
    }
    
    /**
     * 获取大运运势分析
     * 
     * @param Request $request
     * @return \think\Response
     */
    public function dayunAnalysis(Request $request)
    {
        $baziId = $request->param('bazi_id', 0);
        $dayunIndex = (int)$request->param('dayun_index', 0);

        if (!$baziId) {
            return $this->error('请提供八字记录ID');
        }

        // 获取八字记录
        $user = $request->user;
        $record = BaziRecord::where('id', $baziId)
            ->where('user_id', $user['sub'])
            ->find();

        if (!$record) {
            return $this->error('八字记录不存在', 404);
        }

        $bazi = $this->buildBaziPayload($record);

        // 优先复用记录里的大运快照；旧记录缺快照时再按兼容后的性别/出生时分重算
        $dayuns = $this->resolveDayuns($record, $bazi);

        if (!isset($dayuns[$dayunIndex])) {


            return $this->error('大运索引无效', 400);
        }
        
        $dayun = $dayuns[$dayunIndex];
        
        try {
            $result = $this->dayunService->analyzeDayun($dayun, $bazi, $user['sub']);
            
            return $this->success($result);
        } catch (\Throwable $e) {
            return $this->handleFortuneException(
                '获取大运分析',
                $e,
                ['bazi_id' => (int) $baziId, 'dayun_index' => $dayunIndex],
                '积分不足，解锁大运分析需要' . DayunFortuneService::DAYUN_ANALYSIS_POINTS_COST . '积分'
            );
        }

    }
    
    /**
     * 获取大运运势K线图数据
     * 
     * @param Request $request
     * @return \think\Response
     */
    public function dayunChart(Request $request)
    {
        $baziId = $request->param('bazi_id', 0);

        if (!$baziId) {
            return $this->error('请提供八字记录ID');
        }

        // 获取八字记录
        $user = $request->user;
        $record = BaziRecord::where('id', $baziId)
            ->where('user_id', $user['sub'])
            ->find();

        if (!$record) {
            return $this->error('八字记录不存在', 404);
        }

        $bazi = $this->buildBaziPayload($record);

        // 优先复用记录里的大运快照；旧记录缺快照时再按兼容后的性别/出生时分重算
        $dayuns = $this->resolveDayuns($record, $bazi);

        try {


            $result = $this->dayunService->getDayunChartData($dayuns, $bazi, $user['sub']);
            
            return $this->success($result);
        } catch (\Throwable $e) {
            return $this->handleFortuneException(
                '获取大运图表',
                $e,
                ['bazi_id' => (int) $baziId],
                '积分不足，解锁大运图表需要' . DayunFortuneService::DAYUN_CHART_POINTS_COST . '积分'
            );
        }
    }
    
    /**
     * 获取流年运势需要消耗的积分
     * 
     * @return \think\Response
     */
    public function getYearlyFortunePoints()
    {
        return $this->success([
            'yearly_fortune' => YearlyFortuneService::getPointsCost(),
            'dayun_analysis' => DayunFortuneService::getAnalysisPointsCost(),
            'dayun_chart' => DayunFortuneService::getChartPointsCost(),
        ]);
    }

    /**
     * 统一构建运势分析所需的八字结构，兼容旧 tc_bazi_record 的原始字段与 rich schema。
     */
    private function buildBaziPayload(BaziRecord $record): array
    {
        $recordData = $record->toApiArray();
        $birthDate = $this->resolveRecordBirthDate($record, $recordData);
        $birthTimestamp = strtotime($birthDate);
        $birthYear = $birthTimestamp !== false ? (int) date('Y', $birthTimestamp) : null;
        $recordBazi = is_array($recordData['bazi'] ?? null) ? $recordData['bazi'] : [];

        return [
            'year' => [
                'gan' => trim((string) ($recordBazi['year']['gan'] ?? $record->year_gan)),
                'zhi' => trim((string) ($recordBazi['year']['zhi'] ?? $record->year_zhi)),
                'number' => $birthYear,
            ],
            'month' => [
                'gan' => trim((string) ($recordBazi['month']['gan'] ?? $record->month_gan)),
                'zhi' => trim((string) ($recordBazi['month']['zhi'] ?? $record->month_zhi)),
            ],
            'day' => [
                'gan' => trim((string) ($recordBazi['day']['gan'] ?? $record->day_gan)),
                'zhi' => trim((string) ($recordBazi['day']['zhi'] ?? $record->day_zhi)),
            ],
            'hour' => [
                'gan' => trim((string) ($recordBazi['hour']['gan'] ?? $record->hour_gan)),
                'zhi' => trim((string) ($recordBazi['hour']['zhi'] ?? $record->hour_zhi)),
            ],
            'birth_date' => $birthDate,
        ];
    }

    /**
     * 优先复用记录中的大运快照，缺失时再按标准化后的记录信息重算。
     */
    private function resolveDayuns(BaziRecord $record, array $bazi): array
    {
        $reportData = $this->getRecordReportData($record);
        $storedDayuns = $reportData['dayun'] ?? null;
        if ($this->isUsableDayunSnapshot($storedDayuns)) {
            return $storedDayuns;
        }


        $paipanController = new \app\controller\Paipan($this->app);
        return $paipanController->calculateDaYun(
            $bazi,
            $this->resolveRecordGender($record),
            $bazi['birth_date'] ?? $this->resolveRecordBirthDate($record)
        );
    }

    /**
     * 判断历史记录中的大运快照是否足够支持后续评分/图表链路。
     */
    private function isUsableDayunSnapshot($dayuns): bool
    {
        if (!is_array($dayuns) || $dayuns === []) {
            return false;
        }

        foreach ($dayuns as $dayun) {
            if (!is_array($dayun)) {
                return false;
            }

            if (
                trim((string) ($dayun['gan'] ?? '')) === ''
                || trim((string) ($dayun['zhi'] ?? '')) === ''
                || !isset($dayun['start_age'])
                || !isset($dayun['end_age'])
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * 兼容旧表 TINYINT、rich schema 文本与 report_data 中的性别口径。
     */
    private function resolveRecordGender(BaziRecord $record, array $recordData = []): string

    {
        $reportData = $this->getRecordReportData($record);
        $candidates = [
            $recordData['gender'] ?? null,
            $record->getAttr('gender'),
            $reportData['gender'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            $normalized = strtolower(trim((string) $candidate));
            if (in_array($normalized, ['female', '女', '2'], true)) {
                return 'female';
            }
            if (in_array($normalized, ['male', '男', '1'], true)) {
                return 'male';
            }
        }

        Log::warning('运势链路命中缺省性别回退', [
            'record_id' => (int) ($record->getAttr('id') ?? 0),
            'user_id' => (int) ($record->getAttr('user_id') ?? 0),
        ]);

        return 'male';
    }

    /**
     * 兼容 rich schema 的 birth_date + birth_time 以及旧 report_data 里的出生时分文本。
     */
    private function resolveRecordBirthDate(BaziRecord $record, array $recordData = []): string
    {
        $birthDate = trim((string) ($recordData['birth_date'] ?? ''));
        if ($birthDate !== '') {
            return $birthDate;
        }

        $rawBirthDate = trim((string) ($record->getAttr('birth_date') ?? ''));
        $birthTime = trim((string) ($record->getAttr('birth_time') ?? ''));
        if ($rawBirthDate !== '' && $birthTime !== '' && $birthTime !== '00:00:00' && !str_contains($rawBirthDate, ':')) {
            return $rawBirthDate . ' ' . $birthTime;
        }
        if ($rawBirthDate !== '') {
            return $rawBirthDate;
        }

        return trim((string) ($this->getRecordReportData($record)['birth_date'] ?? ''));
    }

    /**
     * 读取 report_data，兼容字符串 JSON 与数组口径。
     */
    private function getRecordReportData(BaziRecord $record): array
    {
        $reportData = $record->getAttr('report_data');
        if (is_array($reportData)) {
            return $reportData;
        }

        if (is_string($reportData) && trim($reportData) !== '') {
            $decoded = json_decode($reportData, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }


    /**
     * 自动解析或创建八字记录
     * 
     * 优先级：
     * 1. 查找用户已有的最近八字记录
     * 2. 如果没有八字记录，检查用户表的 birth_date 字段
     * 3. 如果用户有出生日期，自动排盘并创建八字记录
     * 4. 如果都没有，提示用户设置出生信息
     */
    private function resolveOrCreateBaziRecord(int $userId): array
    {
        // 1. 优先查找用户已有的最近八字记录
        $latestRecord = BaziRecord::where('user_id', $userId)
            ->order('id', 'desc')
            ->find();

        if ($latestRecord) {
            return ['bazi_id' => (int) $latestRecord->id];
        }

        // 2. 没有八字记录，检查用户表的出生信息
        $userInfo = \think\facade\Db::name('tc_user')
            ->where('id', $userId)
            ->field('birth_date, birth_time, birth_place, gender')
            ->find();

        if (!$userInfo) {
            return [
                'error' => true,
                'message' => '用户不存在',
                'code' => 404,
            ];
        }

        $birthDate = trim((string) ($userInfo['birth_date'] ?? ''));
        if ($birthDate === '' || $birthDate === '0000-00-00') {
            return [
                'error' => true,
                'message' => '您尚未设置出生日期，请先在个人中心设置出生日期后再查看流年运势',
                'code' => 422,
            ];
        }

        // 3. 有出生日期，自动排盘创建八字记录
        $birthTime = trim((string) ($userInfo['birth_time'] ?? ''));
        $birthPlace = trim((string) ($userInfo['birth_place'] ?? ''));
        $genderCode = (int) ($userInfo['gender'] ?? 0);

        // 拼接完整的出生时间
        $fullBirthDate = $birthDate;
        if ($birthTime !== '' && $birthTime !== '00:00:00') {
            $fullBirthDate .= ' ' . $birthTime;
        }

        // 转换性别编码为中文（BaziCalculationService 接受中文性别）
        $genderText = $genderCode === 2 ? '女' : '男';

        try {
            return $this->autoCreateBaziRecord($userId, $fullBirthDate, $genderText, $birthPlace);
        } catch (\Throwable $e) {
            Log::error('自动创建八字记录失败: ' . $e->getMessage(), [
                'user_id' => $userId,
                'birth_date' => $fullBirthDate,
            ]);
            return [
                'error' => true,
                'message' => '自动排盘失败，请前往八字排盘页面手动排盘后再查看流年运势',
                'code' => 500,
            ];
        }
    }

    /**
     * 根据用户出生信息自动排盘并创建八字记录
     */
    private function autoCreateBaziRecord(int $userId, string $birthDate, string $gender, string $location): array
    {
        $baziService = new \app\service\BaziCalculationService();
        $interpretationService = new \app\service\BaziInterpretationService();

        // 计算八字（calculateBazi 内部已调用 normalizeBaziStructure）
        $bazi = $baziService->calculateBazi($birthDate, $gender);

        // 生成简单解读（自动排盘不做完整AI解读以节省资源）
        $simpleInterpretation = $interpretationService->generateSimpleInterpretation($bazi, $gender);

        // 计算大运
        $paipanController = new \app\controller\Paipan($this->app);
        $daYun = $paipanController->calculateDaYun($bazi, $gender, $birthDate);

        // 创建八字记录（自动创建不扣积分）
        $record = BaziRecord::createRecord([
            'user_id' => $userId,
            'birth_date' => $birthDate,
            'gender' => $gender,
            'location' => $location,
            'bazi' => $bazi,
            'analysis' => '自动排盘记录（基于用户出生信息）',
            'simple_interpretation' => $simpleInterpretation,
            'full_interpretation' => null,
            'dayun' => $daYun,
            'liunian' => [],
            'fortune_analysis' => null,
            'true_solar_time' => null,
            'points_cost' => 0,
            'is_first' => 0,
        ]);

        if (!$record) {
            throw new \RuntimeException('八字记录保存失败');
        }

        Log::info('自动创建八字记录成功', [
            'user_id' => $userId,
            'record_id' => $record->id,
            'birth_date' => $birthDate,
        ]);

        return ['bazi_id' => (int) $record->id];
    }

    /**
     * 统一处理运势分析异常
     */

    private function handleFortuneException(string $scene, \Throwable $e, array $context = [], string $forbiddenMessage = '当前权益不足或功能暂未开放')
    {
        $code = (int) $e->getCode() === 403 ? 403 : 500;

        Log::error($scene . '失败: ' . $e->getMessage(), array_merge([
            'user_id' => (int) ($this->request->user['sub'] ?? 0),
            'request_url' => $this->request->url(true),
            'request_method' => $this->request->method(),
        ], $context));

        if ($code === 403) {
            return $this->error($forbiddenMessage, 403);
        }

        return $this->error('运势分析失败，请稍后重试', 500);


    }
    
    /**
     * 生成趋势摘要
     */
    protected function generateTrendSummary(array $results): array
    {
        if (empty($results)) {
            return [
                'average_score' => 50,
                'best_year' => null,
                'worst_year' => null,
                'trend' => '平稳',
            ];
        }
        
        $scores = array_column($results, 'score');
        $avgScore = round(array_sum($scores) / count($scores));
        
        $best = $results[array_search(max($scores), $scores)];
        $worst = $results[array_search(min($scores), $scores)];
        
        // 计算趋势
        $firstScore = $scores[0];
        $lastScore = $scores[count($scores) - 1];
        $diff = $lastScore - $firstScore;
        
        if ($diff > 15) $trend = '上升';
        elseif ($diff > 5) $trend = '小升';
        elseif ($diff < -15) $trend = '下降';
        elseif ($diff < -5) $trend = '小降';
        else $trend = '平稳';
        
        return [
            'average_score' => $avgScore,
            'best_year' => [
                'year' => $best['year'],
                'score' => $best['score'],
                'rating' => $best['rating'],
            ],
            'worst_year' => [
                'year' => $worst['year'],
                'score' => $worst['score'],
                'rating' => $worst['rating'],
            ],
            'trend' => $trend,
        ];
    }
}
