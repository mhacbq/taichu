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
                'description' => '事倍而功半',
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
            return ['category' => '伏吟', 'description' => '多此一举，事多重复', 'level' => '平', 'score_adjustment' => 0];
        }

        // 1. 天合地合
        if ($ganRel === '合' && $zhiRel === '合') {
            return ['category' => '天合地合', 'description' => '新际遇有成，内外和谐', 'level' => '大吉', 'score_adjustment' => 20];
        }
        // 2. 天合地会
        if ($ganRel === '合' && $zhiRel === '会') {
            return ['category' => '天合地会', 'description' => '守成之事，近亲相助', 'level' => '吉', 'score_adjustment' => 15];
        }
        // 3. 天合地刑
        if ($ganRel === '合' && $zhiRel === '刑') {
            return ['category' => '天合地刑', 'description' => '外合心不合，表面顺遂内心纠结', 'level' => '平', 'score_adjustment' => -5];
        }
        // 4. 天比地合
        if ($ganRel === '比' && $zhiRel === '合') {
            return ['category' => '天比地合', 'description' => '以智取之，下得人合作', 'level' => '吉', 'score_adjustment' => 10];
        }
        // 5. 天比地冲
        if ($ganRel === '比' && $zhiRel === '冲') {
            return ['category' => '天比地冲', 'description' => '外象平安，内实空虚', 'level' => '凶', 'score_adjustment' => -10];
        }
        // 6. 天比地刑
        if ($ganRel === '比' && $zhiRel === '刑') {
            return ['category' => '天比地刑', 'description' => '处于长期内争，矛盾重重', 'level' => '凶', 'score_adjustment' => -10];
        }
        // 7. 天克地冲 (A克B)
        if ($ganRel === '克' && $zhiRel === '冲') {
            return ['category' => '天克地冲', 'description' => '创业性的碰钉子，压力巨大', 'level' => '大凶', 'score_adjustment' => -20];
        }
        // 8. 克天地冲 (B克A)
        if ($ganRel === '被克' && $zhiRel === '冲') {
            return ['category' => '克天地冲', 'description' => '偿债式的说好话，被动受制', 'level' => '凶', 'score_adjustment' => -15];
        }
        // 9. 天生地冲 (A生B)
        if ($ganRel === '生' && $zhiRel === '冲') {
            return ['category' => '天生地冲', 'description' => '小有成就，冲动中得利', 'level' => '小吉', 'score_adjustment' => 5];
        }
        // 10. 生天地冲 (B生A)
        if ($ganRel === '被生' && $zhiRel === '冲') {
            return ['category' => '生天地冲', 'description' => '因小祸而得福，先难后易', 'level' => '小吉', 'score_adjustment' => 5];
        }
        // 11. 天生地合 (A生B)
        if ($ganRel === '生' && $zhiRel === '合') {
            return ['category' => '天生地合', 'description' => '助他人之故而获利，顺水推舟', 'level' => '吉', 'score_adjustment' => 15];
        }
        // 12. 生天地合 (B生A)
        if ($ganRel === '被生' && $zhiRel === '合') {
            return ['category' => '生天地合', 'description' => '借他人之力而有成，贵人相助', 'level' => '吉', 'score_adjustment' => 15];
        }
        // 13. 天生地刑 (A生B)
        if ($ganRel === '生' && $zhiRel === '刑') {
            return ['category' => '天生地刑', 'description' => '自找麻烦，好心办坏事', 'level' => '凶', 'score_adjustment' => -5];
        }
        // 14. 生天地刑 (B生A)
        if ($ganRel === '被生' && $zhiRel === '刑') {
            return ['category' => '生天地刑', 'description' => '他人加于自己的麻烦，无妄之灾', 'level' => '凶', 'score_adjustment' => -10];
        }
        // 15. 地比天克 (A克B)
        if ($zhiRel === '比' && $ganRel === '克') {
            return ['category' => '地比天克', 'description' => '有信心中应付强对手，挑战中成长', 'level' => '平', 'score_adjustment' => 0];
        }
        // 16. 地比克天 (B克A)
        if ($zhiRel === '比' && $ganRel === '被克') {
            return ['category' => '地比克天', 'description' => '渐失信心中坚持努力，需守成', 'level' => '平', 'score_adjustment' => -5];
        }
        // 17. 天克地刑 (A克B)
        if ($ganRel === '克' && $zhiRel === '刑') {
            return ['category' => '天克地刑', 'description' => '以债养债，恶性循环', 'level' => '大凶', 'score_adjustment' => -15];
        }
        // 18. 克天地刑 (B克A)
        if ($ganRel === '被克' && $zhiRel === '刑') {
            return ['category' => '克天地刑', 'description' => '抵押偿债，压力沉重', 'level' => '凶', 'score_adjustment' => -15];
        }

        // 默认
        return ['category' => '普通', 'description' => '运势平稳，无特殊冲合', 'level' => '平', 'score_adjustment' => 0];
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
