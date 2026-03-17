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
        
        try {
            $result = $this->yearlyService->getYearlyFortune(
                $bazi,
                $record->gender,
                $year,
                $user['sub']
            );
            
            return $this->success($result);
        } catch (\Exception $e) {
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
        
        if (!$baziId) {
            return $this->error('请提供八字记录ID');
        }
        
        // 限制查询范围
        if ($endYear - $startYear > 20) {
            return $this->error('查询年份范围不能超过20年');
        }
        
        // 获取八字记录
        $user = $request->user;
        $record = BaziRecord::where('id', $baziId)
            ->where('user_id', $user['sub'])
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
        
        // 重新计算大运
        $paipanController = new \app\controller\Paipan();
        $dayuns = $paipanController->calculateDaYun($bazi, $record->gender, $record->birth_date);
        
        if (!isset($dayuns[$dayunIndex])) {
            return $this->error('大运索引无效', 400);
        }
        
        $dayun = $dayuns[$dayunIndex];
        
        try {
            $result = $this->dayunService->analyzeDayun($dayun, $bazi, $user['sub']);
            
            return $this->success($result);
        } catch (\Exception $e) {
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
        
        // 重新计算大运
        $paipanController = new \app\controller\Paipan();
        $dayuns = $paipanController->calculateDaYun($bazi, $record->gender, $record->birth_date);
        
        try {
            $result = $this->dayunService->getDayunChartData($dayuns, $bazi, $user['sub']);
            
            return $this->success($result);
        } catch (\Exception $e) {
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
            'yearly_fortune' => YearlyFortuneService::YEARLY_FORTUNE_POINTS_COST,
            'dayun_analysis' => DayunFortuneService::DAYUN_ANALYSIS_POINTS_COST,
            'dayun_chart' => DayunFortuneService::DAYUN_CHART_POINTS_COST,
        ]);
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
