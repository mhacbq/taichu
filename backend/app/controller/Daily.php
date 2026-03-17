<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\DailyFortune;
use app\model\PointsRecord;
use app\service\BaziCalculationService;
use app\service\LunarService;
use think\facade\Db;
use think\facade\Log;

class Daily extends BaseController
{
    protected $middleware = [\app\middleware\Auth::class];
    
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
        $user = $this->request->user;
        $fortune = DailyFortune::getToday();
        $almanac = LunarService::solarToLunar($fortune->date);
        
        $yi = !empty($almanac['yi']) ? $almanac['yi'] : array_values(array_filter(explode(',', $fortune->yi)));
        $ji = !empty($almanac['ji']) ? $almanac['ji'] : array_values(array_filter(explode(',', $fortune->ji)));
        
        // 获取用户八字信息，生成个性化运势
        $personalized = null;
        if ($user) {
            $personalized = $this->generatePersonalizedFortune($user['sub'], $fortune);
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
     * 生成个性化运势
     */
    protected function generatePersonalizedFortune(int $userId, $fortune): ?array
    {
        // 获取用户最近的八字排盘记录
        $baziRecord = Db::name('tc_bazi_record')
            ->where('user_id', $userId)
            ->order('created_at', 'desc')
            ->find();
        
        if (!$baziRecord) {
            return null;
        }
        
        // 计算今日干支（统一以运势记录日期 + 东八区历日为准）
        $today = $fortune->date ?: (new \DateTimeImmutable('now', new \DateTimeZone('Asia/Shanghai')))->format('Y-m-d');
        $todayGanZhi = $this->getDayGanZhi($today);
        $todayGan = mb_substr($todayGanZhi, 0, 1);
        $todayZhi = mb_substr($todayGanZhi, 1, 1);
        
        // 用户日主
        $dayMaster = $baziRecord['day_gan'];
        
        // 五行属性
        $ganWuXing = [
            '甲' => '木', '乙' => '木',
            '丙' => '火', '丁' => '火',
            '戊' => '土', '己' => '土',
            '庚' => '金', '辛' => '金',
            '壬' => '水', '癸' => '水'
        ];
        $zhiWuXing = [
            '子' => '水', '丑' => '土', '寅' => '木', '卯' => '木',
            '辰' => '土', '巳' => '火', '午' => '火', '未' => '土',
            '申' => '金', '酉' => '金', '戌' => '土', '亥' => '水'
        ];
        
        $dayMasterWuxing = $ganWuXing[$dayMaster];
        $todayGanWuxing = $ganWuXing[$todayGan];
        $todayZhiWuxing = $zhiWuXing[$todayZhi];
        
        // 简易强弱判断：月令生扶为强，克泄耗为弱
        $monthZhi = $baziRecord['month_zhi'] ?? '';
        $monthWx = $zhiWuXing[$monthZhi] ?? '';
        $shengMei = ['木' => '水', '火' => '木', '土' => '火', '金' => '土', '水' => '金']; // 生我者
        $isStrong = ($monthWx === $dayMasterWuxing || $monthWx === $shengMei[$dayMasterWuxing]);
        
        // 五行关系
        $wuxingShengKe = [
            '金' => ['生' => '水', '克' => '木', '被生' => '土', '被克' => '火'],
            '木' => ['生' => '火', '克' => '土', '被生' => '水', '被克' => '金'],
            '水' => ['生' => '木', '克' => '火', '被生' => '金', '被克' => '土'],
            '火' => ['生' => '土', '克' => '金', '被生' => '木', '被克' => '水'],
            '土' => ['生' => '金', '克' => '水', '被生' => '火', '被克' => '木']
        ];
        
        // 计算今日与日主的关系
        $relation = '';
        $luckLevel = '平';
        $advice = '';
        
        if ($todayGanWuxing === $dayMasterWuxing) {
            $relation = '比劫';
            $luckLevel = $isStrong ? '平' : '吉';
            $advice = $isStrong ? '今日比劫林立，竞争压力较大，需防范同辈竞争，不宜过度自信。' : '今日比劫帮身，贵人缘佳，适合与朋友合作、共谋大计。';
        } elseif ($wuxingShengKe[$dayMasterWuxing]['被生'] === $todayGanWuxing) {
            $relation = '印绶';
            $luckLevel = $isStrong ? '平' : '吉';
            $advice = $isStrong ? '今日印星过旺，容易思虑过多、固执己见，建议放空心态。' : '今日印绶护身，利于学习进修、考证求职，易得长辈提携。';
        } elseif ($wuxingShengKe[$dayMasterWuxing]['生'] === $todayGanWuxing) {
            $relation = '食伤';
            $luckLevel = $isStrong ? '吉' : '平';
            $advice = $isStrong ? '今日食伤泄秀，才华横溢，创意灵感迸发，事业有望取得新突破。' : '今日精力消耗较快，容易思虑过度，需注意言语分寸，防范口舌。';
        } elseif ($wuxingShengKe[$dayMasterWuxing]['被克'] === $todayGanWuxing) {
            $relation = '官杀';
            $luckLevel = $isStrong ? '吉' : '凶';
            $advice = $isStrong ? '今日官杀制身，威望提升，适合处理公职或重要事务，易获领导赏识。' : '今日官杀攻身，压力倍增，需谨慎行事，防范突发状况，注意身体。';
        } else {
            $relation = '财星';
            $luckLevel = $isStrong ? '吉' : '凶';
            $advice = $isStrong ? '今日财星高照，财源广进，适合理财投资、商务谈判，有意外收获。' : '今日财多身弱，不宜盲目跟风投资，注意守财，防范因财生事。';
        }
        
        // 根据今日运势基础分和个人关系调整
        $baseScore = $fortune->overall_score;
        $adjustedScore = $baseScore;
        if ($luckLevel === '吉') {
            $adjustedScore = min(100, $baseScore + 5);
        } elseif ($luckLevel === '凶') {
            $adjustedScore = max(40, $baseScore - 5);
        }
        
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
            'luckyColors' => $this->getLuckyColorsByWuxing($dayMasterWuxing),
            'luckyDirections' => $this->getLuckyDirectionsByWuxing($dayMasterWuxing),
        ];
    }
    
    /**
     * 根据五行获取幸运色
     */
    protected function getLuckyColorsByWuxing(string $wuxing): array
    {
        $colors = [
            '金' => ['白色', '金色', '银色'],
            '木' => ['绿色', '青色', '翠色'],
            '水' => ['黑色', '蓝色', '灰色'],
            '火' => ['红色', '紫色', '橙色'],
            '土' => ['黄色', '棕色', '咖啡色']
        ];
        return $colors[$wuxing] ?? ['黄色'];
    }
    
    /**
     * 根据五行获取幸运方位
     */
    protected function getLuckyDirectionsByWuxing(string $wuxing): array
    {
        $directions = [
            '金' => ['西方', '西北方'],
            '木' => ['东方', '东南方'],
            '水' => ['北方'],
            '火' => ['南方'],
            '土' => ['中央', '东北方', '西南方']
        ];
        return $directions[$wuxing] ?? ['中央'];
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
            'luckyNumbers' => $this->generateLuckyNumbers($favoriteWuxing, (int)$user['sub']),
            'luckyColors' => $this->generateLuckyColors($favoriteWuxing, (int)$user['sub']),
            'luckyDirections' => $this->generateLuckyDirections($favoriteWuxing, (int)$user['sub']),
            'calculation_method' => '基于八字喜用神推算',
        ]);
    }

    /**
     * 计算喜用五行（简化版）
     */
    protected function calculateFavoriteWuxing(array $baziRecord, int $userId): string
    {
        $dayMaster = $baziRecord['day_gan'];
        $ganWuXing = ['甲' => '木', '乙' => '木', '丙' => '火', '丁' => '火', '戊' => '土', '己' => '土', '庚' => '金', '辛' => '金', '壬' => '水', '癸' => '水'];
        $dmWx = $ganWuXing[$dayMaster] ?? '土';

        // 五行生克关系
        $sheng = ['木' => '水', '火' => '木', '土' => '火', '金' => '土', '水' => '金']; // 被生

        // 简易强弱判断
        $monthZhi = $baziRecord['month_zhi'] ?? '';
        $zhiWuXing = ['子' => '水', '丑' => '土', '寅' => '木', '卯' => '木', '辰' => '土', '巳' => '火', '午' => '火', '未' => '土', '申' => '金', '酉' => '金', '戌' => '土', '亥' => '水'];
        $monthWx = $zhiWuXing[$monthZhi] ?? '';

        $isStrong = ($monthWx === $dmWx || $monthWx === $sheng[$dmWx]);
        
        // 使用固定种子，确保结果每日一致且对不同用户唯一
        $seed = crc32($userId . date('Ymd'));

        if (!$isStrong) {
            // 身弱，喜印比 (生我者或同我者)
            return ($seed % 2 == 0) ? $dmWx : $sheng[$dmWx];
        } else {
            // 身旺，喜食财官 (我生者、我克者、克我者)
            $options = [
                '木' => ['火', '土', '金'],
                '火' => ['土', '金', '水'],
                '土' => ['金', '水', '木'],
                '金' => ['水', '木', '火'],
                '水' => ['木', '火', '土'],
            ];
            $choices = $options[$dmWx];
            return $choices[$seed % count($choices)];
        }
    }
    
    /**
     * 生成幸运数字
     */
    protected function generateLuckyNumbers(string $wuxing, int $userId): array
    {
        $map = [
            '木' => [3, 8, 13, 18, 23, 28],
            '火' => [2, 7, 12, 17, 22, 27],
            '土' => [5, 10, 15, 20, 25, 30],
            '金' => [4, 9, 14, 19, 24, 29],
            '水' => [1, 6, 11, 16, 21, 26],
        ];
        
        $pool = $map[$wuxing] ?? $map['土'];
        $seed = crc32($userId . date('Ymd') . 'numbers');
        $offset = $seed % count($pool);
        
        $result = [];
        for ($i = 0; $i < 3; $i++) {
            $result[] = $pool[($offset + $i) % count($pool)];
        }
        return $result;
    }
    
    /**
     * 生成幸运颜色
     */
    protected function generateLuckyColors(string $wuxing, int $userId): array
    {
        $colors = [
            '金' => ['白色', '金色', '银色'],
            '木' => ['绿色', '青色', '翠色'],
            '水' => ['黑色', '蓝色', '灰色'],
            '火' => ['红色', '紫色', '橙色'],
            '土' => ['黄色', '棕色', '咖啡色']
        ];
        
        $pool = $colors[$wuxing] ?? ['黄色', '棕色'];
        $seed = crc32($userId . date('Ymd') . 'colors');
        $offset = $seed % count($pool);
        
        return [
            $pool[$offset],
            $pool[($offset + 1) % count($pool)]
        ];
    }
    
    /**
     * 生成幸运方位
     */
    protected function generateLuckyDirections(string $wuxing, int $userId): array
    {
        $directions = [
            '金' => ['西方', '西北方'],
            '木' => ['东方', '东南方'],
            '水' => ['北方'],
            '火' => ['南方'],
            '土' => ['中央', '东北方', '西南方']
        ];
        
        $pool = $directions[$wuxing] ?? ['中央'];
        $seed = crc32($userId . date('Ymd') . 'directions');
        $offset = $seed % count($pool);
        
        if (count($pool) == 1) return [$pool[0]];
        return [
            $pool[$offset],
            $pool[($offset + 1) % count($pool)]
        ];
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
        $basePoints = self::CHECKIN_POINTS;
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
            'todayPoints' => self::CHECKIN_POINTS,
            'monthCheckins' => $monthCheckins,
            'consecutiveBonus' => self::CONSECUTIVE_BONUS,
        ]);
    }
}
