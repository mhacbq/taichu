<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\BaziRecord;
use app\model\DailyFortune;
use app\model\PointsRecord;
use app\service\BaziCalculationService;
use app\service\ConfigService;
use app\service\LunarService;
use app\service\WuxingHelper;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use think\facade\Config;
use think\facade\Db;
use think\facade\Log;

class Daily extends BaseController
{
    protected $middleware = [
        \app\middleware\OptionalAuth::class => ['only' => ['fortune']],
        \app\middleware\Auth::class => ['only' => ['luck', 'checkin', 'checkinStatus']],
    ];

    
    // 签到奖励积分
    const CHECKIN_POINTS = 5;
    // 连续签到额外奖励
    const CONSECUTIVE_BONUS = [3 => 3, 7 => 7, 15 => 15, 30 => 30];

    /**
     * @var BaziCalculationService
     */
    protected $baziCalculationService;

    protected ?array $checkinStorage = null;

    public function __construct(\think\App $app)
    {
        parent::__construct($app);
        $this->baziCalculationService = new BaziCalculationService();
    }
    
    /**
     * 获取今日运势
     */
    public function fortune()
    {
        $user = $this->resolveOptionalUser();
        $fortune = DailyFortune::getToday();
        $almanac = LunarService::solarToLunar($fortune->date);

        
        $yi = !empty($almanac['yi']) ? $almanac['yi'] : array_values(array_filter(explode(',', $fortune->yi)));
        $ji = !empty($almanac['ji']) ? $almanac['ji'] : array_values(array_filter(explode(',', $fortune->ji)));
        
        // 获取用户八字信息，生成个性化运势
        $personalized = null;
        if ($user) {
            $personalized = $this->generatePersonalizedFortune($user['sub'], $fortune, $almanac);
        }

        
        return $this->success([
            'date' => $fortune->date,
            'lunarDate' => $almanac['lunar_text'] ?? $fortune->lunar_date,
            'ganzhi' => $almanac['ganzhi'] ?? '',
            'overallScore' => $fortune->overall_score,
            'summary' => $fortune->summary,
            'aspects' => [
                ['name' => '事业运', 'icon' => '💼', 'score' => $fortune->career_score, 'description' => $fortune->career_desc],
                ['name' => '财运', 'icon' => '💰', 'score' => $fortune->wealth_score, 'description' => $fortune->wealth_desc],
                ['name' => '感情运', 'icon' => '💕', 'score' => $fortune->love_score, 'description' => $fortune->love_desc],
                ['name' => '健康运', 'icon' => '🏃', 'score' => $fortune->health_score, 'description' => $fortune->health_desc],
            ],
            'yi' => $yi,
            'ji' => $ji,
            'details' => [
                'career' => $fortune->career_desc,
                'wealth' => $fortune->wealth_desc,
                'love' => $fortune->love_desc,
                'health' => $fortune->health_desc,
            ],
            'almanac' => [
                'dayGanzhi' => $almanac['day_gan_zhi'] ?? '',
                'monthGanzhi' => $almanac['month_gan_zhi'] ?? '',
                'yearGanzhi' => $almanac['year_gan_zhi'] ?? '',
                'zhiri' => $almanac['zhiri'] ?? '',
                'jishen' => $almanac['jishen'] ?? [],
                'xiongsha' => $almanac['xiongsha'] ?? [],
                'sha' => $almanac['sha'] ?? '',
                'shichen' => $almanac['shichen'] ?? [],
            ],
            'personalized' => $personalized,
        ]);
    }

    /**
     * 公开接口的登录态兜底。
     * 即使 OptionalAuth 因部署漂移或缓存未生效，也尝试按 Bearer Token 还原用户上下文。
     */
    protected function resolveOptionalUser(): ?array
    {
        $requestUser = $this->request->user ?? null;
        if (is_array($requestUser) && !empty($requestUser['sub'])) {
            return $requestUser;
        }

        $authorization = (string) ($this->request->header('Authorization') ?? '');
        $token = '';
        if ($authorization !== '' && stripos($authorization, 'Bearer ') === 0) {
            $token = trim(substr($authorization, 7));
        }
        if ($token === '') {
            $token = trim((string) $this->request->param('token', ''));
        }
        if ($token === '' || strlen($token) < 10) {
            return null;
        }

        try {
            $secret = (string) Config::get('jwt.secret');
            if ($secret === '') {
                return null;
            }

            $decoded = (array) JWT::decode($token, new Key($secret, 'HS256'));
            if (empty($decoded['sub'])) {
                return null;
            }

            $this->request->user = $decoded;
            return $decoded;
        } catch (\Throwable $e) {
            Log::warning('每日运势可选鉴权解析失败，按游客返回公共结果', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }
    
    /**
     * 生成个性化运势
     */

    protected function generatePersonalizedFortune(int $userId, $fortune, array $almanac = []): ?array
    {
        // 获取用户最近的八字排盘记录
        $baziRecord = BaziRecord::where('user_id', $userId)
            ->order('created_at', 'desc')
            ->find();
        
        if (!$baziRecord) {
            return null;
        }

        $baziRecord = $baziRecord->toArray();

        
        // 今日干支优先复用黄历口径，确保页面展示与个性化分析始终是同一套底盘。
        $today = $fortune->date ?: (new \DateTimeImmutable('now', new \DateTimeZone('Asia/Shanghai')))->format('Y-m-d');
        $todayGanZhi = (string)($almanac['day_gan_zhi'] ?? '');
        if (mb_strlen($todayGanZhi) < 2) {
            $todayGanZhi = $this->getDayGanZhi($today);
        }
        $todayGan = mb_substr($todayGanZhi, 0, 1);
        $todayZhi = mb_substr($todayGanZhi, 1, 1);

        
        // 用户日主
        $dayMaster = $baziRecord['day_gan'] ?? '';
        if ($dayMaster === '' && isset($baziRecord['day_pillar'])) {
            $dayMaster = mb_substr($baziRecord['day_pillar'], 0, 1);
        }
        if ($dayMaster === '') {
            return null; // 无法获取日主信息，返回null
        }
        
        $dayMasterWuxing = WuxingHelper::ganToWuxing($dayMaster);
        $todayGanWuxing = WuxingHelper::ganToWuxing($todayGan);
        $todayZhiWuxing = WuxingHelper::zhiToWuxing($todayZhi);
        
        // 简易强弱判断：月令生扶为强
        $monthZhi = $baziRecord['month_zhi'] ?? '';
        $isStrong = WuxingHelper::isStrong($dayMasterWuxing, $monthZhi);
        
        // 使用李虚中命书算法分析流日与日柱关系
        $liXuZhongService = new \app\service\LiXuZhongService();
        $dayPillar = $baziRecord['day_pillar'] ?? ($dayMaster . ($baziRecord['day_zhi'] ?? '子'));
        $lxzResult = $liXuZhongService->analyzeRelationship($todayGanZhi, $dayPillar);
        
        $relation = $lxzResult['category'];
        $luckLevel = $lxzResult['level'];
        $advice = $lxzResult['description'];
        
        // 根据今日运势基础分和李虚中算法调整
        $baseScore = $fortune->overall_score;
        $adjustedScore = max(40, min(100, $baseScore + $lxzResult['score_adjustment']));
        
        return [
            'hasBazi' => true,
            'dayMaster' => $dayMaster,
            'dayMasterWuxing' => $dayMasterWuxing,
            'todayGanZhi' => $todayGan . $todayZhi,
            'todayWuxing' => $todayGanWuxing . $todayZhiWuxing,
            'relation' => $relation,
            'luckLevel' => $luckLevel,
            'advice' => $advice,
            'personalScore' => $adjustedScore,
            'luckyColors' => WuxingHelper::getLuckyColors($dayMasterWuxing),
            'luckyDirections' => WuxingHelper::getLuckyDirections($dayMasterWuxing),
        ];
    }
    
    /**
     * 根据五行获取幸运色
     */
    protected function getLuckyColorsByWuxing(string $wuxing): array
    {
        return WuxingHelper::getLuckyColors($wuxing);
    }

    /**
     * 根据五行获取幸运方位
     */
    protected function getLuckyDirectionsByWuxing(string $wuxing): array
    {
        return WuxingHelper::getLuckyDirections($wuxing);
    }
    
    /**
     * 获取指定日期的日干支
     * 统一使用 BaziCalculationService 提供的核心算法
     */
    protected function getDayGanZhi(string $date): string
    {
        $moment = new \DateTimeImmutable($date . ' 12:00:00', new \DateTimeZone('Asia/Shanghai'));
        $year = (int)$moment->format('Y');
        $month = (int)$moment->format('n');
        $day = (int)$moment->format('j');

        $tianGan = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];
        $diZhi = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];

        $dayPillar = $this->baziCalculationService->calculateDayPillar($year, $month, $day);

        return $tianGan[$dayPillar['gan_index']] . $diZhi[$dayPillar['zhi_index']];
    }
    
    /**
     * 获取今日宜忌
     */
    public function luck()
    {
        $user = $this->request->user;
        $fortune = DailyFortune::getToday();
        $almanac = LunarService::solarToLunar($fortune->date);
        
        $favoriteWuxing = '土'; // 默认
        if ($user) {
            $baziRecord = Db::name('tc_bazi_record')
                ->where('user_id', $user['sub'])
                ->order('created_at', 'desc')
                ->find();
            
            if ($baziRecord) {
                // 计算喜用五行 (简化逻辑：身弱取印比，身旺取食财官)
                $favoriteWuxing = $this->calculateFavoriteWuxing($baziRecord, (int)$user['sub']);
            }
        }
        
        return $this->success([
            'date' => $fortune->date,
            'lunarDate' => $almanac['lunar_text'] ?? $fortune->lunar_date,
            'yi' => !empty($almanac['yi']) ? $almanac['yi'] : array_values(array_filter(explode(',', $fortune->yi))),
            'ji' => !empty($almanac['ji']) ? $almanac['ji'] : array_values(array_filter(explode(',', $fortune->ji))),
            'luckyNumbers' => WuxingHelper::getLuckyNumbers($favoriteWuxing, (int)$user['sub']),
            'luckyColors' => WuxingHelper::getLuckyColors($favoriteWuxing, (int)$user['sub']),
            'luckyDirections' => WuxingHelper::getLuckyDirections($favoriteWuxing, (int)$user['sub']),
            'calculation_method' => '基于八字喜用神推算',
        ]);
    }

    /**
     * 计算喜用五行（简化版）
     */
    protected function calculateFavoriteWuxing(array $baziRecord, int $userId): string
    {
        $birthDate = (string)($baziRecord['birth_date'] ?? '');
        $gender = (string)($baziRecord['gender'] ?? '男');

        if ($birthDate !== '') {
            try {
                $bazi = $this->baziCalculationService->calculateBazi($birthDate, $gender);
                $favoriteDetails = array_values(array_filter(
                    $bazi['strength']['favorite_wuxing_details'] ?? [],
                    static fn($item): bool => is_array($item) && is_string($item['element'] ?? null) && ($item['element'] ?? '') !== ''
                ));
                if (!empty($favoriteDetails)) {
                    return (string) $favoriteDetails[0]['element'];
                }

                $favoriteWuxing = array_values(array_filter(
                    $bazi['strength']['favorite_wuxing'] ?? [],
                    static fn($item) => is_string($item) && $item !== ''
                ));
                if (!empty($favoriteWuxing)) {
                    return $favoriteWuxing[0];
                }

                $dayMasterWuxing = (string)($bazi['day_master_wuxing'] ?? ($bazi['day']['gan_wuxing'] ?? ''));
                if ($dayMasterWuxing !== '') {
                    return $dayMasterWuxing;
                }
            } catch (\Throwable $e) {
                Log::warning('每日运势喜用五行回退到简化算法', [
                    'user_id' => $userId,
                    'birth_date' => $birthDate,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 回退：用日主干支 + 月令判断强弱
        $dayMaster = (string)($baziRecord['day_gan'] ?? '戊');
        $dmWx = WuxingHelper::ganToWuxing($dayMaster) ?: '土';
        $monthZhi = (string)($baziRecord['month_zhi'] ?? '');
        $isStrong = WuxingHelper::isStrong($dmWx, $monthZhi);

        return WuxingHelper::getFavoriteWuxing($dmWx, $isStrong);
    }

    
    /**
     * 生成幸运数字（已迁移到 WuxingHelper，保留兼容入口）
     */
    protected function generateLuckyNumbers(string $wuxing, int $userId): array
    {
        return WuxingHelper::getLuckyNumbers($wuxing, $userId);
    }

    /**
     * 生成幸运颜色（已迁移到 WuxingHelper，保留兼容入口）
     */
    protected function generateLuckyColors(string $wuxing, int $userId): array
    {
        return WuxingHelper::getLuckyColors($wuxing, $userId);
    }

    /**
     * 生成幸运方位（已迁移到 WuxingHelper，保留兼容入口）
     */
    protected function generateLuckyDirections(string $wuxing, int $userId): array
    {
        return WuxingHelper::getLuckyDirections($wuxing, $userId);
    }

    protected function resolveCheckinStorage(): array
    {
        if ($this->checkinStorage !== null) {
            return $this->checkinStorage;
        }

        foreach (['checkin_record', 'tc_checkin_record'] as $table) {
            if (!$this->tableExists($table)) {
                continue;
            }

            $columns = $this->getTableColumns($table);
            $dateColumn = isset($columns['date']) ? 'date' : (isset($columns['checkin_date']) ? 'checkin_date' : 'date');
            $consecutiveColumn = isset($columns['consecutive_days']) ? 'consecutive_days' : (isset($columns['continuous_days']) ? 'continuous_days' : 'consecutive_days');

            return $this->checkinStorage = [
                'table' => $table,
                'date_column' => $dateColumn,
                'consecutive_column' => $consecutiveColumn,
            ];
        }

        return $this->checkinStorage = [
            'table' => 'checkin_record',
            'date_column' => 'date',
            'consecutive_column' => 'consecutive_days',
        ];
    }

    protected function findCheckinRecord(int $userId, string $date): ?array
    {
        $storage = $this->resolveCheckinStorage();

        return Db::name($storage['table'])
            ->where('user_id', $userId)
            ->where($storage['date_column'], $date)
            ->find();
    }

    protected function getMonthCheckins(int $userId, string $monthStart): array
    {
        $storage = $this->resolveCheckinStorage();

        return Db::name($storage['table'])
            ->where('user_id', $userId)
            ->where($storage['date_column'], '>=', $monthStart)
            ->column($storage['date_column']);
    }

    protected function tableExists(string $table): bool
    {
        $escapedTable = addslashes($table);
        return !empty(Db::query("SHOW TABLES LIKE '{$escapedTable}'"));
    }

    protected function getTableColumns(string $table): array
    {
        $escapedTable = str_replace('`', '``', $table);
        $columns = [];

        foreach (Db::query("SHOW COLUMNS FROM `{$escapedTable}`") as $column) {
            $field = (string) ($column['Field'] ?? '');
            if ($field !== '') {
                $columns[$field] = true;
            }
        }

        return $columns;
    }

    protected function getCheckinRewardPoints(): int
    {
        $configuredPoints = ConfigService::get('checkin_points', ConfigService::get('points_sign_daily', self::CHECKIN_POINTS));
        return max(0, (int) $configuredPoints);
    }
    
    /**
     * 每日签到
     */

    public function checkin()
    {
        $user = $this->request->user ?? [];
        $userId = isset($user['sub']) ? (int) $user['sub'] : 0;
        if ($userId <= 0) {
            return $this->error('请先登录', 401);
        }

        $today = date('Y-m-d');
        $storage = $this->resolveCheckinStorage();
        
        // 检查今天是否已签到
        $todayCheckin = $this->findCheckinRecord($userId, $today);
        
        if ($todayCheckin) {
            return $this->error('您今天已经签到过了，明天再来吧！', 400);
        }
        
        // 获取昨天是否签到（用于计算连续天数）
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $yesterdayCheckin = $this->findCheckinRecord($userId, $yesterday);
        
        $consecutiveDays = $yesterdayCheckin ? ((int) ($yesterdayCheckin[$storage['consecutive_column']] ?? 0) + 1) : 1;
        
        // 计算奖励
        $basePoints = $this->getCheckinRewardPoints();
        $bonusPoints = 0;

        
        foreach (self::CONSECUTIVE_BONUS as $days => $bonus) {
            if ($consecutiveDays >= $days) {
                $bonusPoints = $bonus;
            }
        }
        
        $totalPoints = $basePoints + $bonusPoints;
        
        Db::startTrans();
        try {
            // 记录签到，依赖(user_id, date/checkin_date)唯一索引兜底并发重复请求
            Db::name($storage['table'])->insert([
                'user_id' => $userId,
                $storage['date_column'] => $today,
                $storage['consecutive_column'] => $consecutiveDays,
                'points' => $totalPoints,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            
            // 增加积分
            $userModel = \app\model\User::find($userId);
            if (!$userModel) {
                throw new \RuntimeException('签到用户不存在');
            }

            $userModel->addPoints($totalPoints);
            
            // 记录积分变动
            $remark = "连续签到{$consecutiveDays}天";
            if ($bonusPoints > 0) {
                $remark .= "，额外奖励{$bonusPoints}积分";
            }
            
            PointsRecord::record(
                $userId,
                '每日签到',
                $totalPoints,
                'checkin',
                0,
                $remark
            );
            
            Db::commit();
            
            return $this->success([
                'points' => $totalPoints,
                'consecutiveDays' => $consecutiveDays,
                'bonusPoints' => $bonusPoints,
                'totalPoints' => $userModel->points,
                'message' => $bonusPoints > 0 
                    ? "签到成功！获得{$basePoints}积分，连续{$consecutiveDays}天额外奖励{$bonusPoints}积分！"
                    : "签到成功！获得{$basePoints}积分！",
            ]);
        } catch (\Throwable $e) {
            Db::rollback();

            if ($this->isDuplicateCheckinException($e)) {
                return $this->error('您今天已经签到过了，明天再来吧！', 400);
            }

            Log::error('每日签到失败', [
                'user_id' => $userId,
                'date' => $today,
                'error' => $e->getMessage(),
            ]);

            return $this->error('签到失败，请稍后重试', 500);
        }
    }
    
    /**
     * 判断是否为重复签到异常
     */
    protected function isDuplicateCheckinException(\Throwable $e): bool
    {
        $errorCode = (string) $e->getCode();
        $message = strtolower($e->getMessage());

        return in_array($errorCode, ['1062', '23000'], true)
            || str_contains($message, 'duplicate')
            || str_contains($message, 'uk_user_date');
    }

    /**
     * 获取签到状态
     */
    public function checkinStatus()
    {
        $user = $this->request->user;
        $userId = $user['sub'];
        $today = date('Y-m-d');
        $storage = $this->resolveCheckinStorage();
        
        // 检查今天是否已签到
        $todayCheckin = $this->findCheckinRecord($userId, $today);
        
        // 获取连续签到天数
        $consecutiveDays = $todayCheckin ? (int) ($todayCheckin[$storage['consecutive_column']] ?? 0) : 0;
        
        // 如果今天没签到，检查昨天是否签到
        if (!$todayCheckin) {
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $yesterdayCheckin = $this->findCheckinRecord($userId, $yesterday);
            $consecutiveDays = $yesterdayCheckin ? (int) ($yesterdayCheckin[$storage['consecutive_column']] ?? 0) : 0;
        }
        
        // 获取本月签到记录
        $monthStart = date('Y-m-01');
        $monthCheckins = $this->getMonthCheckins($userId, $monthStart);
        
        return $this->success([
            'checkedIn' => !!$todayCheckin,
            'consecutiveDays' => $consecutiveDays,
            'todayPoints' => $this->getCheckinRewardPoints(),
            'monthCheckins' => $monthCheckins,
            'consecutiveBonus' => self::CONSECUTIVE_BONUS,
        ]);

    }
}
