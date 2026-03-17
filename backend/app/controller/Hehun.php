<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\HehunRecord;
use app\model\PointsRecord;
use app\model\User;
use app\service\AiService;
use app\service\BaziCalculationService;
use app\service\CacheService;
use app\service\ConfigService;
use think\facade\Db;

/**
 * 八字合婚控制器
 * 
 * 分层付费设计：
 * - 免费层：基础合婚分数、简单等级评定、1条基础建议
 * - 付费层（80积分）：五维度详细评分、AI深度分析、化解方案、流年分析
 * - VIP层：所有付费功能免费
 */
class Hehun extends BaseController
{
    // 合婚基础积分（免费预览后解锁详细报告）
    const HEHUN_POINTS_COST = 80;
    
    // 导出报告额外积分
    const HEHUN_EXPORT_COST = 30;
    
    // 是否启用缓存
    const ENABLE_CACHE = true;
    
    // 分层配置
    const TIER_FREE = 'free';
    const TIER_PREMIUM = 'premium';
    const TIER_VIP = 'vip';
    
    protected $middleware = [\app\middleware\Auth::class];
    
    /**
     * @var BaziCalculationService
     */
    protected $baziCalculationService;
    
    public function __construct()
    {
        parent::__construct();
        $this->baziCalculationService = new BaziCalculationService();
    }
    
    /**
     * 天干五行
     */
    protected $ganWuXing = [
        '甲' => '木', '乙' => '木',
        '丙' => '火', '丁' => '火',
        '戊' => '土', '己' => '土',
        '庚' => '金', '辛' => '金',
        '壬' => '水', '癸' => '水'
    ];
    
    /**
     * 地支五行
     */
    protected $zhiWuXing = [
        '子' => '水', '丑' => '土', '寅' => '木', '卯' => '木',
        '辰' => '土', '巳' => '火', '午' => '火', '未' => '土',
        '申' => '金', '酉' => '金', '戌' => '土', '亥' => '水'
    ];
    
    /**
     * 天干阴阳
     */
    protected $ganYinYang = [
        '甲' => '阳', '乙' => '阴', '丙' => '阳', '丁' => '阴', '戊' => '阳',
        '己' => '阴', '庚' => '阳', '辛' => '阴', '壬' => '阳', '癸' => '阴'
    ];
    
    /**
     * 地支藏干
     */
    protected $zhiCangGan = [
        '子' => ['癸'],
        '丑' => ['己', '癸', '辛'],
        '寅' => ['甲', '丙', '戊'],
        '卯' => ['乙'],
        '辰' => ['戊', '乙', '癸'],
        '巳' => ['丙', '庚', '戊'],
        '午' => ['丁', '己'],
        '未' => ['己', '丁', '乙'],
        '申' => ['庚', '壬', '戊'],
        '酉' => ['辛'],
        '戌' => ['戊', '辛', '丁'],
        '亥' => ['壬', '甲']
    ];
    
    /**
     * 生肖配对宜忌
     */
    protected $shengxiaoMatch = [
        '鼠' => ['宜' => ['龙', '猴', '牛'], '忌' => ['马', '羊', '兔']],
        '牛' => ['宜' => ['鼠', '蛇', '鸡'], '忌' => ['马', '羊', '狗']],
        '虎' => ['宜' => ['马', '狗', '猪'], '忌' => ['猴', '蛇']],
        '兔' => ['宜' => ['羊', '狗', '猪'], '忌' => ['龙', '鼠', '鸡']],
        '龙' => ['宜' => ['鼠', '猴', '鸡'], '忌' => ['狗', '兔', '龙']],
        '蛇' => ['宜' => ['牛', '鸡', '猴'], '忌' => ['猪', '虎']],
        '马' => ['宜' => ['虎', '羊', '狗'], '忌' => ['鼠', '牛', '马']],
        '羊' => ['宜' => ['兔', '马', '猪'], '忌' => ['牛', '狗', '羊']],
        '猴' => ['宜' => ['鼠', '龙', '蛇'], '忌' => ['虎', '猪']],
        '鸡' => ['宜' => ['牛', '龙', '蛇'], '忌' => ['兔', '狗', '鸡']],
        '狗' => ['宜' => ['虎', '兔', '马'], '忌' => ['龙', '牛', '羊']],
        '猪' => ['宜' => ['羊', '兔', '虎'], '忌' => ['蛇', '猴', '猪']]
    ];
    
    /**
     * 日主配对关系
     */
    protected $dayMasterRelations = [
        '甲' => ['合' => '己', '冲' => '庚'],
        '乙' => ['合' => '庚', '冲' => '辛'],
        '丙' => ['合' => '辛', '冲' => '壬'],
        '丁' => ['合' => '壬', '冲' => '癸'],
        '戊' => ['合' => '癸', '冲' => '甲'],
        '己' => ['合' => '甲', '冲' => '乙'],
        '庚' => ['合' => '乙', '冲' => '丙'],
        '辛' => ['合' => '丙', '冲' => '丁'],
        '壬' => ['合' => '丁', '冲' => '戊'],
        '癸' => ['合' => '戊', '冲' => '己']
    ];
    
    /**
     * 地支六合
     */
    protected $liuHe = [
        '子' => '丑', '丑' => '子',
        '寅' => '亥', '亥' => '寅',
        '卯' => '戌', '戌' => '卯',
        '辰' => '酉', '酉' => '辰',
        '巳' => '申', '申' => '巳',
        '午' => '未', '未' => '午'
    ];
    
    /**
     * 地支六冲
     */
    protected $liuChong = [
        '子' => '午', '午' => '子',
        '丑' => '未', '未' => '丑',
        '寅' => '申', '申' => '寅',
        '卯' => '酉', '酉' => '卯',
        '辰' => '戌', '戌' => '辰',
        '巳' => '亥', '亥' => '巳'
    ];
    
    /**
     * 地支三合
     */
    protected $sanHe = [
        ['申', '子', '辰'], // 水局
        ['亥', '卯', '未'], // 木局
        ['寅', '午', '戌'], // 火局
        ['巳', '酉', '丑']  // 金局
    ];
    
    /**
     * 地支三会
     */
    protected $sanHui = [
        ['寅', '卯', '辰'], // 东方木
        ['巳', '午', '未'], // 南方火
        ['申', '酉', '戌'], // 西方金
        ['亥', '子', '丑']  // 北方水
    ];
    
    /**
     * 获取合婚定价配置
     */
    public function getPricing()
    {
        $user = $this->request->user;
        $userModel = User::find($user['sub']);
        $isVip = $userModel->is_vip ?? false;
        
        // 获取基础积分消耗
        $basePoints = ConfigService::get('points_cost_hehun', self::HEHUN_POINTS_COST);
        $exportPoints = ConfigService::get('points_cost_hehun_export', self::HEHUN_EXPORT_COST);
        
        // 检查是否新用户
        $isNewUser = HehunRecord::getUserCount($user['sub']) === 0;
        
        // 计算实际消耗（含折扣）
        $costInfo = ConfigService::calculatePointsCost('hehun', $basePoints, $isNewUser);
        
        return $this->success([
            'tier' => [
                'free' => [
                    'name' => '免费预览',
                    'price' => 0,
                    'features' => [
                        '基础合婚分数（总分）',
                        '简单等级评定',
                        '1条基础建议',
                        '双方八字展示'
                    ],
                    'is_available' => true
                ],
                'premium' => [
                    'name' => '详细报告',
                    'price' => $costInfo['final'],
                    'original_price' => $costInfo['original'],
                    'discount' => $costInfo['discount'] > 0 ? [
                        'percent' => $costInfo['discount'],
                        'reason' => $costInfo['reason'],
                        'saved' => $costInfo['saved']
                    ] : null,
                    'features' => [
                        '五维度详细评分',
                        'AI深度分析',
                        '专业化解方案',
                        '流年合婚分析',
                        '大运同步分析',
                        '子女缘分析',
                        '保存历史记录'
                    ],
                    'is_available' => $isVip || $userModel->points >= $costInfo['final']
                ],
                'vip' => [
                    'name' => 'VIP特权',
                    'price' => 0,
                    'features' => [
                        '所有详细报告功能',
                        '无限次合婚分析',
                        '导出报告免费',
                        '优先AI分析',
                        '专家咨询折扣'
                    ],
                    'is_vip' => $isVip,
                    'is_available' => $isVip
                ]
            ],
            'export' => [
                'pdf_points' => $isVip ? 0 : $exportPoints,
                'image_points' => $isVip ? 0 : $exportPoints
            ],
            'user_status' => [
                'is_vip' => $isVip,
                'points' => $userModel->points,
                'is_new_user' => $isNewUser
            ]
        ]);
    }
    
    /**
     * 八字合婚计算 - 支持分层付费
     * 
     * 请求参数：
     * - maleBirthDate: 男方出生日期
     * - femaleBirthDate: 女方出生日期
     * - maleName: 男方姓名（可选）
     * - femaleName: 女方姓名（可选）
     * - tier: 层级（free/premium，默认premium）
     * - useAi: 是否使用AI分析（付费层）
     */
    public function calculate()
    {
        $data = $this->request->post();
        $user = $this->request->user;
        
        // 验证参数
        if (empty($data['maleBirthDate']) || empty($data['femaleBirthDate'])) {
            return $this->error('请提供双方出生日期');
        }
        
        $maleName = $data['maleName'] ?? '男方';
        $femaleName = $data['femaleName'] ?? '女方';
        $tier = $data['tier'] ?? self::TIER_PREMIUM; // free 或 premium
        $useAi = $data['useAi'] ?? true;
        
        // 计算双方八字（免费层也需要）
        // 使用BaziCalculationService服务类，避免直接实例化控制器
        $maleBazi = $this->baziCalculationService->calculateBazi($data['maleBirthDate']);
        $femaleBazi = $this->baziCalculationService->calculateBazi($data['femaleBirthDate']);
        
        // 执行完整合婚分析
        $hehunResult = $this->analyzeHehun($maleBazi, $femaleBazi, $maleName, $femaleName, $data['maleBirthDate'], $data['femaleBirthDate']);
        
        // 如果是免费层，只返回基础信息
        if ($tier === self::TIER_FREE) {
            return $this->success([
                'tier' => self::TIER_FREE,
                'male_bazi' => [
                    'year' => $maleBazi['year']['gan'] . $maleBazi['year']['zhi'],
                    'month' => $maleBazi['month']['gan'] . $maleBazi['month']['zhi'],
                    'day' => $maleBazi['day']['gan'] . $maleBazi['day']['zhi'],
                    'hour' => $maleBazi['hour']['gan'] . $maleBazi['hour']['zhi'],
                    'day_master' => $maleBazi['day_master']
                ],
                'female_bazi' => [
                    'year' => $femaleBazi['year']['gan'] . $femaleBazi['year']['zhi'],
                    'month' => $femaleBazi['month']['gan'] . $femaleBazi['month']['zhi'],
                    'day' => $femaleBazi['day']['gan'] . $femaleBazi['day']['zhi'],
                    'hour' => $femaleBazi['hour']['gan'] . $femaleBazi['hour']['zhi'],
                    'day_master' => $femaleBazi['day_master']
                ],
                'hehun' => [
                    'score' => $hehunResult['score'],
                    'level' => $hehunResult['level'],
                    'level_text' => $hehunResult['level_text'],
                    'comment' => $hehunResult['comment'],
                    'suggestions' => [count($hehunResult['suggestions']) > 0 ? $hehunResult['suggestions'][0] : '双方缘分尚可，建议查看详细报告了解更多。']
                ],
                'preview_hint' => '查看五维度详细分析、AI解读、化解方案等，请解锁详细报告',
                'pricing' => $this->getQuickPricing($user['sub'])
            ]);
        }
        
        // 付费层逻辑
        return $this->processPremiumHehun($data, $user, $maleBazi, $femaleBazi, $hehunResult, $maleName, $femaleName, $useAi);
    }
    
    /**
     * 处理付费层合婚
     */
    protected function processPremiumHehun(array $data, array $user, array $maleBazi, array $femaleBazi, array $hehunResult, string $maleName, string $femaleName, bool $useAi)
    {
        // AI深度分析（付费层）- 在事务外执行AI调用
        $aiAnalysis = null;
        if ($useAi) {
            $aiAnalysis = $this->generateAiAnalysis($hehunResult, $maleBazi, $femaleBazi, $maleName, $femaleName);
        }
        
        // 计算实际积分消耗（事务外计算）
        $isNewUser = HehunRecord::getUserCount($user['sub']) === 0;
        $basePoints = ConfigService::get('points_cost_hehun', self::HEHUN_POINTS_COST);
        $costInfo = ConfigService::calculatePointsCost('hehun', $basePoints, $isNewUser);
        $finalPoints = $costInfo['final'];
        
        // ===== 使用数据库行锁防止并发问题 =====
        Db::startTrans();
        try {
            // 1. 使用行锁查询用户
            $userData = Db::name('tc_user')
                ->where('id', $user['sub'])
                ->where('status', 1)
                ->lock(true)
                ->find();
            
            if (!$userData) {
                Db::rollback();
                return $this->error('用户不存在', 404);
            }
            
            $isVip = $userData['is_vip'] ?? false;
            $vipUnlockHehun = ConfigService::get('vip_unlock_hehun', true);
            
            // VIP用户免积分
            $needPoints = !($isVip && $vipUnlockHehun);
            
            // 2. 检查积分余额
            if ($needPoints && $userData['points'] < $finalPoints) {
                Db::rollback();
                return $this->error('积分不足，请先充值或开通VIP', 403, [
                    'need_points' => $finalPoints,
                    'current_points' => $userData['points'],
                    'shortage' => $finalPoints - $userData['points']
                ]);
            }
            
            // 3. 扣除积分（非VIP用户，使用原子操作）
            if ($needPoints) {
                $updateResult = Db::name('tc_user')
                    ->where('id', $user['sub'])
                    ->dec('points', $finalPoints)
                    ->update();
                
                if ($updateResult === 0) {
                    Db::rollback();
                    return $this->error('积分扣除失败，请重试');
                }
                
                // 记录积分变动
                Db::name('tc_points_record')->insert([
                    'user_id' => $user['sub'],
                    'action' => '八字合婚-详细报告',
                    'points' => -$finalPoints,
                    'type' => 'hehun',
                    'related_id' => 0,
                    'remark' => "男方: {$data['maleBirthDate']}, 女方: {$data['femaleBirthDate']}",
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
            
            // 保存合婚记录
            $record = HehunRecord::create([
                'user_id' => $user['sub'],
                'male_name' => $maleName,
                'female_name' => $femaleName,
                'male_birth_date' => $data['maleBirthDate'],
                'female_birth_date' => $data['femaleBirthDate'],
                'male_bazi' => json_encode($maleBazi),
                'female_bazi' => json_encode($femaleBazi),
                'score' => $hehunResult['score'],
                'level' => $hehunResult['level'],
                'result' => json_encode($hehunResult),
                'ai_analysis' => $aiAnalysis ? json_encode($aiAnalysis) : null,
                'is_ai_analysis' => $useAi,
                'points_cost' => $needPoints ? $finalPoints : 0,
            ]);
            
            Db::commit();
            
            // 查询最新的积分余额
            $remainingPoints = Db::name('tc_user')
                ->where('id', $user['sub'])
                ->value('points');
            
            $result = [
                'id' => $record->id,
                'tier' => $isVip ? self::TIER_VIP : self::TIER_PREMIUM,
                'male_bazi' => $maleBazi,
                'female_bazi' => $femaleBazi,
                'hehun' => $hehunResult,
                'ai_analysis' => $aiAnalysis,
                'pricing' => [
                    'points_cost' => $needPoints ? $finalPoints : 0,
                    'original_points' => $costInfo['original'],
                    'discount' => $costInfo['discount'] > 0 ? [
                        'percent' => $costInfo['discount'],
                        'reason' => $costInfo['reason'],
                        'saved' => $costInfo['saved']
                    ] : null
                ],
                'user_status' => [
                    'remaining_points' => $remainingPoints,
                    'is_vip' => $isVip,
                    'is_vip_free' => !$needPoints
                ]
            ];
            
            // 缓存结果
            $cacheKey = CacheService::hehunKey($data['maleBirthDate'], $data['femaleBirthDate']);
            if (self::ENABLE_CACHE) {
                CacheService::set($cacheKey, $result, CacheService::TTL_WEEK, CacheService::TAG_HEHUN);
            }
            
            return $this->success($result);
            
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('合婚分析失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 快速获取定价信息（用于免费层提示）
     */
    protected function getQuickPricing(int $userId): array
    {
        $userModel = User::find($userId);
        $isVip = $userModel->is_vip ?? false;
        $isNewUser = HehunRecord::getUserCount($userId) === 0;
        
        $basePoints = ConfigService::get('points_cost_hehun', self::HEHUN_POINTS_COST);
        $costInfo = ConfigService::calculatePointsCost('hehun', $basePoints, $isNewUser);
        
        return [
            'unlock_points' => $isVip ? 0 : $costInfo['final'],
            'is_vip' => $isVip,
            'discount_info' => $costInfo['discount'] > 0 ? [
                'percent' => $costInfo['discount'],
                'reason' => $costInfo['reason']
            ] : null
        ];
    }
    
    /**
     * 合婚核心分析逻辑
     */
    protected function analyzeHehun(array $maleBazi, array $femaleBazi, string $maleName, string $femaleName, string $maleBirthDate = '', string $femaleBirthDate = ''): array
    {
        $scores = [];
        $details = [];
        $suggestions = [];
        
        // 1. 年柱（生肖）配对分析 - 15分
        $yearScore = $this->analyzeYearPillar($maleBazi['year'], $femaleBazi['year']);
        $scores['year'] = $yearScore;
        $details['year'] = $this->getYearPillarDescription($yearScore);
        
        // 2. 日柱（日主）配对分析 - 30分
        $dayScore = $this->analyzeDayPillar($maleBazi['day'], $femaleBazi['day']);
        $scores['day'] = $dayScore;
        $details['day'] = $this->getDayPillarDescription($dayScore);
        
        // 3. 五行互补分析 - 20分
        $wuxingScore = $this->analyzeWuxingComplement($maleBazi, $femaleBazi);
        $scores['wuxing'] = $wuxingScore;
        $details['wuxing'] = $this->getWuxingDescription($wuxingScore);
        
        // 4. 天干地支合冲分析 - 20分
        $hechongScore = $this->analyzeHeChong($maleBazi, $femaleBazi);
        $scores['hechong'] = $hechongScore;
        $details['hechong'] = $this->getHeChongDescription($hechongScore);
        
        // 5. 纳音五行分析 - 15分
        $nayinScore = $this->analyzeNayin($maleBazi, $femaleBazi);
        $scores['nayin'] = $nayinScore;
        $details['nayin'] = $this->getNayinDescription($nayinScore);
        
        // 6. 三元合婚分析（传统合婚法）
        $sanyuanAnalysis = $this->analyzeSanYuanHehun($maleBazi, $femaleBazi, $maleBirthDate, $femaleBirthDate);
        
        // 7. 九宫合婚分析（传统合婚法）
        $jiugongAnalysis = $this->analyzeJiuGongHehun($maleBazi, $femaleBazi, $maleBirthDate, $femaleBirthDate);

        
        // 计算总分
        $totalScore = array_sum($scores);
        
        // 确定等级
        $level = $this->calculateLevel($totalScore);
        
        // 生成建议
        $suggestions = $this->generateSuggestions($scores, $maleBazi, $femaleBazi);
        
        // 生成综合评语
        $comment = $this->generateComment($totalScore, $level, $scores, $maleName, $femaleName);
        
        return [
            'score' => $totalScore,
            'max_score' => 100,
            'level' => $level,
            'level_text' => $this->getLevelText($level),
            'scores' => $scores,
            'details' => $details,
            'suggestions' => $suggestions,
            'comment' => $comment,
            'highlights' => $this->getHighlights($scores),
            'traditional_methods' => [
                'sanyuan' => $sanyuanAnalysis,
                'jiugong' => $jiugongAnalysis,
            ],
        ];
    }
    
    /**
     * 年柱（生肖）配对分析
     */
    protected function analyzeYearPillar(array $maleYear, array $femaleYear): int
    {
        // 获取生肖
        $shengxiao = ['鼠', '牛', '虎', '兔', '龙', '蛇', '马', '羊', '猴', '鸡', '狗', '猪'];
        $maleSx = $shengxiao[$maleYear['zhi_index']];
        $femaleSx = $shengxiao[$femaleYear['zhi_index']];
        
        // 检查生肖配对
        $match = $this->shengxiaoMatch[$maleSx] ?? null;
        if (!$match) return 8;
        
        if (in_array($femaleSx, $match['宜'])) {
            return 15; // 上等婚配
        } elseif (in_array($femaleSx, $match['忌'])) {
            return 5;  // 下等婚配
        }
        
        return 10; // 中等婚配
    }
    
    /**
     * 获取年柱配对描述
     */
    protected function getYearPillarDescription(int $score): string
    {
        if ($score >= 15) return '生肖相合，属相婚配大吉，家庭和睦，子女兴旺。';
        if ($score >= 10) return '生肖无明显冲突，属相婚配一般，需其他因素补救。';
        return '生肖有冲，属相婚配需谨慎，建议通过其他方式化解。';
    }
    
    /**
     * 日柱（日主）配对分析
     */
    protected function analyzeDayPillar(array $maleDay, array $femaleDay): int
    {
        $maleDayGan = $maleDay['gan'];
        $femaleDayGan = $femaleDay['gan'];
        $maleDayZhi = $maleDay['zhi'];
        $femaleDayZhi = $femaleDay['zhi'];
        
        $score = 0;
        
        // 天干合化（如甲己合）
        $maleRelation = $this->dayMasterRelations[$maleDayGan] ?? null;
        if ($maleRelation) {
            if ($femaleDayGan === $maleRelation['合']) {
                $score += 15; // 天干相合
            } elseif ($femaleDayGan === $maleRelation['冲']) {
                $score -= 5; // 天干相冲
            }
        }
        
        // 地支六合
        if (isset($this->liuHe[$maleDayZhi]) && $this->liuHe[$maleDayZhi] === $femaleDayZhi) {
            $score += 10;
        }
        
        // 地支六冲
        if (isset($this->liuChong[$maleDayZhi]) && $this->liuChong[$maleDayZhi] === $femaleDayZhi) {
            $score -= 10;
        }
        
        // 五行相生
        $maleWuxing = $this->ganWuXing[$maleDayGan];
        $femaleWuxing = $this->ganWuXing[$femaleDayGan];
        
        $shengRelation = ['木' => '火', '火' => '土', '土' => '金', '金' => '水', '水' => '木'];
        if ($shengRelation[$maleWuxing] === $femaleWuxing) {
            $score += 8; // 男生日主生女方
        } elseif ($shengRelation[$femaleWuxing] === $maleWuxing) {
            $score += 5; // 女方日主生男方
        }
        
        return max(0, min(30, 15 + $score)); // 基础分15 + 加减分，最高30
    }
    
    /**
     * 获取日柱配对描述
     */
    protected function getDayPillarDescription(int $score): string
    {
        if ($score >= 25) return '日主相合，夫妻情深，相互扶持，白头偕老之象。';
        if ($score >= 20) return '日主相生，性格互补，感情融洽，婚姻稳定。';
        if ($score >= 15) return '日主无明显冲突，需相互包容理解。';
        if ($score >= 10) return '日主略有冲克，需注意沟通方式，避免争执。';
        return '日主相冲，婚姻需格外用心经营，建议晚婚或找化解之法。';
    }
    
    /**
     * 五行互补分析
     */
    protected function analyzeWuxingComplement(array $maleBazi, array $femaleBazi): int
    {
        $maleStats = $maleBazi['wuxing_stats'];
        $femaleStats = $femaleBazi['wuxing_stats'];
        
        $score = 10; // 基础分
        
        // 检查双方五行是否互补
        foreach ($maleStats as $element => $count) {
            // 男方旺的，女方弱则为互补
            if ($count >= 3 && $femaleStats[$element] <= 1) {
                $score -= 2; // 双方都旺同一五行，减分
            }
            // 男方弱的，女方旺则为互补
            if ($count <= 1 && $femaleStats[$element] >= 3) {
                $score += 3; // 互补，加分
            }
        }
        
        // 检查日主五行关系
        $maleDayWuxing = $maleBazi['day_master_wuxing'];
        $femaleDayWuxing = $femaleBazi['day_master_wuxing'];
        
        $shengRelation = ['木' => '火', '火' => '土', '土' => '金', '金' => '水', '水' => '木'];
        $keRelation = ['木' => '土', '土' => '水', '水' => '火', '火' => '金', '金' => '木'];
        
        if ($shengRelation[$maleDayWuxing] === $femaleDayWuxing) {
            $score += 5; // 相生
        } elseif ($keRelation[$maleDayWuxing] === $femaleDayWuxing) {
            $score -= 3; // 相克
        }
        
        return max(0, min(20, $score));
    }
    
    /**
     * 获取五行互补描述
     */
    protected function getWuxingDescription(int $score): string
    {
        if ($score >= 18) return '五行互补极佳，双方命局相互补益，婚后运势共同提升。';
        if ($score >= 15) return '五行互补良好，双方能够相互支持，共同进步。';
        if ($score >= 10) return '五行互补一般，无明显冲突也无特别补益。';
        if ($score >= 5) return '五行略有冲突，需注意性格差异，相互包容。';
        return '五行冲突较多，建议通过风水调理或择吉日化解。';
    }
    
    /**
     * 天干地支合冲分析
     */
    protected function analyzeHeChong(array $maleBazi, array $femaleBazi): int
    {
        $score = 10; // 基础分
        $heCount = 0;
        $chongCount = 0;
        
        // 检查所有柱的合冲
        $pillars = ['year', 'month', 'day', 'hour'];
        
        foreach ($pillars as $pillar) {
            $maleZhi = $maleBazi[$pillar]['zhi'];
            $femaleZhi = $femaleBazi[$pillar]['zhi'];
            
            // 六合
            if (isset($this->liuHe[$maleZhi]) && $this->liuHe[$maleZhi] === $femaleZhi) {
                $heCount++;
                $score += 3;
            }
            
            // 六冲
            if (isset($this->liuChong[$maleZhi]) && $this->liuChong[$maleZhi] === $femaleZhi) {
                $chongCount++;
                $score -= 3;
            }
        }
        
        // 三合局检查
        $allZhi = [
            $maleBazi['year']['zhi'], $maleBazi['month']['zhi'],
            $maleBazi['day']['zhi'], $maleBazi['hour']['zhi'],
            $femaleBazi['year']['zhi'], $femaleBazi['month']['zhi'],
            $femaleBazi['day']['zhi'], $femaleBazi['hour']['zhi']
        ];
        
        foreach ($this->sanHe as $sanhe) {
            $matchCount = count(array_intersect($allZhi, $sanhe));
            if ($matchCount >= 3) {
                $score += 5; // 形成三合局
            } elseif ($matchCount === 2) {
                $score += 2; // 半合
            }
        }
        
        return max(0, min(20, $score));
    }
    
    /**
     * 获取合冲描述
     */
    protected function getHeChongDescription(int $score): string
    {
        if ($score >= 18) return '干支多合少冲，缘分深厚，相处融洽，遇事能相互体谅。';
        if ($score >= 15) return '干支合多于冲，感情基础好，偶有分歧也能化解。';
        if ($score >= 10) return '干支合冲平衡，感情有甜有苦，需要用心经营。';
        if ($score >= 5) return '干支冲多于合，性格差异大，需要更多包容理解。';
        return '干支多冲，容易产生矛盾，建议找专业命理师化解。';
    }
    
    /**
     * 纳音五行分析
     */
    protected function analyzeNayin(array $maleBazi, array $femaleBazi): int
    {
        $score = 8; // 基础分
        
        // 纳音五行相生相克
        $nayinWuxing = [
            '海中金' => '金', '剑锋金' => '金', '白蜡金' => '金', '沙中金' => '金',
            '金箔金' => '金', '钗钏金' => '金', '石榴木' => '木', '大林木' => '木',
            '杨柳木' => '木', '松柏木' => '木', '平地木' => '木', '桑柘木' => '木',
            '长流水' => '水', '天河水' => '水', '涧下水' => '水', '大溪水' => '水',
            '大海水' => '水', '泉中水' => '水', '炉中火' => '火', '山头火' => '火',
            '霹雳火' => '火', '山下火' => '火', '覆灯火' => '火', '天上火' => '火',
            '大驿土' => '土', '城头土' => '土', '屋上土' => '土', '路旁土' => '土',
            '壁上土' => '土', '沙中土' => '土'
        ];
        
        $pillars = ['year', 'month', 'day', 'hour'];
        foreach ($pillars as $pillar) {
            $maleNayin = $maleBazi[$pillar]['nayin'] ?? '';
            $femaleNayin = $femaleBazi[$pillar]['nayin'] ?? '';
            
            if (empty($maleNayin) || empty($femaleNayin)) continue;
            
            $maleNyWx = $nayinWuxing[$maleNayin] ?? '';
            $femaleNyWx = $nayinWuxing[$femaleNayin] ?? '';
            
            $shengRelation = ['木' => '火', '火' => '土', '土' => '金', '金' => '水', '水' => '木'];
            
            if ($shengRelation[$maleNyWx] === $femaleNyWx) {
                $score += 2;
            }
        }
        
        return max(0, min(15, $score));
    }
    
    /**
     * 获取纳音描述
     */
    protected function getNayinDescription(int $score): string
    {
        if ($score >= 13) return '纳音相生，命理契合度高，夫妻同心，家业兴旺。';
        if ($score >= 10) return '纳音和谐，命理配合良好，生活比较顺遂。';
        if ($score >= 7) return '纳音一般，命理无明显冲突，平淡是福。';
        return '纳音略有冲突，生活中可能有些小波折。';
    }
    
    /**
     * 计算等级
     */
    protected function calculateLevel(int $score): string
    {
        if ($score >= 85) return 'excellent';
        if ($score >= 70) return 'good';
        if ($score >= 55) return 'medium';
        if ($score >= 40) return 'fair';
        return 'poor';
    }
    
    /**
     * 获取等级文本
     */
    protected function getLevelText(string $level): string
    {
        $texts = [
            'excellent' => '天作之合',
            'good' => '佳偶天成',
            'medium' => '中等婚配',
            'fair' => '需加经营',
            'poor' => '谨慎考虑'
        ];
        return $texts[$level] ?? '未知';
    }
    
    /**
     * 生成建议
     */
    protected function generateSuggestions(array $scores, array $maleBazi, array $femaleBazi): array
    {
        $suggestions = [];
        
        // 根据各项得分生成建议
        if ($scores['year'] < 10) {
            $suggestions[] = '生肖略有相冲，建议选择吉日成婚，或佩戴相合生肖饰品化解。';
        }
        
        if ($scores['day'] < 15) {
            $suggestions[] = '日柱需要调和，婚后要注意沟通方式，遇事多商量。';
        }
        
        if ($scores['wuxing'] < 10) {
            $suggestions[] = '五行不够互补，可通过家居风水、职业选择等方式调整。';
        }
        
        if ($scores['hechong'] < 10) {
            $suggestions[] = '干支冲克较多，建议晚婚或找专业命理师择吉日化解。';
        }
        
        if (empty($suggestions)) {
            $suggestions[] = '八字配合良好，珍惜缘分，用心经营婚姻。';
            $suggestions[] = '建议互相尊重、包容理解，共同创造美好未来。';
        }
        
        return $suggestions;
    }
    
    /**
     * 生成综合评语
     */
    protected function generateComment(int $score, string $level, array $scores, string $maleName, string $femaleName): string
    {
        $levelComments = [
            'excellent' => "{$maleName}与{$femaleName}的八字配合极佳，乃是难得的天作之合。双方命理高度契合，婚后生活和谐美满，事业家庭双丰收。",
            'good' => "{$maleName}与{$femaleName}的八字配合良好，属于上等婚配。双方性格互补，感情基础稳固，只要用心经营，必能白头偕老。",
            'medium' => "{$maleName}与{$femaleName}的八字配合中等，属于普通婚配。双方有一定缘分，但需要相互包容理解，共同面对生活中的挑战。",
            'fair' => "{$maleName}与{$femaleName}的八字配合一般，需要多加经营。建议婚前多了解彼此，婚后多沟通，通过努力也能获得幸福。",
            'poor' => "{$maleName}与{$femaleName}的八字配合较弱，需要谨慎考虑。如决定在一起，建议找专业命理师择吉日、布置风水等方式化解。"
        ];
        
        return $levelComments[$level] ?? '八字合婚分析完成，请参考详细评分和建议。';
    }
    
    /**
     * 获取亮点
     */
    protected function getHighlights(array $scores): array
    {
        $highlights = [];
        
        if ($scores['year'] >= 12) {
            $highlights[] = ['type' => 'good', 'text' => '生肖相合'];
        }
        if ($scores['day'] >= 20) {
            $highlights[] = ['type' => 'good', 'text' => '日主相生'];
        }
        if ($scores['wuxing'] >= 15) {
            $highlights[] = ['type' => 'good', 'text' => '五行互补'];
        }
        if ($scores['hechong'] >= 15) {
            $highlights[] = ['type' => 'good', 'text' => '干支多合'];
        }
        if ($scores['year'] < 8) {
            $highlights[] = ['type' => 'warn', 'text' => '生肖相冲'];
        }
        if ($scores['day'] < 12) {
            $highlights[] = ['type' => 'warn', 'text' => '日主相克'];
        }
        
        return $highlights;
    }
    
    /**
     * AI深度分析
     * 
     * 优先使用真实AI服务，如不可用则使用规则引擎分析
     */
    protected function generateAiAnalysis(array $hehunResult, array $maleBazi, array $femaleBazi, string $maleName, string $femaleName): ?array
    {
        try {
            // 初始化AI服务
            $aiService = new AiService();
            
            // 检查AI服务是否可用
            if ($aiService->isAvailable()) {
                // 调用真实AI分析
                $aiResult = $aiService->analyzeHehun($hehunResult, $maleBazi, $femaleBazi, $maleName, $femaleName);
                
                if ($aiResult) {
                    return array_merge($aiResult, [
                        'provider' => $aiService->getProvider(),
                        'is_ai_generated' => true,
                    ]);
                }
            }
            
            // AI服务不可用，使用规则引擎分析
            return $this->generateRuleBasedAnalysis($hehunResult, $maleBazi, $femaleBazi, $maleName, $femaleName);
            
        } catch (\Exception $e) {
            \think\facade\Log::error('AI分析失败: ' . $e->getMessage());
            return $this->generateRuleBasedAnalysis($hehunResult, $maleBazi, $femaleBazi, $maleName, $femaleName);
        }
    }
    
    /**
     * 基于规则的智能分析（AI不可用时降级使用）
     */
    protected function generateRuleBasedAnalysis(array $hehunResult, array $maleBazi, array $femaleBazi, string $maleName, string $femaleName): array
    {
        return [
            'summary' => $this->generateAiSummary($hehunResult, $maleName, $femaleName),
            'personality_match' => $this->analyzePersonalityMatch($maleBazi, $femaleBazi),
            'marriage_prospect' => $this->analyzeMarriageProspect($hehunResult),
            'career_wealth' => $this->analyzeCareerWealth($maleBazi, $femaleBazi),
            'children_fate' => $this->analyzeChildrenFate($maleBazi, $femaleBazi),
            'suggestions' => $this->generateLifeSuggestions($hehunResult),
            'auspicious_info' => [
                'best_years' => $this->suggestAuspiciousYears($maleBazi, $femaleBazi),
                'auspicious_months' => ['农历二月', '农历八月', '农历十月'],
                'notes' => '避开双方生肖相冲的月份'
            ],
            'is_ai_generated' => false,
            'note' => '当前使用规则引擎分析，配置AI API后可获得更专业的解读'
        ];
    }
    
    /**
     * 构建AI提示词
     */
    protected function buildAiPrompt(array $hehunResult, array $maleBazi, array $femaleBazi, string $maleName, string $femaleName): string
    {
        $maleBaziStr = $maleBazi['year']['gan'] . $maleBazi['year']['zhi'] . ' ' .
                       $maleBazi['month']['gan'] . $maleBazi['month']['zhi'] . ' ' .
                       $maleBazi['day']['gan'] . $maleBazi['day']['zhi'] . ' ' .
                       $maleBazi['hour']['gan'] . $maleBazi['hour']['zhi'];
        
        $femaleBaziStr = $femaleBazi['year']['gan'] . $femaleBazi['year']['zhi'] . ' ' .
                         $femaleBazi['month']['gan'] . $femaleBazi['month']['zhi'] . ' ' .
                         $femaleBazi['day']['gan'] . $femaleBazi['day']['zhi'] . ' ' .
                         $femaleBazi['hour']['gan'] . $femaleBazi['hour']['zhi'];
        
        return <<<PROMPT
请作为命理专家，分析以下八字合婚：

男方：{$maleName}
八字：{$maleBaziStr}
日主：{$maleBazi['day']['gan']}（{$maleBazi['day_master_wuxing']}）

女方：{$femaleName}
八字：{$femaleBaziStr}
日主：{$femaleBazi['day']['gan']}（{$femaleBazi['day_master_wuxing']}）

合婚评分：{$hehunResult['score']}/100
等级：{$hehunResult['level_text']}

请从以下方面给出专业分析：
1. 性格契合度分析
2. 婚姻相处建议
3. 事业财运配合
4. 子女缘分析
5. 化解建议（如有冲克）

请用专业但通俗的语言，给出详细的婚姻指导建议。
PROMPT;
    }
    
    /**
     * 生成AI摘要
     */
    protected function generateAiSummary(array $hehunResult, string $maleName, string $femaleName): string
    {
        $score = $hehunResult['score'];
        $level = $hehunResult['level'];
        
        if ($level === 'excellent') {
            return "根据八字合婚分析，{$maleName}与{$femaleName}的命理契合度极高（{$score}分）。双方五行互补，日主相生，干支多合，是难得的上等婚配。婚后感情稳定，事业互助，家庭和睦，建议把握良缘。";
        } elseif ($level === 'good') {
            return "根据八字合婚分析，{$maleName}与{$femaleName}的命理配合良好（{$score}分）。双方有一定缘分基础，性格互补性较强。婚后需要相互包容理解，共同经营，必能收获美满婚姻。";
        } elseif ($level === 'medium') {
            return "根据八字合婚分析，{$maleName}与{$femaleName}的命理配合中等（{$score}分）。双方缘分尚可，但也存在一些小冲克。建议婚前多了解，婚后多沟通，通过共同努力创造幸福生活。";
        } else {
            return "根据八字合婚分析，{$maleName}与{$femaleName}的命理配合需要谨慎（{$score}分）。双方存在一些冲克因素，建议慎重考虑。如决定在一起，可通过择吉日、风水调理等方式化解。";
        }
    }
    
    /**
     * 分析性格契合度
     */
    protected function analyzePersonalityMatch(array $maleBazi, array $femaleBazi): array
    {
        $maleDayMaster = $maleBazi['day']['gan'];
        $femaleDayMaster = $femaleBazi['day']['gan'];
        
        $personalityTraits = [
            '甲' => '仁慈正直，有领导力，但有时固执',
            '乙' => '温和柔顺，善于变通，但容易优柔寡断',
            '丙' => '热情开朗，光明磊落，但容易急躁',
            '丁' => '温和内敛，心思细腻，但容易多虑',
            '戊' => '稳重踏实，诚信可靠，但有时保守',
            '己' => '包容大度，善于协调，但容易迁就',
            '庚' => '果断刚毅，正义感强，但容易刚愎',
            '辛' => '聪明伶俐，追求完美，但容易挑剔',
            '壬' => '智慧灵活，善于交际，但容易多变',
            '癸' => '温柔细腻，富有想象力，但容易敏感'
        ];
        
        return [
            'male_personality' => $personalityTraits[$maleDayMaster] ?? '性格独特',
            'female_personality' => $personalityTraits[$femaleDayMaster] ?? '性格独特',
            'match_analysis' => $this->getPersonalityMatchAnalysis($maleDayMaster, $femaleDayMaster)
        ];
    }
    
    /**
     * 获取性格匹配分析
     */
    protected function getPersonalityMatchAnalysis(string $male, string $female): string
    {
        $relation = $this->dayMasterRelations[$male] ?? null;
        if (!$relation) return '双方性格需要相互了解磨合。';
        
        if ($female === $relation['合']) {
            return '男方日主与女方日主相合，性格互补性强，相处融洽，能够相互理解和包容。';
        } elseif ($female === $relation['冲']) {
            return '男方日主与女方日主相冲，性格差异较大，需要更多沟通和包容。';
        }
        
        return '双方性格有一定差异，但只要互相尊重、理解包容，也能相处融洽。';
    }
    
    /**
     * 生成生活建议
     */
    protected function generateLifeSuggestions(array $hehunResult): array
    {
        $suggestions = [];
        
        if ($hehunResult['level'] === 'excellent' || $hehunResult['level'] === 'good') {
            $suggestions[] = '你们的命理配合良好，婚后生活会比较顺遂，要珍惜这份缘分。';
            $suggestions[] = '建议在事业规划上相互支持，共同制定家庭目标。';
            $suggestions[] = '日常生活中保持沟通，遇到问题共同面对。';
        } elseif ($hehunResult['level'] === 'medium') {
            $suggestions[] = '婚后要注意沟通方式，遇事多商量，避免独断专行。';
            $suggestions[] = '建议培养共同兴趣爱好，增进感情。';
            $suggestions[] = '财务管理上要透明公开，避免产生误会。';
        } else {
            $suggestions[] = '建议婚前多了解彼此，充分沟通对未来的规划。';
            $suggestions[] = '婚后要格外用心经营，遇到分歧要冷静处理。';
            $suggestions[] = '可寻求专业婚姻咨询师的帮助，学习有效的沟通技巧。';
        }
        
        return $suggestions;
    }
    
    /**
     * 建议吉日
     */
    protected function suggestAuspiciousDates(): array
    {
        // 返回未来几个月的吉日建议（简化版）
        $months = ['本月', '次月', '第三个月'];
        $dates = [];
        
        foreach ($months as $i => $month) {
            $dates[] = [
                'period' => $month,
                'suggestion' => '建议选择双数日或带有6、8的日子，避开双方生肖相冲的日子。'
            ];
        }
        
        return $dates;
    }
    
    /**
     * 分析婚姻前景
     */
    protected function analyzeMarriageProspect(array $hehunResult): string
    {
        $score = $hehunResult['score'];
        $level = $hehunResult['level'];
        
        if ($level === 'excellent') {
            return '双方八字高度契合，婚姻基础稳固。婚后感情和谐，能够相互扶持，白头偕老的可能性极高。建议把握良缘，用心经营。';
        } elseif ($level === 'good') {
            return '八字配合良好，婚姻前景乐观。双方性格互补，能够相互理解和包容。婚后生活会比较顺遂，偶有分歧也能妥善化解。';
        } elseif ($level === 'medium') {
            return '八字配合中等，婚姻需要双方共同努力。虽然有一定缘分基础，但也存在一些小冲克。建议婚前多了解，婚后多沟通，共同面对挑战。';
        } else {
            return '八字配合一般，婚姻需要格外用心经营。双方存在一些冲克因素，建议慎重考虑。如决定在一起，可通过择吉日、风水调理等方式化解。';
        }
    }
    
    /**
     * 分析事业财运配合
     */
    protected function analyzeCareerWealth(array $maleBazi, array $femaleBazi): string
    {
        $maleDayMaster = $maleBazi['day_master_wuxing'];
        $femaleDayMaster = $femaleBazi['day_master_wuxing'];
        
        $shengRelation = ['木' => '火', '火' => '土', '土' => '金', '金' => '水', '水' => '木'];
        
        if ($shengRelation[$maleDayMaster] === $femaleDayMaster) {
            return '男方日主生女方，事业财运上男方对女方有助益。适合共同创业或男方主导事业、女方辅助管理的模式。财运方面能够相互促进，共同积累财富。';
        } elseif ($shengRelation[$femaleDayMaster] === $maleDayMaster) {
            return '女方日主生男方，事业财运上女方对男方有助益。适合女方在幕后支持，男方在前台发展的模式。财运稳定，女方理财能力较强。';
        } else {
            return '双方日主五行关系为比劫或相克，事业财运上需要各自独立发展。建议保持经济独立，共同制定家庭财务规划，避免因金钱产生矛盾。';
        }
    }
    
    /**
     * 分析子女缘
     */
    protected function analyzeChildrenFate(array $maleBazi, array $femaleBazi): string
    {
        $maleStats = $maleBazi['wuxing_stats'];
        $femaleStats = $femaleBazi['wuxing_stats'];
        
        $analysis = '根据双方八字分析，';
        
        if ($maleStats['水'] >= 1 && $femaleStats['木'] >= 1) {
            $analysis .= '子女缘分较好，有望生育健康聪明的子女。建议顺其自然，不必过于焦虑。';
        } else {
            $analysis .= '子女缘分中等，可能需要一些时间。建议保持良好心态，注意身体健康。';
        }
        
        return $analysis;
    }
    
    /**
     * 建议适合结婚的年份
     */
    protected function suggestAuspiciousYears(array $maleBazi, array $femaleBazi): array
    {
        $currentYear = (int)date('Y');
        $years = [];
        
        for ($i = 1; $i <= 5; $i++) {
            $years[] = ($currentYear + $i) . '年（建议择吉日）';
        }
        
        return $years;
    }
    
    /**
     * 处理缓存结果
     */
    protected function processCachedHehun(array $cachedResult, array $user, array $data, int $pointsCost, bool $needPoints)
    {
        $userModel = User::find($user['sub']);
        
        // 扣除积分（非VIP用户）
        if ($needPoints) {
            if ($userModel->points < $pointsCost) {
                return $this->error('积分不足，请先充值或开通VIP', 403);
            }
            
            Db::startTrans();
            try {
                $userModel->deductPoints($pointsCost);
                
                PointsRecord::record(
                    $user['sub'],
                    '八字合婚消耗',
                    -$pointsCost,
                    'hehun',
                    0,
                    "男方: {$data['maleBirthDate']}, 女方: {$data['femaleBirthDate']} (使用缓存)"
                );
                
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                return $this->error('处理失败: ' . $e->getMessage());
            }
        }
        
        $result = $cachedResult;
        $result['points_cost'] = $needPoints ? $pointsCost : 0;
        $result['remaining_points'] = $userModel->points;
        $result['is_vip_free'] = !$needPoints;
        $result['from_cache'] = true;
        
        return $this->success($result);
    }
    
    /**
     * 获取合婚历史
     */
    public function history()
    {
        $user = $this->request->user;
        $limit = $this->request->get('limit', 20);
        
        $history = HehunRecord::where('user_id', $user['sub'])
            ->order('create_time', 'desc')
            ->limit((int)$limit)
            ->select();
        
        return $this->success($history);
    }
    
    /**
     * 导出合婚报告
     * 
     * 支持格式：pdf（专业报告）、image（分享长图）
     */
    public function export()
    {
        $data = $this->request->post();
        $user = $this->request->user;
        
        // 验证参数
        if (empty($data['record_id'])) {
            return $this->error('请提供合婚记录ID');
        }
        
        $format = $data['format'] ?? 'pdf';
        $template = $data['template'] ?? 'default';
        
        // 获取合婚记录
        $record = HehunRecord::where('id', $data['record_id'])
            ->where('user_id', $user['sub'])
            ->find();
        
        if (!$record) {
            return $this->error('合婚记录不存在', 404);
        }
        
        // 检查用户VIP状态
        $userModel = User::find($user['sub']);
        $isVip = $userModel->is_vip ?? false;
        
        // 计算导出积分消耗
        $exportPoints = ConfigService::get('points_cost_hehun_export', self::HEHUN_EXPORT_COST);
        $needPoints = !$isVip;
        
        if ($needPoints && $userModel->points < $exportPoints) {
            return $this->error('积分不足，导出报告需要 ' . $exportPoints . ' 积分', 403);
        }
        
        // 生成报告
        try {
            $reportData = [
                'male_name' => $record->male_name,
                'female_name' => $record->female_name,
                'male_bazi' => json_decode($record->male_bazi, true),
                'female_bazi' => json_decode($record->female_bazi, true),
                'hehun' => json_decode($record->result, true),
                'ai_analysis' => $record->ai_analysis ? json_decode($record->ai_analysis, true) : null,
                'score' => $record->score,
                'level' => $record->level,
                'created_at' => $record->create_time,
                'template' => $template
            ];
            
            // 生成报告文件
            $exportResult = $this->generateReport($reportData, $format);
            
            if (!$exportResult['success']) {
                return $this->error('报告生成失败: ' . $exportResult['message']);
            }
            
            // 扣除积分（非VIP）
            if ($needPoints) {
                $userModel->deductPoints($exportPoints);
                
                PointsRecord::record(
                    $user['sub'],
                    '导出合婚报告',
                    -$exportPoints,
                    'hehun_export',
                    $record->id,
                    "格式: {$format}, 记录ID: {$record->id}"
                );
            }
            
            return $this->success([
                'record_id' => $record->id,
                'format' => $format,
                'template' => $template,
                'download_url' => $exportResult['url'],
                'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days')),
                'points_cost' => $needPoints ? $exportPoints : 0,
                'remaining_points' => $userModel->points
            ]);
            
        } catch (\Exception $e) {
            return $this->error('报告生成失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 生成报告文件
     */
    protected function generateReport(array $data, string $format): array
    {
        // 生成安全的文件名：只允许字母数字和下划线
        $hash = md5(serialize($data));
        $timestamp = time();
        $filename = 'hehun_' . preg_replace('/[^a-zA-Z0-9_]/', '', $hash) . '_' . (int)$timestamp;
        
        // 验证并规范化存储路径
        $publicPath = realpath(public_path());
        $storagePath = $publicPath . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'hehun' . DIRECTORY_SEPARATOR;
        
        // 防止路径遍历：确保生成的路径在public目录内
        $realStoragePath = realpath(dirname($storagePath)) ?: $publicPath;
        if (strpos($realStoragePath, $publicPath) !== 0) {
            return [
                'success' => false,
                'url' => '',
                'message' => '存储路径无效'
            ];
        }
        
        // 确保目录存在
        if (!is_dir($storagePath)) {
            mkdir($storagePath, 0755, true);
        }
        
        // 生成HTML报告内容
        $htmlContent = $this->buildReportHtml($data);
        
        if ($format === 'pdf') {
            // 保存HTML文件，用户可以用浏览器打印为PDF
            $filePath = $storagePath . $filename . '.html';
            file_put_contents($filePath, $htmlContent);
            
            // 生成可访问的URL
            $baseUrl = request()->scheme() . '://' . request()->host();
            $url = $baseUrl . '/storage/hehun/' . $filename . '.html';
            
            return [
                'success' => true,
                'url' => $url,
                'message' => '报告生成成功，请在浏览器中打开并选择"打印为PDF"'
            ];
        } else {
            // 生成图片格式（使用HTML截图的替代方案）
            $filePath = $storagePath . $filename . '.html';
            file_put_contents($filePath, $htmlContent);
            
            $baseUrl = request()->scheme() . '://' . request()->host();
            $url = $baseUrl . '/storage/hehun/' . $filename . '.html';
            
            return [
                'success' => true,
                'url' => $url,
                'message' => '报告生成成功，请截图保存'
            ];
        }
    }
    
    /**
     * 构建报告HTML内容
     */
    protected function buildReportHtml(array $data): string
    {
        $male = $data['male'] ?? [];
        $female = $data['female'] ?? [];
        $result = $data['result'] ?? [];
        $analysis = $data['analysis'] ?? [];
        $score = $data['score'] ?? 0;
        $grade = $data['grade'] ?? '中等';
        $reportTitle = $data['report_title'] ?? '八字合婚分析报告';
        $generatedAt = date('Y年m月d日 H:i');
        
        $maleName = htmlspecialchars($male['name'] ?? '男方', ENT_QUOTES, 'UTF-8');
        $femaleName = htmlspecialchars($female['name'] ?? '女方', ENT_QUOTES, 'UTF-8');
        
        // 转义八字数据
        $maleYear = htmlspecialchars($male['year'] ?? '', ENT_QUOTES, 'UTF-8');
        $maleMonth = htmlspecialchars($male['month'] ?? '', ENT_QUOTES, 'UTF-8');
        $maleDay = htmlspecialchars($male['day'] ?? '', ENT_QUOTES, 'UTF-8');
        $maleHour = htmlspecialchars($male['hour'] ?? '', ENT_QUOTES, 'UTF-8');
        $femaleYear = htmlspecialchars($female['year'] ?? '', ENT_QUOTES, 'UTF-8');
        $femaleMonth = htmlspecialchars($female['month'] ?? '', ENT_QUOTES, 'UTF-8');
        $femaleDay = htmlspecialchars($female['day'] ?? '', ENT_QUOTES, 'UTF-8');
        $femaleHour = htmlspecialchars($female['hour'] ?? '', ENT_QUOTES, 'UTF-8');
        
        // 转义其他输出
        $safeReportTitle = htmlspecialchars($reportTitle, ENT_QUOTES, 'UTF-8');
        $safeGrade = htmlspecialchars($grade, ENT_QUOTES, 'UTF-8');
        
        // 五维度评分
        $dimensions = $result['dimensions'] ?? [];
        $wuxingScore = (int)($dimensions['wuxing'] ?? 0);
        $shengxiaoScore = (int)($dimensions['shengxiao'] ?? 0);
        $riyuanScore = (int)($dimensions['riyuan'] ?? 0);
        $tianmingScore = (int)($dimensions['tianming'] ?? 0);
        $dayunScore = (int)($dimensions['dayun'] ?? 0);
        
        // 转义分数防止XSS
        $safeScore = (int)$score;
        
        // 建议
        $suggestions = $result['suggestions'] ?? [];
        $suggestionsHtml = '';
        foreach ($suggestions as $i => $suggestion) {
            $num = $i + 1;
            $safeSuggestion = htmlspecialchars((string)$suggestion, ENT_QUOTES, 'UTF-8');
            $suggestionsHtml .= "<div class='suggestion-item'><span class='suggestion-num'>{$num}</span>{$safeSuggestion}</div>";
        }
        
        // 流年分析
        $liunian = $analysis['liunian'] ?? [];
        $liunianHtml = '';
        if (!empty($liunian)) {
            $liunianHtml = "<div class='section'><h3>🗓️ 未来三年流年运势</h3>";
            foreach ($liunian as $year => $yearData) {
                $yearDesc = htmlspecialchars($yearData['description'] ?? '', ENT_QUOTES, 'UTF-8');
                $yearLuck = htmlspecialchars($yearData['fortune'] ?? '平', ENT_QUOTES, 'UTF-8');
                $safeYear = htmlspecialchars((string)$year, ENT_QUOTES, 'UTF-8');
                $luckClass = $yearLuck === '吉' ? 'luck-good' : ($yearLuck === '凶' ? 'luck-bad' : 'luck-neutral');
                $liunianHtml .= "<div class='liunian-item'><span class='year-tag'>{$safeYear}年</span><span class='luck-badge {$luckClass}'>{$yearLuck}</span><span>{$yearDesc}</span></div>";
            }
            $liunianHtml .= "</div>";
        }
        
        // 化解方案
        $huajie = $analysis['huajie'] ?? [];
        $huajieHtml = '';
        if (!empty($huajie)) {
            $huajieHtml = "<div class='section'><h3>🔮 化解方案</h3>";
            foreach ($huajie as $h) {
                $safeTitle = htmlspecialchars($h['title'] ?? '', ENT_QUOTES, 'UTF-8');
                $safeContent = htmlspecialchars($h['content'] ?? '', ENT_QUOTES, 'UTF-8');
                $huajieHtml .= "<div class='huajie-item'><strong>{$safeTitle}</strong><p>{$safeContent}</p></div>";
            }
            $huajieHtml .= "</div>";
        }
        
        // AI深度分析 - 转义防止XSS
        $safeAiAnalysis = htmlspecialchars($analysis['ai_analysis'] ?? '暂无详细分析', ENT_QUOTES, 'UTF-8');
        
        return <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$safeReportTitle}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'PingFang SC', 'Microsoft YaHei', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .header .subtitle { opacity: 0.9; font-size: 14px; }
        .header .datetime { margin-top: 10px; font-size: 12px; opacity: 0.8; }
        .content { padding: 30px; }
        .couple-info {
            display: flex;
            justify-content: space-around;
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
        }
        .person-card { text-align: center; }
        .person-card .gender { font-size: 32px; margin-bottom: 5px; }
        .person-card .name { font-size: 18px; font-weight: bold; color: #333; }
        .person-card .bazi { font-size: 14px; color: #666; margin-top: 5px; }
        .score-section {
            text-align: center;
            margin: 30px 0;
            padding: 30px;
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            border-radius: 16px;
            color: white;
        }
        .score-value { font-size: 64px; font-weight: bold; }
        .score-label { font-size: 18px; margin-top: 10px; }
        .grade-badge {
            display: inline-block;
            padding: 8px 24px;
            background: white;
            color: #f5576c;
            border-radius: 20px;
            font-weight: bold;
            margin-top: 15px;
        }
        .section { margin: 25px 0; }
        .section h3 {
            color: #667eea;
            font-size: 18px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e0e0e0;
        }
        .dimensions {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 10px;
            margin: 20px 0;
        }
        .dimension-item {
            text-align: center;
            padding: 15px 10px;
            background: #f8f9fa;
            border-radius: 10px;
        }
        .dimension-item .dim-score { font-size: 24px; font-weight: bold; color: #667eea; }
        .dimension-item .dim-name { font-size: 12px; color: #666; margin-top: 5px; }
        .suggestion-item {
            display: flex;
            align-items: flex-start;
            padding: 15px;
            margin: 10px 0;
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            border-radius: 8px;
        }
        .suggestion-num {
            width: 24px;
            height: 24px;
            background: #ffc107;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
            margin-right: 12px;
            flex-shrink: 0;
        }
        .liunian-item {
            display: flex;
            align-items: center;
            padding: 12px;
            margin: 8px 0;
            background: #e3f2fd;
            border-radius: 8px;
        }
        .year-tag {
            background: #2196f3;
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            margin-right: 10px;
        }
        .luck-badge {
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            margin-right: 10px;
        }
        .luck-good { background: #4caf50; color: white; }
        .luck-bad { background: #f44336; color: white; }
        .luck-neutral { background: #9e9e9e; color: white; }
        .huajie-item {
            padding: 15px;
            margin: 10px 0;
            background: #f3e5f5;
            border-radius: 8px;
        }
        .huajie-item strong { color: #7b1fa2; }
        .huajie-item p { margin-top: 8px; color: #555; line-height: 1.6; }
        .analysis-content {
            line-height: 1.8;
            color: #444;
            text-align: justify;
        }
        .footer {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            color: #999;
            font-size: 12px;
        }
        @media print {
            body { background: white; }
            .container { box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>八字合婚分析报告</h1>
            <div class="subtitle">太初命理 · 专业合婚分析</div>
            <div class="datetime">生成时间：{$generatedAt}</div>
        </div>
        
        <div class="content">
            <div class="couple-info">
                <div class="person-card">
                    <div class="gender">👨</div>
                    <div class="name">{$maleName}</div>
                    <div class="bazi">{$maleYear} {$maleMonth} {$maleDay} {$maleHour}</div>
                </div>
                <div style="display:flex;align-items:center;font-size:24px;color:#667eea;">💕</div>
                <div class="person-card">
                    <div class="gender">👩</div>
                    <div class="name">{$femaleName}</div>
                    <div class="bazi">{$femaleYear} {$femaleMonth} {$femaleDay} {$femaleHour}</div>
                </div>
            </div>
            
            <div class="score-section">
                <div class="score-value">{$safeScore}</div>
                <div class="score-label">综合匹配分数</div>
                <div class="grade-badge">{$safeGrade}</div>
            </div>
            
            <div class="section">
                <h3>📊 五维度详细评分</h3>
                <div class="dimensions">
                    <div class="dimension-item">
                        <div class="dim-score">{$wuxingScore}</div>
                        <div class="dim-name">五行互补</div>
                    </div>
                    <div class="dimension-item">
                        <div class="dim-score">{$shengxiaoScore}</div>
                        <div class="dim-name">生肖配对</div>
                    </div>
                    <div class="dimension-item">
                        <div class="dim-score">{$riyuanScore}</div>
                        <div class="dim-name">日元合婚</div>
                    </div>
                    <div class="dimension-item">
                        <div class="dim-score">{$tianmingScore}</div>
                        <div class="dim-name">天命分析</div>
                    </div>
                    <div class="dimension-item">
                        <div class="dim-score">{$dayunScore}</div>
                        <div class="dim-name">大运同步</div>
                    </div>
                </div>
            </div>
            
            <div class="section">
                <h3>💡 专业建议</h3>
                {$suggestionsHtml}
            </div>
            
            {$liunianHtml}
            {$huajieHtml}
            
            <div class="section">
                <h3>📝 AI深度分析</h3>
                <div class="analysis-content">
                    {$safeAiAnalysis}
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>本报告由太初命理AI系统生成，仅供参考</p>
            <p>© 太初命理 - 探索命运的奥秘</p>
        </div>
    </div>
</body>
</html>
HTML;
    }
    
    /**
     * 三元合婚分析（传统合婚法）
     * 根据双方年命纳音五行判断婚姻吉凶
     * 
     * 三元：上元、中元、下元
     * 根据出生年份判断所属元运，再看纳音五行生克
     */
    protected function analyzeSanYuanHehun(array $maleBazi, array $femaleBazi, string $maleBirthDate, string $femaleBirthDate): array
    {
        $maleYear = (int)date('Y', strtotime($maleBirthDate));
        $femaleYear = (int)date('Y', strtotime($femaleBirthDate));
        
        // 获取纳音五行
        $maleNayin = $this->getNayinWuxing($maleBazi['year']['gan'], $maleBazi['year']['zhi']);
        $femaleNayin = $this->getNayinWuxing($femaleBazi['year']['gan'], $femaleBazi['year']['zhi']);
        
        // 判断所属元运
        $maleYuan = $this->getYuanYun($maleYear);
        $femaleYuan = $this->getYuanYun($femaleYear);
        
        // 分析纳音五行关系
        $wuxingRelation = $this->getWuxingRelation($maleNayin, $femaleNayin);
        
        // 判断是否为"命相生"
        $isXiangSheng = $wuxingRelation === '生';
        $isXiangKe = $wuxingRelation === '克';
        $isBiHe = $wuxingRelation === '比和';
        
        // 生成结果
        $result = [
            'method' => '三元合婚法',
            'male' => [
                'year' => $maleYear,
                'yuan' => $maleYuan,
                'nayin' => $maleNayin,
            ],
            'female' => [
                'year' => $femaleYear,
                'yuan' => $femaleYuan,
                'nayin' => $femaleNayin,
            ],
            'relation' => $wuxingRelation,
            'grade' => '',
            'description' => '',
            'suggestion' => '',
        ];
        
        // 判断三元合婚等级
        if ($isXiangSheng) {
            $result['grade'] = '上等婚';
            $result['description'] = "男方{$maleNayin}生女方{$femaleNayin}，为命相生之配。";
            $result['suggestion'] = '此为上等婚配，夫妻恩爱，家道昌隆，子孙兴旺。';
        } elseif ($isBiHe) {
            $result['grade'] = '中等婚';
            $result['description'] = "双方均为{$maleNayin}命，为比和之配。";
            $result['suggestion'] = '此为中等婚配，夫妻同心，但需注意相互包容。';
        } elseif ($isXiangKe) {
            $result['grade'] = '下等婚';
            $result['description'] = "男方{$maleNayin}克女方{$femaleNayin}，为命相克之配。";
            $result['suggestion'] = '此为下等婚配，建议慎重考虑，可通过择吉日、风水调理化解。';
        } else {
            $result['grade'] = '中等婚';
            $result['description'] = "男方{$maleNayin}与女方{$femaleNayin}，五行关系平平。";
            $result['suggestion'] = '此为中等婚配，婚姻需双方共同经营。';
        }
        
        // 元运分析
        if ($maleYuan === $femaleYuan) {
            $result['yuan_analysis'] = "双方同属{$maleYuan}，元运相同，气场相合，有利于婚姻稳定。";
        } else {
            $result['yuan_analysis'] = "男方属{$maleYuan}，女方属{$femaleYuan}，元运不同但无明显冲突。";
        }
        
        return $result;
    }
    
    /**
     * 九宫合婚分析（传统合婚法）
     * 根据双方出生年推算九宫命卦，判断婚姻吉凶
     */
    protected function analyzeJiuGongHehun(array $maleBazi, array $femaleBazi, string $maleBirthDate = '', string $femaleBirthDate = ''): array
    {
        $maleYear = (int)date('Y', strtotime($maleBirthDate ?: '1990-01-01'));
        $femaleYear = (int)date('Y', strtotime($femaleBirthDate ?: '1990-01-01'));
        
        // 计算命卦
        $maleGua = $this->calculateMingGua($maleYear, 'male');
        $femaleGua = $this->calculateMingGua($femaleYear, 'female');
        
        // 九宫八卦名称
        $guaNames = [
            1 => '坎水', 2 => '坤土', 3 => '震木', 4 => '巽木', 5 => '中土',
            6 => '乾金', 7 => '兑金', 8 => '艮土', 9 => '离火',
        ];
        
        // 八卦五行
        $guaWuxing = [1 => '水', 2 => '土', 3 => '木', 4 => '木', 5 => '土', 6 => '金', 7 => '金', 8 => '土', 9 => '火'];
        
        // 分析命卦关系
        $relation = $this->analyzeGuaRelation($maleGua, $femaleGua);
        
        $result = [
            'method' => '九宫合婚法（命卦合婚）',
            'male' => [
                'year' => $maleYear,
                'minggua' => $maleGua,
                'gua_name' => $guaNames[$maleGua] ?? '未知',
                'wuxing' => $guaWuxing[$maleGua] ?? '未知',
            ],
            'female' => [
                'year' => $femaleYear,
                'minggua' => $femaleGua,
                'gua_name' => $guaNames[$femaleGua] ?? '未知',
                'wuxing' => $guaWuxing[$femaleGua] ?? '未知',
            ],
            'relation' => $relation,
        ];

        
        // 根据命卦关系判断婚姻等级
        switch ($relation['type']) {
            case '生气':
                $result['grade'] = '上等婚';
                $result['description'] = "男方{$guaNames[$maleGua]}与女方{$guaNames[$femaleGua]}，{$relation['type']}之配。";
                $result['suggestion'] = '上上大吉，夫妻恩爱，子孙昌盛，家运兴隆。';
                break;
            case '天医':
                $result['grade'] = '上等婚';
                $result['description'] = "男方{$guaNames[$maleGua]}与女方{$guaNames[$femaleGua]}，{$relation['type']}之配。";
                $result['suggestion'] = '上吉，夫妻和睦，无病无灾，家庭平安。';
                break;
            case '延年':
                $result['grade'] = '上等婚';
                $result['description'] = "男方{$guaNames[$maleGua]}与女方{$guaNames[$femaleGua]}，{$relation['type']}之配。";
                $result['suggestion'] = '大吉，夫妻长寿，家业兴旺，福禄双全。';
                break;
            case '伏位':
                $result['grade'] = '中等婚';
                $result['description'] = "男方{$guaNames[$maleGua]}与女方{$guaNames[$femaleGua]}，{$relation['type']}之配。";
                $result['suggestion'] = '中平，夫妻平稳，但需相互理解包容。';
                break;
            case '六煞':
                $result['grade'] = '下等婚';
                $result['description'] = "男方{$guaNames[$maleGua]}与女方{$guaNames[$femaleGua]}，{$relation['type']}之配。";
                $result['suggestion'] = '不吉，易生口舌是非，建议化解。';
                break;
            case '祸害':
                $result['grade'] = '下等婚';
                $result['description'] = "男方{$guaNames[$maleGua]}与女方{$guaNames[$femaleGua]}，{$relation['type']}之配。";
                $result['suggestion'] = '凶，易有灾病，需谨慎考虑或化解。';
                break;
            case '五鬼':
                $result['grade'] = '下等婚';
                $result['description'] = "男方{$guaNames[$maleGua]}与女方{$guaNames[$femaleGua]}，{$relation['type']}之配。";
                $result['suggestion'] = '大凶，易有意外灾祸，强烈建议化解或慎重考虑。';
                break;
            case '绝命':
                $result['grade'] = '下等婚';
                $result['description'] = "男方{$guaNames[$maleGua]}与女方{$guaNames[$femaleGua]}，{$relation['type']}之配。";
                $result['suggestion'] = '最凶，易有重大灾祸，建议慎重考虑。';
                break;
            default:
                $result['grade'] = '中等婚';
                $result['description'] = "男方{$guaNames[$maleGua]}与女方{$guaNames[$femaleGua]}。";
                $result['suggestion'] = '中平，婚姻需双方共同努力经营。';
        }
        
        // 方位建议
        $result['direction'] = $this->getJiuGongDirection($maleGua, $femaleGua);
        
        return $result;
    }
    
    /**
     * 获取所属元运（上元、中元、下元）
     */
    protected function getYuanYun(int $year): string
    {
        // 三元九运划分
        // 上元：1864-1923，中元：1924-1983，下元：1984-2043
        // 每元60年，分三个运，每运20年
        
        $baseYear = 1864;
        $cycle = 180; // 三元共180年
        $relativeYear = ($year - $baseYear) % $cycle;
        
        if ($relativeYear < 60) {
            return '上元';
        } elseif ($relativeYear < 120) {
            return '中元';
        } else {
            return '下元';
        }
    }
    
    /**
     * 获取纳音五行
     */
    protected function getNayinWuxing(string $gan, string $zhi): string
    {
        // 纳音五行表（简化版）
        $nayinTable = [
            '甲子' => '金', '乙丑' => '金',
            '丙寅' => '火', '丁卯' => '火',
            '戊辰' => '木', '己巳' => '木',
            '庚午' => '土', '辛未' => '土',
            '壬申' => '金', '癸酉' => '金',
            '甲戌' => '火', '乙亥' => '火',
            '丙子' => '水', '丁丑' => '水',
            '戊寅' => '土', '己卯' => '土',
            '庚辰' => '金', '辛巳' => '金',
            '壬午' => '木', '癸未' => '木',
            '甲申' => '水', '乙酉' => '水',
            '丙戌' => '土', '丁亥' => '土',
            '戊子' => '火', '己丑' => '火',
            '庚寅' => '木', '辛卯' => '木',
            '壬辰' => '水', '癸巳' => '水',
            '甲午' => '金', '乙未' => '金',
            '丙申' => '火', '丁酉' => '火',
            '戊戌' => '木', '己亥' => '木',
            '庚子' => '土', '辛丑' => '土',
            '壬寅' => '金', '癸卯' => '金',
            '甲辰' => '火', '乙巳' => '火',
            '丙午' => '水', '丁未' => '水',
            '戊申' => '土', '己酉' => '土',
            '庚戌' => '金', '辛亥' => '金',
            '壬子' => '木', '癸丑' => '木',
            '甲寅' => '水', '乙卯' => '水',
            '丙辰' => '土', '丁巳' => '土',
            '戊午' => '火', '己未' => '火',
            '庚申' => '木', '辛酉' => '木',
            '壬戌' => '水', '癸亥' => '水',
        ];
        
        $key = $gan . $zhi;
        return $nayinTable[$key] ?? '未知';
    }
    
    /**
     * 获取五行关系
     */
    protected function getWuxingRelation(string $wuxing1, string $wuxing2): string
    {
        $sheng = ['木' => '火', '火' => '土', '土' => '金', '金' => '水', '水' => '木'];
        $ke = ['木' => '土', '土' => '水', '水' => '火', '火' => '金', '金' => '木'];
        
        if ($wuxing1 === $wuxing2) {
            return '比和';
        } elseif ($sheng[$wuxing1] === $wuxing2) {
            return '生';
        } elseif ($ke[$wuxing1] === $wuxing2) {
            return '克';
        } elseif ($sheng[$wuxing2] === $wuxing1) {
            return '被生';
        } elseif ($ke[$wuxing2] === $wuxing1) {
            return '被克';
        }
        
        return '无';
    }
    
    /**
     * 获取天干数字
     */
    protected function getGanNumber(string $gan): int
    {
        $numbers = ['甲' => 1, '乙' => 2, '丙' => 3, '丁' => 4, '戊' => 5, 
                    '己' => 6, '庚' => 7, '辛' => 8, '壬' => 9, '癸' => 10];
        return $numbers[$gan] ?? 1;
    }
    
    /**
     * 获取地支数字
     */
    protected function getZhiNumber(string $zhi): int
    {
        $numbers = ['子' => 1, '丑' => 2, '寅' => 3, '卯' => 4, '辰' => 5, '巳' => 6,
                    '午' => 7, '未' => 8, '申' => 9, '酉' => 10, '戌' => 11, '亥' => 12];
        return $numbers[$zhi] ?? 1;
    }
    
    /**
     * 计算命卦（三元命卦算法）
     * 
     * @param int $year 出生年份
     * @param string $gender 性别 male/female
     * @return int 命卦编号 (1-9)
     */
    protected function calculateMingGua(int $year, string $gender): int
    {
        // 1. 计算年份数字总和的模9余数
        // 例如：1985 -> (1+9+8+5) % 9 = 23 % 9 = 5
        $sum = 0;
        $tempYear = $year;
        while ($tempYear > 0) {
            $sum += $tempYear % 10;
            $tempYear = (int)($tempYear / 10);
        }
        $mod = $sum % 9;
        if ($mod === 0) $mod = 9;

        if ($gender === 'male') {
            // 男命公式：11 - mod
            $gua = 11 - $mod;
            if ($gua > 9) $gua -= 9;
            
            // 男命 5 寄 2 (坤)
            if ($gua === 5) $gua = 2;
        } else {
            // 女命公式：4 + mod
            $gua = 4 + $mod;
            if ($gua > 9) $gua -= 9;
            
            // 女命 5 寄 8 (艮)
            if ($gua === 5) $gua = 8;
        }
        
        return $gua;
    }

    
    /**
     * 分析命卦关系（八宅明镜法）
     */
    protected function analyzeGuaRelation(int $maleGua, int $femaleGua): array
    {
        // 八宅命卦关系表
        // 生气、天医、延年为上吉，伏位为中平，六煞、祸害、五鬼、绝命为凶
        $relations = [
            1 => [1 => '伏位', 2 => '绝命', 3 => '天医', 4 => '生气', 5 => '祸害', 6 => '六煞', 7 => '五鬼', 8 => '延年', 9 => '延年'],
            2 => [1 => '绝命', 2 => '伏位', 3 => '生气', 4 => '天医', 5 => '延年', 6 => '五鬼', 7 => '六煞', 8 => '祸害', 9 => '六煞'],
            3 => [1 => '天医', 2 => '生气', 3 => '伏位', 4 => '延年', 5 => '六煞', 6 => '祸害', 7 => '绝命', 8 => '五鬼', 9 => '生气'],
            4 => [1 => '生气', 2 => '天医', 3 => '延年', 4 => '伏位', 5 => '五鬼', 6 => '绝命', 7 => '祸害', 8 => '六煞', 9 => '天医'],
            5 => [1 => '祸害', 2 => '延年', 3 => '六煞', 4 => '五鬼', 5 => '伏位', 6 => '天医', 7 => '生气', 8 => '绝命', 9 => '祸害'],
            6 => [1 => '六煞', 2 => '五鬼', 3 => '祸害', 4 => '绝命', 5 => '天医', 6 => '伏位', 7 => '延年', 8 => '生气', 9 => '绝命'],
            7 => [1 => '五鬼', 2 => '六煞', 3 => '绝命', 4 => '祸害', 5 => '生气', 6 => '延年', 7 => '伏位', 8 => '天医', 9 => '五鬼'],
            8 => [1 => '延年', 2 => '祸害', 3 => '五鬼', 4 => '六煞', 5 => '绝命', 6 => '生气', 7 => '天医', 8 => '伏位', 9 => '祸害'],
            9 => [1 => '延年', 2 => '六煞', 3 => '生气', 4 => '天医', 5 => '祸害', 6 => '绝命', 7 => '五鬼', 8 => '祸害', 9 => '伏位'],
        ];

        
        $type = $relations[$maleGua][$femaleGua] ?? '未知';
        
        $meanings = [
            '生气' => '朝气蓬勃，积极向上，主家运昌隆',
            '天医' => '身体健康，无病无灾，主平安顺遂',
            '延年' => '长寿康宁，夫妻恩爱，主福禄双全',
            '伏位' => '平稳安定，细水长流，主平淡是真',
            '六煞' => '口舌是非，感情波折，需谨慎处理',
            '祸害' => '疾病灾祸，困难重重，需化解',
            '五鬼' => '意外灾祸，破财损身，大凶之象',
            '绝命' => '生死离别，家破人亡，最凶之象',
        ];
        
        return [
            'type' => $type,
            'meaning' => $meanings[$type] ?? '关系不明',
        ];
    }
    
    /**
     * 获取九宫方位建议
     */
    protected function getJiuGongDirection(int $maleGua, int $femaleGua): array
    {
        $directions = [
            1 => ['吉方' => '东南、南方', '凶方' => '西方、西北', '建议' => '卧室宜设在东南方'],
            2 => ['吉方' => '东北、西南', '凶方' => '东方、南方', '建议' => '卧室宜设在东北方'],
            3 => ['吉方' => '南方、东方', '凶方' => '西方、西北', '建议' => '卧室宜设在南方'],
            4 => ['吉方' => '北方、东南', '凶方' => '东北、西南', '建议' => '卧室宜设在北方'],
            5 => ['吉方' => '中央、四方', '凶方' => '无明显凶方', '建议' => '卧室方位无特殊要求'],
            6 => ['吉方' => '西方、西北', '凶方' => '南方、东方', '建议' => '卧室宜设在西方'],
            7 => ['吉方' => '西北、西方', '凶方' => '南方、东方', '建议' => '卧室宜设在西北方'],
            8 => ['吉方' => '西南、东北', '凶方' => '北方、东南', '建议' => '卧室宜设在西南方'],
            9 => ['吉方' => '东方、南方', '凶方' => '北方、西方', '建议' => '卧室宜设在东方'],
        ];
        
        return [
            'male' => $directions[$maleGua] ?? $directions[5],
            'female' => $directions[$femaleGua] ?? $directions[5],
        ];
    }
}
