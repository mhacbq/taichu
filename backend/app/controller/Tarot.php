<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\PointsRecord;
use app\model\TarotRecord;
use app\service\TarotElementService;
use think\facade\Db;
use think\facade\Log;




class Tarot extends BaseController
{
    // 塔罗占卜所需积分
    const TAROT_POINTS_COST = 5;
    
    protected $middleware = [
        \app\middleware\Auth::class => ['only' => ['draw', 'interpret', 'saveRecord', 'history', 'detail', 'deleteRecord', 'setPublic']],
    ];

    
    // 塔罗牌数组（包含图片/emoji、颜色和正逆位含义）
    protected $tarotCards = [
        // 大阿卡纳 (22张)
        ['name' => '愚者', 'meaning' => '新的开始，冒险，纯真', 'reversed_meaning' => '鲁莽，疏忽，愚蠢的决定，缺乏经验的冒险', 'emoji' => '🃏', 'color' => '#ff9f43', 'element' => '风'],
        ['name' => '魔术师', 'meaning' => '创造力，技巧，意志力，心想事成', 'reversed_meaning' => '缺乏计划，犹豫不决，由于技巧不足导致的失败', 'emoji' => '🎩', 'color' => '#ff6b6b', 'element' => '风'],
        ['name' => '女祭司', 'meaning' => '直觉，神秘，潜意识，宁静，智慧', 'reversed_meaning' => '直觉受阻，肤浅的知识，隐藏的秘密浮现，焦虑', 'emoji' => '🌙', 'color' => '#4834d4', 'element' => '水'],
        ['name' => '皇后', 'meaning' => '丰饶，母性，自然，美，创造性', 'reversed_meaning' => '创造力受阻，过度保护，经济问题，依赖他人', 'emoji' => '👑', 'color' => '#6ab04c', 'element' => '土'],
        ['name' => '皇帝', 'meaning' => '权威，结构，父亲，规则，领导力', 'reversed_meaning' => '暴政，固执，缺乏自控力，权力受限，僵化', 'emoji' => '⚡', 'color' => '#eb4d4b', 'element' => '火'],
        ['name' => '教皇', 'meaning' => '传统，精神指导，信仰，体制，盟约', 'reversed_meaning' => '打破传统，反叛，虚伪的教条，思想过于保守', 'emoji' => '🔑', 'color' => '#f9ca24', 'element' => '土'],
        ['name' => '恋人', 'meaning' => '爱情，选择，和谐，关系，共同价值观', 'reversed_meaning' => '失衡，错误的选择，关系不和，价值观冲突', 'emoji' => '💕', 'color' => '#ff7979', 'element' => '风'],
        ['name' => '战车', 'meaning' => '意志力，胜利，控制，决心，成功', 'reversed_meaning' => '失去控制，缺乏方向，失败，被情绪左右', 'emoji' => '🏆', 'color' => '#22a6b3', 'element' => '水'],
        ['name' => '力量', 'meaning' => '勇气，耐心，内在力量，自律，同情', 'reversed_meaning' => '自我怀疑，软弱，缺乏自信，容易动怒，由于恐惧而退缩', 'emoji' => '🦁', 'color' => '#f0932b', 'element' => '火'],
        ['name' => '隐者', 'meaning' => '内省，孤独，指引，寻求真理，精神觉醒', 'reversed_meaning' => '孤立，拒绝建议，偏执，由于逃避而错失机会', 'emoji' => '🕯️', 'color' => '#535c68', 'element' => '土'],
        ['name' => '命运之轮', 'meaning' => '命运，周期，转折点，运气，重大变化', 'reversed_meaning' => '坏运气，阻碍，抵制变化，意外的负面转折', 'emoji' => '☸️', 'color' => '#be2edd', 'element' => '火'],
        ['name' => '正义', 'meaning' => '公正，真理，因果，法律，责任', 'reversed_meaning' => '不公平，偏见，拒绝承担责任，法律纠纷不利', 'emoji' => '⚖️', 'color' => '#30336b', 'element' => '风'],
        ['name' => '倒吊人', 'meaning' => '牺牲，等待，新视角，停滞，审视', 'reversed_meaning' => '无谓的牺牲，拖延，由于固执而错失良机，拒绝改变', 'emoji' => '🙃', 'color' => '#16a085', 'element' => '水'],
        ['name' => '死神', 'meaning' => '结束，转变，新生，放手，周期终结', 'reversed_meaning' => '拒绝改变，停滞不前，害怕死亡或终结，无谓的执着', 'emoji' => '💀', 'color' => '#2f3542', 'element' => '水'],
        ['name' => '节制', 'meaning' => '平衡，调和，耐心，治愈，中庸', 'reversed_meaning' => '失衡，过度，缺乏远见，缺乏耐性，冲突', 'emoji' => '🏺', 'color' => '#7bed9f', 'element' => '火'],
        ['name' => '恶魔', 'meaning' => '束缚，物质主义，诱惑，成瘾，阴影', 'reversed_meaning' => '解脱，打破束缚，觉醒，克服诱惑，重获自由', 'emoji' => '👿', 'color' => '#8B0000', 'element' => '土'],
        ['name' => '塔', 'meaning' => '突然变化，觉醒，破坏，突发事件，启示', 'reversed_meaning' => '逃过一劫，害怕变化，缓慢的崩解，避免了不必要的冲突', 'emoji' => '🗼', 'color' => '#e74c3c', 'element' => '火'],
        ['name' => '星星', 'meaning' => '希望，灵感，宁静，治愈，乐观', 'reversed_meaning' => '失望，缺乏信念，悲观，灵感枯竭，焦虑', 'emoji' => '⭐', 'color' => '#74b9ff', 'element' => '风'],
        ['name' => '月亮', 'meaning' => '幻觉，恐惧，潜意识，直觉，混乱', 'reversed_meaning' => '真相大白，克服恐惧，缓解焦虑，直觉恢复', 'emoji' => '🌕', 'color' => '#dfe6e9', 'element' => '水'],
        ['name' => '太阳', 'meaning' => '快乐，成功，活力，真诚，光明', 'reversed_meaning' => '成功的延迟，短暂的阴影，傲慢导致的失误', 'emoji' => '☀️', 'color' => '#f1c40f', 'element' => '火'],
        ['name' => '审判', 'meaning' => '重生，觉醒，宽恕，呼唤，关键决定', 'reversed_meaning' => '自我怀疑，错误的决定，拒绝呼唤，难以原谅', 'emoji' => '📯', 'color' => '#9b59b6', 'element' => '火'],
        ['name' => '世界', 'meaning' => '完成，成就，旅行，圆满，成功', 'reversed_meaning' => '由于缺乏专注而导致延迟，未竟之志，成功前的最后阻碍', 'emoji' => '🌍', 'color' => '#3498db', 'element' => '土'],
        
        // 权杖组 (Wands - 火 - 14张)
        ['name' => '权杖一', 'meaning' => '新的行动，灵感，创造力', 'reversed_meaning' => '由于缺乏灵感而停滞，延迟的开始', 'emoji' => '🪄', 'color' => '#ff4757', 'element' => '火'],
        ['name' => '权杖二', 'meaning' => '规划，决策，展望', 'reversed_meaning' => '由于过分担忧而犹豫，计划受阻', 'emoji' => '🗺️', 'color' => '#ff4757', 'element' => '火'],
        ['name' => '权杖三', 'meaning' => '扩张，远见，合作', 'reversed_meaning' => '由于视野受限而失败，合作出现问题', 'emoji' => '🔭', 'color' => '#ff4757', 'element' => '火'],
        ['name' => '权杖四', 'meaning' => '稳固，庆祝，和谐', 'reversed_meaning' => '暂时的不和，庆祝活动推迟', 'emoji' => '🏰', 'color' => '#ff4757', 'element' => '火'],
        ['name' => '权杖五', 'meaning' => '竞争，冲突，挣扎', 'reversed_meaning' => '冲突的缓解，寻找共识，避开纷争', 'emoji' => '⚔️', 'color' => '#ff4757', 'element' => '火'],
        ['name' => '权杖六', 'meaning' => '胜利，成功，认可', 'reversed_meaning' => '由于傲慢而失败，公众形象受损', 'emoji' => '🏇', 'color' => '#ff4757', 'element' => '火'],
        ['name' => '权杖七', 'meaning' => '防御，勇气，坚持', 'reversed_meaning' => '由于软弱而退缩，压力过大而放弃', 'emoji' => '🛡️', 'color' => '#ff4757', 'element' => '火'],
        ['name' => '权杖八', 'meaning' => '迅速，行动，消息', 'reversed_meaning' => '进展缓慢，消息中断，由于急躁而失误', 'emoji' => '🏹', 'color' => '#ff4757', 'element' => '火'],
        ['name' => '权杖九', 'meaning' => '防御，韧性，警惕', 'reversed_meaning' => '防线被突破，精疲力竭，过度防御', 'emoji' => '🧱', 'color' => '#ff4757', 'element' => '火'],
        ['name' => '权杖十', 'meaning' => '压力，重负，责任', 'reversed_meaning' => '压力得以释放，拒绝承担不属于自己的重担', 'emoji' => '🎒', 'color' => '#ff4757', 'element' => '火'],
        ['name' => '权杖侍从', 'meaning' => '热情，消息，探索', 'reversed_meaning' => '由于幼稚而受挫，不可靠的消息', 'emoji' => '👦', 'color' => '#ff4757', 'element' => '火'],
        ['name' => '权杖骑士', 'meaning' => '冲动，冒险，热血', 'reversed_meaning' => '鲁莽的行为导致麻烦，缺乏计划的行动', 'emoji' => '🏇', 'color' => '#ff4757', 'element' => '火'],
        ['name' => '权杖皇后', 'meaning' => '自信，独立，温暖', 'reversed_meaning' => '由于自负而导致关系紧张，缺乏耐性', 'emoji' => '👸', 'color' => '#ff4757', 'element' => '火'],
        ['name' => '权杖国王', 'meaning' => '领导力，远见，决断', 'reversed_meaning' => '由于独断专行而失去支持，由于急躁而决策失误', 'emoji' => '🤴', 'color' => '#ff4757', 'element' => '火'],

        // 圣杯组 (Cups - 水 - 14张)
        ['name' => '圣杯一', 'meaning' => '情感新开端，直觉，爱', 'reversed_meaning' => '情感波动，由于过度期待而失望', 'emoji' => '🍷', 'color' => '#1e90ff', 'element' => '水'],
        ['name' => '圣杯二', 'meaning' => '伴侣关系，吸引，团结', 'reversed_meaning' => '沟通不畅，合作破裂，关系失衡', 'emoji' => '🥂', 'color' => '#1e90ff', 'element' => '水'],
        ['name' => '圣杯三', 'meaning' => '友谊，庆祝，社区', 'reversed_meaning' => '由于流言蜚语而导致的关系不和', 'emoji' => '🍹', 'color' => '#1e90ff', 'element' => '水'],
        ['name' => '圣杯四', 'meaning' => '冥想，冷淡，退缩', 'reversed_meaning' => '由于觉醒而重返现实，接受新机会', 'emoji' => '🧘', 'color' => '#1e90ff', 'element' => '水'],
        ['name' => '圣杯五', 'meaning' => '悲伤，失去，后悔', 'reversed_meaning' => '走出阴影，接纳现状，由于释怀而新生', 'emoji' => '🥀', 'color' => '#1e90ff', 'element' => '水'],
        ['name' => '圣杯六', 'meaning' => '怀旧，童真，赠予', 'reversed_meaning' => '活在过去，不切实际的怀念', 'emoji' => '🎁', 'color' => '#1e90ff', 'element' => '水'],
        ['name' => '圣杯七', 'meaning' => '幻想，选择，白日梦', 'reversed_meaning' => '回归现实，由于清醒而做出决定', 'emoji' => '🌈', 'color' => '#1e90ff', 'element' => '水'],
        ['name' => '圣杯八', 'meaning' => '追寻，离开，失望', 'reversed_meaning' => '由于恐惧而无法离开，由于迷茫而停滞', 'emoji' => '🚶', 'color' => '#1e90ff', 'element' => '水'],
        ['name' => '圣杯九', 'meaning' => '满足，愿望实现，感官享受', 'reversed_meaning' => '由于贪婪而导致的不满，过度纵欲', 'emoji' => '😋', 'color' => '#1e90ff', 'element' => '水'],
        ['name' => '圣杯十', 'meaning' => '和谐，幸福家庭，圆满', 'reversed_meaning' => '由于缺乏沟通而导致的关系裂痕', 'emoji' => '👨‍👩‍👧‍👦', 'color' => '#1e90ff', 'element' => '水'],
        ['name' => '圣杯侍从', 'meaning' => '情感消息，敏感，直觉', 'reversed_meaning' => '由于情绪化而导致的误解', 'emoji' => '🐟', 'color' => '#1e90ff', 'element' => '水'],
        ['name' => '圣杯骑士', 'meaning' => '浪漫，邀请，想象力', 'reversed_meaning' => '由于不切实际而受挫，情感骗局', 'emoji' => '🐎', 'color' => '#1e90ff', 'element' => '水'],
        ['name' => '圣杯皇后', 'meaning' => '慈悲，直觉，情感稳定', 'reversed_meaning' => '由于过度敏感而导致的情绪化', 'emoji' => '👸', 'color' => '#1e90ff', 'element' => '水'],
        ['name' => '圣杯国王', 'meaning' => '情感平衡，掌控，睿智', 'reversed_meaning' => '由于冷漠而导致的关系紧张', 'emoji' => '🤴', 'color' => '#1e90ff', 'element' => '水'],

        // 宝剑组 (Swords - 风 - 14张)
        ['name' => '宝剑一', 'meaning' => '理智，突破，清晰', 'reversed_meaning' => '由于思维混乱而受挫，缺乏远见', 'emoji' => '🗡️', 'color' => '#a4b0be', 'element' => '风'],
        ['name' => '宝剑二', 'meaning' => '僵局，逃避，抉择', 'reversed_meaning' => '由于被迫抉择而感到压力，犹豫不决', 'emoji' => '⚖️', 'color' => '#a4b0be', 'element' => '风'],
        ['name' => '宝剑三', 'meaning' => '心碎，痛苦，分离', 'reversed_meaning' => '缓解痛苦，开始治愈，放下心结', 'emoji' => '💔', 'color' => '#a4b0be', 'element' => '风'],
        ['name' => '宝剑四', 'meaning' => '休息，康复，冥想', 'reversed_meaning' => '强迫性的活动，由于焦虑而无法休息', 'emoji' => '🛌', 'color' => '#a4b0be', 'element' => '风'],
        ['name' => '宝剑五', 'meaning' => '冲突，背叛，失败', 'reversed_meaning' => '由于放下执念而结束冲突，由于妥协而共赢', 'emoji' => '😒', 'color' => '#a4b0be', 'element' => '风'],
        ['name' => '宝剑六', 'meaning' => '过渡，康复，离开', 'reversed_meaning' => '由于陷入僵局而无法离开，进展极其缓慢', 'emoji' => '⛵', 'color' => '#a4b0be', 'element' => '风'],
        ['name' => '宝剑七', 'meaning' => '欺骗，逃避，计谋', 'reversed_meaning' => '真相揭晓，为之前的行为承担后果', 'emoji' => '🕵️', 'color' => '#a4b0be', 'element' => '风'],
        ['name' => '宝剑八', 'meaning' => '受困，无助，限制', 'reversed_meaning' => '由于自救而重获自由，发现新契机', 'emoji' => '⛓️', 'color' => '#a4b0be', 'element' => '风'],
        ['name' => '宝剑九', 'meaning' => '焦虑，噩梦，绝望', 'reversed_meaning' => '逐渐走出阴霾，克服内心的恐惧', 'emoji' => '😫', 'color' => '#a4b0be', 'element' => '风'],
        ['name' => '宝剑十', 'meaning' => '背水一战，痛苦，终结', 'reversed_meaning' => '否极泰来，最困难的时刻已经过去', 'emoji' => '🔪', 'color' => '#a4b0be', 'element' => '风'],
        ['name' => '宝剑侍从', 'meaning' => '好奇，消息，机警', 'reversed_meaning' => '由于言语失当而导致的麻烦', 'emoji' => '🦜', 'color' => '#a4b0be', 'element' => '风'],
        ['name' => '宝剑骑士', 'meaning' => '行动，思维敏捷，直言不讳', 'reversed_meaning' => '由于盲目而失误，咄咄逼人的态度', 'emoji' => '💨', 'color' => '#a4b0be', 'element' => '风'],
        ['name' => '宝剑皇后', 'meaning' => '独立，公正，理性', 'reversed_meaning' => '由于冷酷而导致的人际关系紧张', 'emoji' => '👸', 'color' => '#a4b0be', 'element' => '风'],
        ['name' => '宝剑国王', 'meaning' => '智商，权威，真理', 'reversed_meaning' => '由于滥用职权而失去信任', 'emoji' => '🤴', 'color' => '#a4b0be', 'element' => '风'],

        // 星币组 (Pentacles - 土 - 14张)
        ['name' => '星币一', 'meaning' => '财富开端，繁荣，稳固', 'reversed_meaning' => '财务受损，投资不善，错失良机', 'emoji' => '🪙', 'color' => '#ffa502', 'element' => '土'],
        ['name' => '星币二', 'meaning' => '平衡，适应，多重任务', 'reversed_meaning' => '由于应接不暇而导致混乱，失衡', 'emoji' => '🤹', 'color' => '#ffa502', 'element' => '土'],
        ['name' => '星币三', 'meaning' => '团队，技能，合作', 'reversed_meaning' => '由于沟通不畅而导致的团队破裂', 'emoji' => '🏗️', 'color' => '#ffa502', 'element' => '土'],
        ['name' => '星币四', 'meaning' => '保守，占有欲，贪婪', 'reversed_meaning' => '由于过度挥霍而导致风险，吝啬', 'emoji' => '🔒', 'color' => '#ffa502', 'element' => '土'],
        ['name' => '星币五', 'meaning' => '贫困，困境，排斥', 'reversed_meaning' => '处境逐渐好转，开始获得外界援助', 'emoji' => '🥶', 'color' => '#ffa502', 'element' => '土'],
        ['name' => '星币六', 'meaning' => '慷慨，施舍，平衡', 'reversed_meaning' => '由于自私而导致的失衡，财务纠纷', 'emoji' => '⚖️', 'color' => '#ffa502', 'element' => '土'],
        ['name' => '星币七', 'meaning' => '评估，耐心，收获', 'reversed_meaning' => '由于缺乏耐心而导致功亏一篑', 'emoji' => '🌱', 'color' => '#ffa502', 'element' => '土'],
        ['name' => '星币八', 'meaning' => '勤奋，技艺，精进', 'reversed_meaning' => '由于缺乏专注而导致停滞，急功近利', 'emoji' => '🛠️', 'color' => '#ffa502', 'element' => '土'],
        ['name' => '星币九', 'meaning' => '独立，富足，奢华', 'reversed_meaning' => '由于过分炫耀而导致的危机，孤独', 'emoji' => '🏡', 'color' => '#ffa502', 'element' => '土'],
        ['name' => '星币十', 'meaning' => '家族，财富，遗产', 'reversed_meaning' => '家庭内部出现财务纠纷，由于继承而产生的矛盾', 'emoji' => '🏘️', 'color' => '#ffa502', 'element' => '土'],
        ['name' => '星币侍从', 'meaning' => '机遇，勤奋，金钱消息', 'reversed_meaning' => '由于不切实际而导致的机会丧失', 'emoji' => '📔', 'color' => '#ffa502', 'element' => '土'],
        ['name' => '星币骑士', 'meaning' => '稳健，责任，勤勉', 'reversed_meaning' => '由于过分保守而导致的一成不变', 'emoji' => '🐂', 'color' => '#ffa502', 'element' => '土'],
        ['name' => '星币皇后', 'meaning' => '富饶，务实，安全感', 'reversed_meaning' => '由于缺乏安全感而导致的过度依赖', 'emoji' => '👸', 'color' => '#ffa502', 'element' => '土'],
        ['name' => '星币国王', 'meaning' => '成功，丰厚，领导力', 'reversed_meaning' => '由于利欲熏心而导致的信誉破产', 'emoji' => '🤴', 'color' => '#ffa502', 'element' => '土'],
    ];
    
    /**
     * 抽牌 - 增强版错误处理
     */
    public function draw()
    {
        try {
            $data = $this->request->post();
            $user = $this->request->user ?? [];
            
            // 验证用户身份
            if (empty($user['sub'])) {
                return $this->error('用户未登录，请先登录', 401);
            }
            
            // 验证参数
            if (empty($data['spread'])) {
                return $this->error('请选择牌阵');
            }
            
            $spread = $data['spread'];
            $numCards = match($spread) {
                'single' => 1,
                'three' => 3,
                'celtic' => 10,
                default => 3,
            };
            
            // 检查用户积分
            $userModel = \app\model\User::find($user['sub']);
            if (!$userModel) {
                return $this->error('用户不存在', 404);
            }
            
            if ($userModel->points < self::TAROT_POINTS_COST) {
                return $this->error('积分不足，请先充值', 403);
            }
            
            // 抽牌
            $cards = $this->drawCards($numCards);
            
            // 扣除积分（使用行锁确保原子性）
            Db::startTrans();
            try {
                // 1. 重新查询用户（带行锁），确保获取最新积分并防止并发
                $userData = Db::name('tc_user')
                    ->where('id', $user['sub'])
                    ->where('status', 1)
                    ->lock(true)
                    ->find();
                
                if (!$userData) {
                    Db::rollback();
                    return $this->error('用户不存在', 404);
                }
                
                if ($userData['points'] < self::TAROT_POINTS_COST) {
                    Db::rollback();
                    return $this->error('积分不足，请先充值', 403);
                }
                
                // 2. 扣除积分
                $updateResult = Db::name('tc_user')
                    ->where('id', $user['sub'])
                    ->dec('points', self::TAROT_POINTS_COST)
                    ->update();
                
                if ($updateResult === 0) {
                    Db::rollback();
                    return $this->error('积分扣除失败，请重试');
                }
                
                // 3. 记录积分变动（兼容新旧积分流水表结构，并保持在同一事务）
                $remainingPoints = (int) $userData['points'] - self::TAROT_POINTS_COST;
                $pointsPayload = PointsRecord::buildRecordPayload(
                    (int) $user['sub'],
                    '塔罗占卜消耗',
                    -self::TAROT_POINTS_COST,
                    'tarot',
                    0,
                    "牌阵: {$spread}",
                    [
                        'balance' => $remainingPoints,
                        'reason' => '塔罗抽牌消耗积分',
                        'description' => "塔罗 {$spread} 牌阵抽牌消耗",
                    ]
                );
                Db::name('tc_points_record')->insert($pointsPayload);
                
                Db::commit();
                
                return $this->success([
                    'cards' => $cards,
                    'spread' => $spread,
                    'points_cost' => self::TAROT_POINTS_COST,
                    'remaining_points' => $remainingPoints,
                ]);
            } catch (\Exception $e) {
                Db::rollback();
                throw $e; // 重新抛出以便外层捕获
            }
        } catch (\Throwable $e) {
            return $this->respondSystemException('执行塔罗抽牌', $e, '抽牌失败，请稍后重试', [
                'user_id' => (int) ($user['sub'] ?? 0),
                'spread' => (string) ($spread ?? 'unknown'),
                'points_cost' => self::TAROT_POINTS_COST,
                'request_data' => $this->sanitizeLogContext($data),
            ], 500);
        }
    }
    
    /**
     * 解读牌阵
     */
    public function interpret()
    {
        try {
            $data = $this->request->post();

            if (empty($data['cards']) || empty($data['question'])) {
                return $this->error('缺少必要参数');
            }

            $cards = $data['cards'];
            $question = $data['question'];

            $interpretation = $this->generateInterpretation($cards, $question);

            return $this->success([
                'interpretation' => $interpretation,
            ]);
        } catch (\Throwable $e) {
            $this->logControllerException('执行塔罗解读', $e, [
                'card_count' => is_array($cards ?? null) ? count($cards) : 0,
                'question_length' => mb_strlen((string) ($question ?? '')),
            ]);
            return $this->error('解读失败，请稍后重试', 500);
        }
    }

    
    /**
     * 高精度抽取指定数量的牌
     */
    protected function drawCards(int $num): array
    {
        $cards = [];
        $totalCards = count($this->tarotCards);
        $positions = $this->getSpreadPositions($num);
        
        // 使用更高质量的随机数源
        $randomKeys = $this->generateHighQualityRandomKeys($num, $totalCards);
        
        foreach ($randomKeys as $index => $key) {
            $card = $this->tarotCards[$key];
            
            // 使用更科学的正逆位判断算法
            $reversed = $this->calculateReversedProbability($card, $index, $num);
            $orientation = $reversed ? '逆位' : '正位';
            $orientationMeaning = $reversed ? ($card['reversed_meaning'] ?? '') : $card['meaning'];
            
            $cards[] = [
                'name' => $card['name'],
                'meaning' => $card['meaning'],
                'reversed_meaning' => $card['reversed_meaning'] ?? '',
                'emoji' => $card['emoji'],
                'color' => $card['color'],
                'element' => $card['element'],
                'position' => $positions[$index] ?? ('第' . ($index + 1) . '张'),
                'keywords' => str_replace('，', '、', $orientationMeaning),
                'orientation' => $orientation,
                'orientation_meaning' => $orientationMeaning,
                'reversed' => $reversed,
                'random_seed' => $this->generateRandomSeed($card, $index), // 记录随机种子用于验证
            ];
        }

        return $cards;
    }
    
    /**
     * 生成高质量随机键值
     */
    protected function generateHighQualityRandomKeys(int $num, int $totalCards): array
    {
        // 使用更安全的随机数生成器
        if (function_exists('random_int')) {
            $keys = [];
            $selected = [];
            
            while (count($keys) < $num) {
                $key = random_int(0, $totalCards - 1);
                if (!in_array($key, $selected)) {
                    $selected[] = $key;
                    $keys[] = $key;
                }
                
                // 防止无限循环
                if (count($selected) >= min($num, $totalCards)) {
                    break;
                }
            }
            
            return $keys;
        }
        
        // 备用方案：使用改进的mt_rand算法
        mt_srand((int)(microtime(true) * 1000000));
        
        // 处理特殊情况：当需要抽取的牌数大于总牌数时
        if ($num >= $totalCards) {
            return range(0, $totalCards - 1);
        }
        
        $keys = array_rand(range(0, $totalCards - 1), $num);
        return is_array($keys) ? $keys : [$keys];
    }
    
    /**
     * 计算正逆位概率（基于牌的特性和位置）
     */
    protected function calculateReversedProbability(array $card, int $positionIndex, int $totalCards): bool
    {
        $baseProbability = 0.5; // 基础概率50%
        
        // 根据牌的特性调整概率
        $cardAdjustment = $this->getCardReversedBias($card);
        
        // 根据位置调整概率
        $positionAdjustment = $this->getPositionReversedBias($positionIndex, $totalCards);
        
        // 最终概率计算
        $finalProbability = $baseProbability + $cardAdjustment + $positionAdjustment;
        $finalProbability = max(0.1, min(0.9, $finalProbability)); // 限制在10%-90%之间
        
        // 使用高质量随机数判断
        $randomValue = function_exists('random_int') ? 
            random_int(0, 10000) / 10000 : 
            mt_rand(0, 10000) / 10000;
        
        return $randomValue < $finalProbability;
    }
    
    /**
     * 获取牌的逆位偏向性
     */
    protected function getCardReversedBias(array $card): float
    {
        $name = strtolower($card['name'] ?? '');
        $element = $card['element'] ?? '';
        
        // 某些牌更容易出现逆位（如塔、恶魔等）
        $difficultCards = ['塔', '恶魔', '死神', '审判', '高塔', '倒吊人'];
        if (in_array($card['name'], $difficultCards)) {
            return 0.15; // 增加15%逆位概率
        }
        
        // 某些牌更倾向于正位（如太阳、世界、星星等）
        $positiveCards = ['太阳', '世界', '星星', '恋人', '战车'];
        if (in_array($card['name'], $positiveCards)) {
            return -0.1; // 减少10%逆位概率
        }
        
        return 0.0;
    }
    
    /**
     * 获取位置的逆位偏向性
     */
    protected function getPositionReversedBias(int $positionIndex, int $totalCards): float
    {
        // 凯尔特十字牌阵中，某些位置更容易出现逆位
        if ($totalCards === 10) {
            $difficultPositions = [1, 2, 4, 6]; // 障碍、潜意识、过去、未来
            if (in_array($positionIndex, $difficultPositions)) {
                return 0.08;
            }
        }
        
        return 0.0;
    }
    
    /**
     * 生成随机种子用于验证
     */
    protected function generateRandomSeed(array $card, int $positionIndex): string
    {
        $timestamp = microtime(true);
        $cardHash = md5($card['name'] . $positionIndex);
        return substr($cardHash, 0, 8) . '_' . substr((string)$timestamp, -6);
    }
    
    /**
     * 生成解读
     */
    protected function generateInterpretation(array $cards, string $question): string
    {
        $count = count($cards);
        $profiles = $this->getSpreadPositionProfiles($count);
        $spreadLabel = $this->getSpreadLabel($count);
        $interpretation = "针对您的问题：「{$question}」\n\n";

        if ($count === 1) {
            $card = $cards[0];
            $profile = $profiles[0] ?? ['name' => '今日指引', 'focus' => '当下最核心的课题', 'advice' => '先抓住眼前最需要回应的一步'];
            $orientation = $card['reversed'] ? '逆位' : '正位';
            $interpretation .= "抽到的牌是【{$card['name']}】{$orientation}，落在【{$profile['name']}】。\n";
            $interpretation .= $this->buildPositionInterpretation($card, $profile, $question) . "\n\n";
            $interpretation .= "【核心提醒】\n" . $this->buildSpreadConclusion($cards, $question, $profiles);
            return $interpretation;
        }

        $interpretation .= "您的【{$spreadLabel}】牌阵解读如下：\n\n";
        foreach ($cards as $index => $card) {
            $profile = $profiles[$index] ?? ['name' => '第' . ($index + 1) . '张', 'focus' => '当前情势的一个侧面', 'advice' => '顺着问题脉络继续观察'];
            $orientation = $card['reversed'] ? '逆位' : '正位';
            $interpretation .= "【{$profile['name']}】{$card['name']} {$orientation}\n";
            $interpretation .= $this->buildPositionInterpretation($card, $profile, $question) . "\n\n";
        }

        $elementAnalysis = $this->analyzeElements($cards);
        $interpretation .= "【元素分析】\n{$elementAnalysis}\n\n";

        if ($count >= 2) {
            $relationshipAnalysis = $this->analyzeCardRelationships($cards);
            $interpretation .= "【牌阵关系分析】\n{$relationshipAnalysis}\n\n";
        }

        $interpretation .= "【综合结论】\n" . $this->buildSpreadConclusion($cards, $question, $profiles);

        return $interpretation;
    }
    
    /**
     * 获取牌阵名称。
     */
    protected function getSpreadLabel(int $count): string
    {
        return match($count) {
            1 => '单张牌',
            3 => '三牌阵',
            10 => '凯尔特十字',
            default => '通用牌阵',
        };
    }

    /**
     * 获取牌位画像，统一前后端对凯尔特十字的中文语义。
     */
    protected function getSpreadPositionProfiles(int $count): array
    {
        return match($count) {
            1 => [
                ['name' => '今日指引', 'focus' => '当下最核心的课题', 'advice' => '先抓住眼前最需要回应的一步'],
            ],
            3 => [
                ['name' => '过去', 'focus' => '已经形成的背景与惯性', 'advice' => '先看清旧模式如何影响现在'],
                ['name' => '现在', 'focus' => '此刻最真实的局面', 'advice' => '别绕开眼前最重要的问题'],
                ['name' => '未来', 'focus' => '若维持当前轨迹将出现的走向', 'advice' => '提前为即将到来的变化留出余地'],
            ],
            10 => [
                ['name' => '当前状态', 'focus' => '问题最直观的表层表现', 'advice' => '先承认局势目前真正站在哪个阶段'],
                ['name' => '障碍/挑战', 'focus' => '真正卡住推进的阻力点', 'advice' => '不要只看表面冲突，要找出核心卡点'],
                ['name' => '潜意识/基础', 'focus' => '问题背后的深层动机与根基', 'advice' => '回到根源，才能解释反复出现的模式'],
                ['name' => '过去影响', 'focus' => '已经发生且仍在起作用的旧因素', 'advice' => '分清哪些经验该保留，哪些执念该放下'],
                ['name' => '目标可能', 'focus' => '你想抵达的方向或显意识目标', 'advice' => '确认目标是否真的契合内心而非只为止损'],
                ['name' => '近期发展', 'focus' => '短期内最容易先浮现的变化', 'advice' => '留意第一个转折点，它会决定后续节奏'],
                ['name' => '你的态度', 'focus' => '你此刻的立场、姿态与处理方式', 'advice' => '先调整自己的应对姿态，再谈外部变化'],
                ['name' => '外部环境', 'focus' => '环境、人际与现实条件带来的推动或牵制', 'advice' => '把可控与不可控因素分开处理'],
                ['name' => '希望/恐惧', 'focus' => '你最想抓住也最怕失去的心理张力', 'advice' => '识别愿望与担忧是不是在拉扯同一件事'],
                ['name' => '最终走向', 'focus' => '若沿当前路径推进，事情收束的方向', 'advice' => '把现在的每一步都当成通往结果的铺垫'],
            ],
            default => [
                ['name' => '过去', 'focus' => '旧经验与背景', 'advice' => '先理解来时路'],
                ['name' => '现在', 'focus' => '当前局势', 'advice' => '聚焦正在发生的事'],
                ['name' => '未来', 'focus' => '后续发展', 'advice' => '提前布局下一步'],
            ],
        };
    }

    /**
     * 将牌义放到具体牌位中做位序化解读。
     */
    protected function buildPositionInterpretation(array $card, array $profile, string $question): string
    {
        $meaning = $this->expandCardMeaning($card);
        $positionName = $profile['name'] ?? '当前牌位';
        $focus = $profile['focus'] ?? '这个议题的关键侧面';
        $advice = $profile['advice'] ?? '顺着牌阵脉络继续观察';
        $element = $card['element'] ?? '';
        $elementAspect = $this->getElementAspect($element);
        $lead = match ($positionName) {
            '今日指引' => "这张单牌先照向{$focus}，像是在提醒你别把重点放散。",
            '过去' => "过去位说明，事情之所以走到现在，根子多半落在{$focus}。",
            '现在' => "现在位最直接，它把眼下真正需要面对的{$focus}摆到了台前。",
            '未来' => "未来位给出的不是宿命结论，而是当前轨迹继续推进后最可能先出现的{$focus}。",
            '障碍/挑战' => "障碍位说得很直白：真正卡住你的，不只是一时情绪，而是{$focus}。",
            '潜意识/基础' => "基础位落在深层，说明许多反应并非偶然，而是被{$focus}持续牵引。",
            '目标可能' => "目标位照见你心里真正想靠近的方向，关键仍在{$focus}。",
            '近期发展' => "近期发展位看的是下一步风向，{$focus}会先浮上来。",
            '你的态度' => "这张牌落在自我位置，提示你当前处理问题的方式正集中在{$focus}。",
            '外部环境' => "外部环境位提醒你，外界真正施加影响的地方在{$focus}。",
            '希望/恐惧' => "希望与恐惧位最容易暴露内在拉扯，核心还是{$focus}。",
            '最终走向' => "结果位看的是收束方式，最后会把问题引回{$focus}。",
            default => "落在「{$positionName}」时，这张牌主要回应{$focus}。",
        };
        $orientationFocus = $this->getOrientationFocusText($card, $positionName);
        $elementText = $element !== ''
            ? "它属{$element}元素，落点自然更偏向{$elementAspect}这一层。"
            : '';

        return $this->normalizeTarotText("{$lead}{$orientationFocus}{$meaning}。{$elementText}回到你问的“{$question}”，{$advice}。");
    }

    protected function getOrientationFocusText(array $card, string $positionName): string
    {
        if (!empty($card['reversed'])) {
            return "逆位说明这股力量在「{$positionName}」上更像延迟、别扭或内耗，不能只看表面动作，得先处理没说出口的阻力。";
        }

        return "正位说明这股力量在「{$positionName}」上仍有顺势展开的空间，关键在于是否愿意正面回应。";
    }


    /**
     * 扩充正逆位牌义，尤其补足权杖/宝剑逆位的深层说明。
     */
    protected function expandCardMeaning(array $card): string
    {
        $baseMeaning = $card['reversed']
            ? ($card['reversed_meaning'] ?: $card['meaning'])
            : $card['meaning'];

        if (!$card['reversed']) {
            return $baseMeaning;
        }

        $name = $card['name'] ?? '';
        if (str_starts_with($name, '权杖')) {
            return $baseMeaning . '；逆位的权杖往往提示行动节奏失衡，可能是热情被压住，也可能是冲得太急，先校准动力再发力更稳。';
        }

        if (str_starts_with($name, '宝剑')) {
            return $baseMeaning . '；逆位的宝剑多指认知压力、沟通偏差或判断失焦，先澄清事实与边界，比立刻下结论更重要。';
        }

        if (str_starts_with($name, '圣杯')) {
            return $baseMeaning . '；逆位的圣杯常见于情绪回流、期待失衡或关系感受堵塞，先安顿内在，再谈下一步回应。';
        }

        if (str_starts_with($name, '星币')) {
            return $baseMeaning . '；逆位的星币通常落在资源调配、现实执行或安全感议题上，先把基础盘稳住，再谈扩张与收获。';
        }

        return $baseMeaning . '；逆位也意味着这张大牌的课题尚未真正整合，外在阻力往往对应内在盲点，需要回头校正。';
    }

    /**
     * 生成更贴题的总结，避免结果总是落回固定模板。
     */
    protected function buildSpreadConclusion(array $cards, string $question, array $profiles): string
    {
        if (empty($cards)) {
            return '当前没有可供解读的牌。';
        }

        $theme = $this->inferQuestionTheme($question);
        $count = count($cards);
        $reversedCount = count(array_filter($cards, static fn($card) => !empty($card['reversed'])));
        $reversalSummary = $reversedCount > 0
            ? "本组牌里有{$reversedCount}张逆位，说明{$theme}议题真正难的不是没有机会，而是内在顾虑、旧习惯或时机延滞还没处理干净。"
            : "这组牌以正位推进为主，说明{$theme}议题并非完全受阻，关键在于是否愿意按牌阵节奏稳步落实。";
        $elementSummary = $this->summarizeDominantElements($cards);

        if ($count === 1) {
            $card = $cards[0];
            $profile = $profiles[0]['name'] ?? '核心位置';
            return $this->normalizeTarotText("这次单牌把重点直接压在「{$profile}」：{$card['name']}提示{$this->getCardStateSnippet($card)}。{$reversalSummary}{$elementSummary}");
        }

        if ($count === 3) {
            $past = $cards[0];
            $present = $cards[1];
            $future = $cards[2];
            $presentAdvice = $profiles[1]['advice'] ?? '先把眼前最关键的一步处理好';

            return $this->normalizeTarotText(
                "就{$theme}而言，过去位的{$past['name']}说明旧脉络一直在发酵：{$this->getCardStateSnippet($past)}；"
                . "现在位的{$present['name']}才是眼下真正要回应的焦点：{$this->getCardStateSnippet($present)}；"
                . "若沿当前轨迹继续推进，未来位的{$future['name']}会把结果引向{$this->getCardStateSnippet($future)}。"
                . "{$reversalSummary}{$elementSummary}现在最值得做的，不是急着猜结局，而是{$presentAdvice}。"
            );
        }

        if ($count === 10) {
            $current = $cards[0];
            $challenge = $cards[1] ?? $current;
            $self = $cards[6] ?? $current;
            $environment = $cards[7] ?? $challenge;
            $outcome = $cards[9] ?? $cards[$count - 1];
            $outcomeAdvice = $profiles[9]['advice'] ?? '先把自己与环境的错位调到同一条线上';

            return $this->normalizeTarotText(
                "凯尔特十字更看重结构张力：当前状态位的{$current['name']}说明局势表层正在经历{$this->getCardStateSnippet($current)}；"
                . "挑战位的{$challenge['name']}则指出真正的绊脚石在{$this->getCardStateSnippet($challenge)}。"
                . "来到人物轴线时，你的态度位{$self['name']}与环境位{$environment['name']}一起说明，内外节奏并没有完全同步。"
                . "最终走向位的{$outcome['name']}不是天降答案，而是当这些张力被处理后最可能收束成的结果：{$this->getCardStateSnippet($outcome)}。"
                . "{$reversalSummary}{$elementSummary}所以这组牌给你的重点，不是套牌义，而是{$outcomeAdvice}。"
            );
        }

        $firstCard = $cards[0];
        $lastCard = $cards[$count - 1];
        $firstProfile = $profiles[0]['name'] ?? '起点';
        $lastProfile = $profiles[$count - 1]['name'] ?? '落点';
        $lastAdvice = $profiles[$count - 1]['advice'] ?? '顺着结果线索提前布局下一步';

        return $this->normalizeTarotText(
            "就{$theme}而言，事情先从「{$firstProfile}」的{$firstCard['name']}展开：{$this->getCardStateSnippet($firstCard)}；"
            . "最后会收束到「{$lastProfile}」的{$lastCard['name']}：{$this->getCardStateSnippet($lastCard)}。"
            . "{$reversalSummary}{$elementSummary}接下来更值得执行的一步，是{$lastAdvice}。"
        );
    }

    protected function summarizeDominantElements(array $cards): string
    {
        $elementCounts = ['风' => 0, '水' => 0, '火' => 0, '土' => 0];
        foreach ($cards as $card) {
            $element = $card['element'] ?? '';
            if (isset($elementCounts[$element])) {
                $elementCounts[$element]++;
            }
        }

        $maxCount = max($elementCounts);
        if ($maxCount <= 0) {
            return '';
        }

        $dominantElements = array_keys(array_filter($elementCounts, static fn($value) => $value === $maxCount));
        $missingElements = array_keys(array_filter($elementCounts, static fn($value) => $value === 0));
        $summary = '';
        if (count($dominantElements) === 1) {
            $element = $dominantElements[0];
            $summary .= "当前主轴偏向{$element}元素，重点自然落在{$this->getElementAspect($element)}。";
        } else {
            $summary .= '当前没有单一元素独占上风，' . implode('、', $dominantElements) . '并行发声，意味着这个议题得多线并看。';
        }

        if ($missingElements !== []) {
            $summary .= '相对偏弱的是' . implode('、', $missingElements) . '元素，对应视角容易被忽略。';
        }

        return $summary;
    }

    protected function getCardStateSnippet(array $card): string
    {
        $meaning = !empty($card['reversed'])
            ? ($card['reversed_meaning'] ?: ($card['meaning'] ?? ''))
            : ($card['meaning'] ?? '');

        return $this->normalizeTarotText($meaning);
    }



    /**
     * 推断问题主题，让总结更贴近用户实际提问。
     */
    protected function inferQuestionTheme(string $question): string
    {
        $question = trim($question);
        $map = [
            '感情' => ['感情', '爱情', '关系', '复合', '暧昧', '伴侣', '婚姻'],
            '事业' => ['工作', '事业', '职场', '跳槽', '创业', '升职'],
            '财务' => ['钱', '财', '投资', '收入', '合作'],
            '学业' => ['学习', '考试', '学校', '专业'],
            '健康' => ['健康', '身体', '状态', '睡眠'],
        ];

        foreach ($map as $theme => $keywords) {
            foreach ($keywords as $keyword) {
                if ($keyword !== '' && mb_strpos($question, $keyword) !== false) {
                    return $theme;
                }
            }
        }

        return '当前议题';
    }

    /**
     * 分析牌阵中的元素分布
     * 风(思想/沟通)、水(情感/直觉)、火(行动/能量)、土(物质/现实)
     */
    protected function analyzeElements(array $cards): string
    {
        $elements = ['风' => 0, '水' => 0, '火' => 0, '土' => 0];
        $elementWeights = ['风' => 0.0, '水' => 0.0, '火' => 0.0, '土' => 0.0];
        $elementMeanings = [
            '风' => '思维、沟通、判断',
            '水' => '情感、直觉、连结',
            '火' => '行动、意志、推动',
            '土' => '现实、资源、落地',
        ];

        // 高精度元素统计（包含位置权重）
        foreach ($cards as $index => $card) {
            $element = $card['element'] ?? '';
            if (isset($elements[$element])) {
                $elements[$element]++;
                
                // 根据位置计算权重（中心位置权重更高）
                $positionWeight = $this->calculateElementPositionWeight($index, count($cards));
                $elementWeights[$element] += $positionWeight;
            }
        }

        $total = max(1, count($cards));
        $totalWeight = array_sum($elementWeights);
        
        // 生成高精度分布分析
        $distribution = [];
        $elementAnalysis = [];
        
        foreach ($elements as $element => $count) {
            if ($count > 0) {
                $percentage = round(($count / $total) * 100, 1);
                $weightPercentage = $totalWeight > 0 ? round(($elementWeights[$element] / $totalWeight) * 100, 1) : 0;
                
                $elementAnalysis[$element] = [
                    'count' => $count,
                    'percentage' => $percentage,
                    'weight' => $elementWeights[$element],
                    'weight_percentage' => $weightPercentage
                ];
                
                $dominance = '';
                if ($weightPercentage >= 40) {
                    $dominance = '（主导）';
                } elseif ($weightPercentage >= 25) {
                    $dominance = '（显著）';
                } elseif ($weightPercentage <= 10) {
                    $dominance = '（次要）';
                }
                
                $distribution[] = "{$element}{$count}张（{$elementMeanings[$element]}，占比{$percentage}%，影响力{$weightPercentage}%{$dominance}）";
            }
        }

        $segments = [];
        if ($distribution !== []) {
            $segments[] = '牌阵里的元素分布是：' . implode('；', $distribution) . '。';
        }

        // 添加平衡性分析
        if (count($elementAnalysis) >= 2) {
            $balanceAnalysis = $this->analyzeElementBalance($elementAnalysis);
            $segments[] = $balanceAnalysis;
        }

        $dominantSummary = $this->summarizeDominantElements($cards);
        if ($dominantSummary !== '') {
            $segments[] = $dominantSummary;
        }

        $relationNotes = $this->collectElementRelationNotes($cards);
        if ($relationNotes !== []) {
            $segments[] = '相邻牌的元素尊严显示：' . implode('；', $relationNotes) . '。';
        }

        return $this->normalizeTarotText(implode(' ', $segments));
    }
    
    /**
     * 计算元素位置权重（中心位置权重更高）
     */
    protected function calculateElementPositionWeight(int $positionIndex, int $totalCards): float
    {
        // 凯尔特十字牌阵的特殊权重分配
        if ($totalCards === 10) {
            $centerPositions = [0, 1, 2, 3]; // 核心位置权重更高
            $importantPositions = [4, 5, 6]; // 重要位置权重中等
            
            if (in_array($positionIndex, $centerPositions)) {
                return 1.5;
            } elseif (in_array($positionIndex, $importantPositions)) {
                return 1.2;
            } else {
                return 1.0;
            }
        }
        
        // 三牌阵：中间位置权重最高
        if ($totalCards === 3) {
            return $positionIndex === 1 ? 1.5 : 1.0;
        }
        
        // 单牌阵：唯一位置权重最高
        if ($totalCards === 1) {
            return 2.0;
        }
        
        // 默认：中心位置权重稍高
        $centerIndex = floor($totalCards / 2);
        return abs($positionIndex - $centerIndex) <= 1 ? 1.3 : 1.0;
    }
    
    /**
     * 分析元素平衡性
     */
    protected function analyzeElementBalance(array $elementAnalysis): string
    {
        $maxWeight = max(array_column($elementAnalysis, 'weight_percentage'));
        $minWeight = min(array_column($elementAnalysis, 'weight_percentage'));
        $weightRange = $maxWeight - $minWeight;
        
        if ($weightRange <= 15) {
            return '【平衡性分析】元素分布较为均衡，各元素影响力差距不大，整体呈现和谐状态。';
        } elseif ($weightRange <= 30) {
            return '【平衡性分析】元素分布有一定偏向，主导元素影响力明显，但其他元素仍有表达空间。';
        } else {
            return '【平衡性分析】元素分布显著失衡，主导元素占据绝对优势，需要注意其他元素的补充。';
        }
    }

    
    /**
     * 分析牌与牌之间的关系
     */
    protected function analyzeCardRelationships(array $cards): string
    {
        $count = count($cards);
        if ($count < 2) {
            return '';
        }

        $positions = $this->getSpreadPositions($count);
        $analysis = [];

        $firstCard = $cards[0];
        $lastCard = $cards[$count - 1];
        $analysis[] = '首尾主线：从「' . $firstCard['name'] . '」走到「' . $lastCard['name'] . '」，' . $this->getCardRelationship($firstCard, $lastCard);

        if ($count === 10) {
            $analysis[] = $this->analyzeCelticCrossRelationships($cards, $positions);
        } elseif ($count >= 3) {
            $flows = [];
            for ($i = 0; $i < $count - 1; $i++) {
                $current = $cards[$i];
                $next = $cards[$i + 1];
                $flows[] = ($positions[$i] ?? ('第' . ($i + 1) . '张'))
                    . '→'
                    . ($positions[$i + 1] ?? ('第' . ($i + 2) . '张'))
                    . '：'
                    . $this->getTransitionDesc($current, $next);
            }
            $analysis[] = '牌位流转：' . implode('；', $flows) . '。';
        }

        $elementNotes = $this->collectElementRelationNotes($cards);
        if ($elementNotes !== []) {
            $analysis[] = '元素脉络：' . implode('；', $elementNotes) . '。';
        }

        $upright = count(array_filter($cards, static fn($c) => !$c['reversed']));
        $reversed = $count - $upright;
        if ($reversed > $upright) {
            $analysis[] = "正逆位分布为正位{$upright}张、逆位{$reversed}张，阻滞面比顺势面更强，解读时要优先看卡点。";
        } elseif ($upright > $reversed) {
            $analysis[] = "正逆位分布为正位{$upright}张、逆位{$reversed}张，整体仍以顺势推进为主。";
        } else {
            $analysis[] = "正逆位分布为正位{$upright}张、逆位{$reversed}张，内在顾虑与外部机会同时存在。";
        }

        return $this->normalizeTarotText(implode(PHP_EOL, $analysis));
    }

    protected function collectElementRelationNotes(array $cards): array
    {
        $notes = [];
        $seen = [];
        for ($i = 0; $i < count($cards) - 1; $i++) {
            $left = $cards[$i];
            $right = $cards[$i + 1];
            $leftElement = $left['element'] ?? '';
            $rightElement = $right['element'] ?? '';
            if ($leftElement === '' || $rightElement === '') {
                continue;
            }

            $pair = $leftElement . '-' . $rightElement;
            if (isset($seen[$pair])) {
                continue;
            }
            $seen[$pair] = true;

            $relation = $this->getElementRelation($leftElement, $rightElement);
            if ($relation !== '') {
                $notes[] = $left['name'] . '与' . $right['name'] . '呈现' . $relation;
            }
        }

        return $notes;
    }

    protected function normalizeTarotText(string $text): string
    {
        $text = preg_replace('/[。]{2,}/u', '。', $text) ?? $text;
        $text = preg_replace('/([，；：]){2,}/u', '$1', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }


    /**
     * 获取当前牌阵的标准牌位名称。
     */
    protected function getSpreadPositions(int $count): array
    {
        return array_map(
            static fn(array $profile) => $profile['name'],
            $this->getSpreadPositionProfiles($count)
        );
    }

    /**
     * 凯尔特十字的关键牌位互读。
     */
    protected function analyzeCelticCrossRelationships(array $cards, array $positions): string
    {
        $pairs = [
            [0, 1, '核心张力'],
            [0, 5, '走势推演'],
            [4, 9, '理想与落点'],
            [6, 7, '自我与环境'],
            [8, 9, '希望/恐惧与结果'],
        ];

        $analysis = ["\n【凯尔特十字交叉解读】"];
        foreach ($pairs as [$from, $to, $label]) {
            if (!isset($cards[$from], $cards[$to])) {
                continue;
            }

            $left = $cards[$from];
            $right = $cards[$to];
            $analysis[] = "{$label}：{$positions[$from]}「{$left['name']}」与{$positions[$to]}「{$right['name']}」——" . $this->getCardRelationship($left, $right);
        }

        return implode('', $analysis);
    }
    
    /**
     * 获取两张牌之间的关系描述
     */
    protected function getCardRelationship(array $card1, array $card2): string
    {
        $leftElement = \app\service\TarotElementService::resolveCardElement($card1);
        $rightElement = \app\service\TarotElementService::resolveCardElement($card2);

        $leftElementText = $leftElement !== '' ? $leftElement : '未知元素';
        $rightElementText = $rightElement !== '' ? $rightElement : '未知元素';
        $sameElement = $leftElement !== '' && $leftElement === $rightElement;
        $bothReversed = !empty($card1['reversed']) && !empty($card2['reversed']);
        $bothUpright = empty($card1['reversed']) && empty($card2['reversed']);

        
        if ($sameElement) {
            $elementGuidance = [
                '风' => '思想层面的连贯性很强，说明你的思考模式较为一致。',
                '水' => '情感流动贯穿始终，内心深处有持续的感受在影响你。',
                '火' => '行动力和热情从开始到结束都很强烈，保持这种能量。',
                '土' => '现实基础稳固，整个过程都有物质层面的支撑。',
            ];
            return "同为{$leftElement}元素，" . ($elementGuidance[$leftElement] ?? '主题能量高度集中。');
        }
        
        if ($bothUpright) {
            $relation = $this->getElementRelation($leftElement, $rightElement);
            $prefix = $relation !== '' ? $relation . ' ' : '';
            return $prefix . "两张均为正位，能量流动顺畅，从{$leftElement}转向{$rightElement}，提示需要从" . $this->getElementAspect($leftElement) . "转向" . $this->getElementAspect($rightElement) . "。";
        }
        
        if ($bothReversed) {
            return "两张均为逆位，可能存在深层的阻碍需要面对，从{$leftElement}的困境中寻求{$rightElement}的突破。";
        }
        
        return "能量状态有所变化，从{$card1['name']}的" . (!empty($card1['reversed']) ? '逆位' : '正位') . "状态转向{$card2['name']}的" . (!empty($card2['reversed']) ? '逆位' : '正位') . "状态。";
    }

    
    /**
     * 获取元素代表的方面
     */
    protected function getElementAspect(string $element): string
    {
        return TarotElementService::getAspect($element);
    }

    
    /**
     * 获取两张牌过渡的描述
     */
    protected function getTransitionDesc(array $from, array $to): string
    {
        return TarotElementService::describeTransition($from, $to);
    }

    
    /**
     * 获取元素之间的关系 (基于西方传统四元素尊严模型 - Elemental Dignities)
     */
    protected function getElementRelation(string $element1, string $element2): string
    {
        return \app\service\TarotElementService::formatRelation($element1, $element2);
    }

    
    /**
     * 保存塔罗占卜记录
     */
    public function saveRecord()
    {
        $data = $this->request->post();
        $user = $this->request->user;
        
        // 参数验证
        if (empty($data['spread_type']) || empty($data['cards'])) {
            return $this->error('缺少必要参数');
        }
        
        $spreadType = $data['spread_type'];
        $question = $data['question'] ?? '';
        $cards = $data['cards'];
        $interpretation = $data['interpretation'] ?? '';
        $aiAnalysis = $data['ai_analysis'] ?? '';
        
        // 验证牌阵类型
        $validSpreads = ['single', 'three', 'celtic', 'relationship'];
        if (!in_array($spreadType, $validSpreads)) {
            return $this->error('牌阵类型不正确');
        }
        
        // 创建记录
        $record = TarotRecord::createRecord(
            $user['sub'],
            $spreadType,
            $question,
            $cards,
            $interpretation,
            $aiAnalysis,
            $this->request->ip()
        );
        
        if ($record) {
            return $this->success([
                'record_id' => $record->id,
                'share_code' => $record->share_code,
            ], '保存成功');
        }
        
        return $this->error('保存失败');
    }
    
    /**
     * 获取塔罗历史记录（分页）
     */
    public function history()
    {
        $user = $this->request->user;
        $page = (int)$this->request->get('page', 1);
        $pageSize = (int)$this->request->get('page_size', 10);
        
        // 限制最大页大小
        $pageSize = min(max($pageSize, 1), 50);
        
        $history = TarotRecord::getUserHistory($user['sub'], $page, $pageSize);
        
        return $this->success($history);
    }
    
    /**
     * 获取单条塔罗记录详情
     */
    public function detail()
    {
        $user = $this->request->user;
        $id = (int)$this->request->get('id');
        
        if (!$id) {
            return $this->error('记录ID不能为空');
        }
        
        $record = TarotRecord::getByIdWithAuth($id, $user['sub']);
        
        if (!$record) {
            return $this->error('记录不存在或无权限查看', 404);
        }
        
        // 如果不是自己的记录，增加查看次数
        if ($record->user_id !== $user['sub']) {
            $record->incrementViewCount();
        }
        
        return $this->success([
            'id' => $record->id,
            'spread_type' => $record->spread_type,
            'spread_name' => $record->getSpreadName(),
            'question' => $record->question,
            'cards' => is_string($record->cards) ? json_decode($record->cards, true) : $record->cards,
            'interpretation' => $record->interpretation,
            'ai_analysis' => $record->ai_analysis,
            'is_public' => $record->is_public,
            'share_code' => $record->share_code,
            'view_count' => $record->view_count,
            'created_at' => $record->created_at,
        ]);
    }
    
    /**
     * 删除塔罗记录
     */
    public function deleteRecord()
    {
        $user = $this->request->user;
        $id = (int)$this->request->post('id');
        
        if (!$id) {
            return $this->error('记录ID不能为空');
        }
        
        if (TarotRecord::deleteById($id, $user['sub'])) {
            return $this->success([], '删除成功');
        }
        
        return $this->error('删除失败，记录不存在');
    }
    
    /**
     * 设置记录公开状态
     */
    public function setPublic()
    {
        $user = $this->request->user;
        $id = (int)$this->request->post('id');
        $isPublic = (bool)$this->request->post('is_public', false);
        
        if (!$id) {
            return $this->error('记录ID不能为空');
        }
        
        $record = TarotRecord::where('id', $id)
            ->where('user_id', $user['sub'])
            ->find();
        
        if (!$record) {
            return $this->error('记录不存在', 404);
        }
        
        if ($record->setPublic($isPublic)) {
            return $this->success([
                'is_public' => $isPublic,
                'share_code' => $record->share_code,
            ], '设置成功');
        }
        
        return $this->error('设置失败');
    }
    
    /**
     * 通过分享码查看塔罗记录（无需登录）
     */
    public function share()
    {
        $shareCode = $this->request->get('code');
        
        if (empty($shareCode)) {
            return $this->error('分享码不能为空');
        }
        
        $record = TarotRecord::findByShareCode($shareCode);
        
        if (!$record) {
            return $this->error('分享记录不存在或已失效', 404);
        }
        
        // 增加查看次数
        $record->incrementViewCount();
        $record = TarotRecord::find((int) $record->id) ?: $record;
        
        return $this->success([
            'id' => (int) $record->id,
            'user_id' => (int) $record->user_id,
            'spread_type' => $record->getSpreadTypeValue(),
            'spread_name' => $record->getSpreadName(),
            'question' => $record->getQuestionValue(),
            'cards' => is_string($record->cards) ? (json_decode($record->cards, true) ?: []) : ($record->cards ?: []),
            'interpretation' => $record->interpretation,
            'ai_analysis' => $record->ai_analysis,
            'is_public' => $record->isPublicRecord() ? 1 : 0,
            'share_code' => $record->getShareCodeValue(),
            'view_count' => $record->getViewCountValue(),
            'created_at' => $record->getCreatedAtValue(),
            'share_supported' => $record->canShare(),
        ]);
    }
}
