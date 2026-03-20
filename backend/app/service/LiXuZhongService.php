<?php
declare(strict_types=1);

namespace app\service;

/**
 * 李虚中命书 - 每日运势/流年流月分析服务
 * 
 * 基于《李虚中命书》的十九种干支关系进行运势判定。
 * 主要用于比较两个柱（如流日柱 vs 日柱，或流月柱 vs 大运柱）之间的关系。
 */
class LiXuZhongService
{
    protected $tianGan = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];
    protected $diZhi = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];

    protected $ganWuXing = [
        '甲' => '木', '乙' => '木', '丙' => '火', '丁' => '火', '戊' => '土',
        '己' => '土', '庚' => '金', '辛' => '金', '壬' => '水', '癸' => '水'
    ];

    protected $zhiWuXing = [
        '子' => '水', '丑' => '土', '寅' => '木', '卯' => '木', '辰' => '土', '巳' => '火',
        '午' => '火', '未' => '土', '申' => '金', '酉' => '金', '戌' => '土', '亥' => '水'
    ];

    // 五行生克关系
    protected $wuxingRelation = [
        '木' => ['生' => '火', '克' => '土'],
        '火' => ['生' => '土', '克' => '金'],
        '土' => ['生' => '金', '克' => '水'],
        '金' => ['生' => '水', '克' => '木'],
        '水' => ['生' => '木', '克' => '火'],
    ];

    // 天干合
    protected $ganHe = [
        '甲' => '己', '己' => '甲',
        '乙' => '庚', '庚' => '乙',
        '丙' => '辛', '辛' => '丙',
        '丁' => '壬', '壬' => '丁',
        '戊' => '癸', '癸' => '戊'
    ];

    // 地支六合
    protected $zhiLiuHe = [
        '子' => '丑', '丑' => '子',
        '寅' => '亥', '亥' => '寅',
        '卯' => '戌', '戌' => '卯',
        '辰' => '酉', '酉' => '辰',
        '巳' => '申', '申' => '巳',
        '午' => '未', '未' => '午'
    ];

    // 地支六冲
    protected $zhiLiuChong = [
        '子' => '午', '午' => '子',
        '丑' => '未', '未' => '丑',
        '寅' => '申', '申' => '寅',
        '卯' => '酉', '酉' => '卯',
        '辰' => '戌', '戌' => '辰',
        '巳' => '亥', '亥' => '巳'
    ];

    // 地支相刑 (简化版：互刑)
    protected $zhiXing = [
        '子' => ['卯'], '卯' => ['子'], // 无礼之刑
        '寅' => ['巳', '申'], '巳' => ['寅', '申'], '申' => ['寅', '巳'], // 无恩之刑
        '丑' => ['未', '戌'], '未' => ['丑', '戌'], '戌' => ['丑', '未'], // 恃势之刑
        '辰' => ['辰'], '午' => ['午'], '酉' => ['酉'], '亥' => ['亥'] // 自刑
    ];

    /**
     * 分析两个柱之间的关系
     * 
     * @param string $pillarA 外界柱（如流日、流月、流年）
     * @param string $pillarB 自身柱（如日柱、大运）
     * @return array 包含关系类型、描述、吉凶等级
     */
    public function analyzeRelationship(string $pillarA, string $pillarB): array
    {
        $ganA = mb_substr($pillarA, 0, 1);
        $zhiA = mb_substr($pillarA, 1, 1);
        $ganB = mb_substr($pillarB, 0, 1);
        $zhiB = mb_substr($pillarB, 1, 1);

        // 1. 分析天干关系
        $ganRel = $this->getGanRelationship($ganA, $ganB);
        
        // 2. 分析地支关系
        $zhiRel = $this->getZhiRelationship($zhiA, $zhiB);

        // 3. 组合判断 (19种格局)
        $result = $this->determineCategory($ganRel, $zhiRel, $ganA, $zhiA, $ganB, $zhiB);

        // 4. 检查空亡 (第20种)
        // 空亡通常以日柱（自身柱）查旬空，看外界柱地支是否落入空亡
        // 这里假设 pillarB 是日柱
        $kongWang = $this->checkKongWang($ganB, $zhiB, $zhiA);
        if ($kongWang) {
            $result = [
                'category' => '空亡',
                'description' => '你付出的努力，并不总是能立刻看到回报,今天尤其如此。这不是你的失败，而是某种积累在悄悄发生。有时候，什么都不做，也是一种选择。',
                'level' => '凶',
                'score_adjustment' => -10
            ];
        }

        return $result;
    }

    protected function getGanRelationship(string $ganA, string $ganB): string
    {
        if ($ganA === $ganB) {
            return '比'; // 比肩
        }
        if (($this->ganHe[$ganA] ?? '') === $ganB) {
            return '合'; // 天干五合
        }
        
        $wxA = $this->ganWuXing[$ganA];
        $wxB = $this->ganWuXing[$ganB];

        if ($this->wuxingRelation[$wxA]['生'] === $wxB) {
            return '生'; // A生B (天生)
        }
        if ($this->wuxingRelation[$wxB]['生'] === $wxA) {
            return '被生'; // B生A (生天)
        }
        if ($this->wuxingRelation[$wxA]['克'] === $wxB) {
            return '克'; // A克B (天克)
        }
        if ($this->wuxingRelation[$wxB]['克'] === $wxA) {
            return '被克'; // B克A (克天)
        }

        return '无';
    }

    protected function getZhiRelationship(string $zhiA, string $zhiB): string
    {
        if ($zhiA === $zhiB) {
            return '比'; // 伏吟/比和
        }
        if (($this->zhiLiuHe[$zhiA] ?? '') === $zhiB) {
            return '合'; // 六合
        }
        if (($this->zhiLiuChong[$zhiA] ?? '') === $zhiB) {
            return '冲'; // 六冲
        }
        
        // 检查三合/三会 (统称"会")
        if ($this->checkSanHeOrSanHui($zhiA, $zhiB)) {
            return '会';
        }

        // 检查相刑
        if (in_array($zhiB, $this->zhiXing[$zhiA] ?? [])) {
            return '刑';
        }

        return '无';
    }

    protected function checkSanHeOrSanHui(string $zhiA, string $zhiB): bool
    {
        // 简化判断：只要两个地支能构成半合或半会，就算"会"
        // 三合局
        $sanHeGroups = [
            ['申', '子', '辰'], ['亥', '卯', '未'], ['寅', '午', '戌'], ['巳', '酉', '丑']
        ];
        foreach ($sanHeGroups as $group) {
            if (in_array($zhiA, $group) && in_array($zhiB, $group)) {
                return true;
            }
        }

        // 三会方
        $sanHuiGroups = [
            ['寅', '卯', '辰'], ['巳', '午', '未'], ['申', '酉', '戌'], ['亥', '子', '丑']
        ];
        foreach ($sanHuiGroups as $group) {
            if (in_array($zhiA, $group) && in_array($zhiB, $group)) {
                return true;
            }
        }

        return false;
    }

    protected function determineCategory(string $ganRel, string $zhiRel, string $ganA, string $zhiA, string $ganB, string $zhiB): array
    {
        // 19. 伏吟
        if ($ganA === $ganB && $zhiA === $zhiB) {
            return ['category' => '伏吟', 'description' => '你是那种不轻易放弃的人，但今天的努力可能会让你感到一种说不清的疲惫,不是因为你不够努力，而是方向需要重新校准。停下来，往往比继续走更需要勇气。', 'level' => '平', 'score_adjustment' => 0];
        }

        // 1. 天合地合
        if ($ganRel === '合' && $zhiRel === '合') {
            return ['category' => '天合地合', 'description' => '你内心深处一直在等待某个时机，而今天，那种感觉终于对了。你不需要解释太多，事情会自然而然地朝你期待的方向走——这不是运气，而是你一直以来的积累在今天开花。', 'level' => '大吉', 'score_adjustment' => 20];
        }
        // 2. 天合地会
        if ($ganRel === '合' && $zhiRel === '会') {
            return ['category' => '天合地会', 'description' => '你有一种让人感到安心的气质，今天这种特质会为你带来意想不到的助力。那些你以为不会开口的人，今天可能会主动向你靠近。', 'level' => '吉', 'score_adjustment' => 15];
        }
        // 3. 天合地刑
        if ($ganRel === '合' && $zhiRel === '刑') {
            return ['category' => '天合地刑', 'description' => '你善于在别人面前保持体面，但今天你的内心可能比外表更复杂。那种说不清道不明的别扭感，其实是你在提醒自己：有些情绪需要被看见，而不是被压下去。', 'level' => '平', 'score_adjustment' => -5];
        }
        // 4. 天比地合
        if ($ganRel === '比' && $zhiRel === '合') {
            return ['category' => '天比地合', 'description' => '你有一种别人不容易察觉的洞察力，今天这种能力会在关键时刻发挥作用。你不需要强迫任何人，只需要做你自己，合适的人自然会被你吸引过来。', 'level' => '吉', 'score_adjustment' => 10];
        }
        // 5. 天比地冲
        if ($ganRel === '比' && $zhiRel === '冲') {
            return ['category' => '天比地冲', 'description' => '你有时会用忙碌来掩盖内心的空洞感，今天这种感觉可能会更明显。表面的平静背后，你其实比任何人都清楚，有些事情还没有真正落地。', 'level' => '凶', 'score_adjustment' => -10];
        }
        // 6. 天比地刑
        if ($ganRel === '比' && $zhiRel === '刑') {
            return ['category' => '天比地刑', 'description' => '你对自己的要求很高，有时甚至有些苛刻。今天这种内在的张力可能会向外投射，让你觉得周围的人都不太对劲——但也许，需要和解的对象只是你自己。', 'level' => '凶', 'score_adjustment' => -10];
        }
        // 7. 天克地冲 (A克B)
        if ($ganRel === '克' && $zhiRel === '冲') {
            return ['category' => '天克地冲', 'description' => '你不是那种轻易认输的人，但今天你可能会遇到一种特殊的阻力——它不来自外部，而是来自某种时机上的错位。越是用力，越是感到吃力，这不是你的问题，只是今天不是那个适合强攻的时机。', 'level' => '大凶', 'score_adjustment' => -20];
        }
        // 8. 克天地冲 (B克A)
        if ($ganRel === '被克' && $zhiRel === '冲') {
            return ['category' => '克天地冲', 'description' => '你其实比外表看起来更在意别人的看法，尽管你不常承认这一点。今天你可能会发现自己处于一种被动的位置，但这并不意味着你失去了主动权——只是换了一种方式在运作。', 'level' => '凶', 'score_adjustment' => -15];
        }
        // 9. 天生地冲 (A生B)
        if ($ganRel === '生' && $zhiRel === '冲') {
            return ['category' => '天生地冲', 'description' => '你有一种在混乱中找到秩序的天赋，今天这种能力会在不经意间为你带来收获。那些看似偶然的小事，背后往往有你自己都没意识到的努力在支撑。', 'level' => '小吉', 'score_adjustment' => 5];
        }
        // 10. 生天地冲 (B生A)
        if ($ganRel === '被生' && $zhiRel === '冲') {
            return ['category' => '生天地冲', 'description' => '你有一种别人羡慕的韧性，总能在跌倒后重新站起来。今天可能会有一个小小的挫折，但你内心深处其实已经知道，这不过是更好的事情到来之前的一个小插曲。', 'level' => '小吉', 'score_adjustment' => 5];
        }
        // 11. 天生地合 (A生B)
        if ($ganRel === '生' && $zhiRel === '合') {
            return ['category' => '天生地合', 'description' => '你是那种愿意为别人付出的人，而今天，这种善意会以一种你意想不到的方式回到你身上。你不需要刻意去追求什么，只需要做你一直在做的事。', 'level' => '吉', 'score_adjustment' => 15];
        }
        // 12. 生天地合 (B生A)
        if ($ganRel === '被生' && $zhiRel === '合') {
            return ['category' => '生天地合', 'description' => '你身边一直有人在默默关注你，只是你可能还没有意识到。今天，某个人会以一种恰到好处的方式出现，而你需要做的，只是允许自己接受这份帮助。', 'level' => '吉', 'score_adjustment' => 15];
        }
        // 13. 天生地刑 (A生B)
        if ($ganRel === '生' && $zhiRel === '刑') {
            return ['category' => '天生地刑', 'description' => '你有一颗真诚想帮助别人的心，但今天你的好意可能会被误读，或者带来你没有预料到的复杂局面。有时候，最好的帮助是给对方空间，而不是亲自下场。', 'level' => '凶', 'score_adjustment' => -5];
        }
        // 14. 生天地刑 (B生A)
        if ($ganRel === '被生' && $zhiRel === '刑') {
            return ['category' => '生天地刑', 'description' => '你有一种容易被别人依赖的气场，这是你的魅力，但今天它可能会把你带入一个你本不需要参与的局面。你的冷静和理性，是今天最重要的保护。', 'level' => '凶', 'score_adjustment' => -10];
        }
        // 15. 地比天克 (A克B)
        if ($zhiRel === '比' && $ganRel === '克') {
            return ['category' => '地比天克', 'description' => '你比自己以为的更有实力，只是有时候你自己不太相信这一点。今天你会遇到某种考验，而你内心深处其实早就准备好了——你只需要相信那个已经准备好的自己。', 'level' => '平', 'score_adjustment' => 0];
        }
        // 16. 地比克天 (B克A)
        if ($zhiRel === '比' && $ganRel === '被克') {
            return ['category' => '地比克天', 'description' => '你是一个对自己有要求的人，正因如此，今天那种"怎么努力都差一口气"的感觉会格外刺痛。但这不是你的终点，只是一个让你重新审视方向的信号——守住已有的，比强行突破更明智。', 'level' => '平', 'score_adjustment' => -5];
        }
        // 17. 天克地刑 (A克B)
        if ($ganRel === '克' && $zhiRel === '刑') {
            return ['category' => '天克地刑', 'description' => '你有时会用行动来回避思考，用忙碌来掩盖某种深层的不安。今天这种模式可能会让事情变得更复杂，而不是更简单。真正的解决，往往需要你先停下来，面对那个你一直在绕开的问题。', 'level' => '大凶', 'score_adjustment' => -15];
        }
        // 18. 克天地刑 (B克A)
        if ($ganRel === '被克' && $zhiRel === '刑') {
            return ['category' => '克天地刑', 'description' => '你习惯独自承担压力，不太愿意让别人看到你吃力的一面。今天这种压力可能会以一种具体的形式出现，提醒你：懂得保留余地，是一种智慧，不是退缩。', 'level' => '凶', 'score_adjustment' => -15];
        }

        // 默认
        return ['category' => '普通', 'description' => '你是一个内心比外表更丰富的人，今天这种平静的表面下，其实有很多细腻的感受在流动。不是每一天都需要大起大落，有时候，平稳本身就是一种珍贵的状态。', 'level' => '平', 'score_adjustment' => 0];
    }

    protected function checkKongWang(string $gan, string $zhi, string $targetZhi): bool
    {
        // 计算旬首
        $ganIdx = array_search($gan, $this->tianGan);
        $zhiIdx = array_search($zhi, $this->diZhi);
        
        // 旬首地支索引 = (地支索引 - 天干索引 + 12) % 12
        $xunShouZhiIdx = ($zhiIdx - $ganIdx + 12) % 12;
        
        // 旬空地支为旬首往前推两个地支
        $kong1 = $this->diZhi[($xunShouZhiIdx + 10) % 12];
        $kong2 = $this->diZhi[($xunShouZhiIdx + 11) % 12];

        return $targetZhi === $kong1 || $targetZhi === $kong2;
    }
}
