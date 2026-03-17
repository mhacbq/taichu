<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\PointsRecord;
use app\model\TarotRecord;
use think\facade\Db;
use think\facade\Log;

class Tarot extends BaseController
{
    // 塔罗占卜所需积分
    const TAROT_POINTS_COST = 5;
    
    protected $middleware = [\app\middleware\Auth::class];
    
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
     * 抽牌
     */
    public function draw()
    {
        $data = $this->request->post();
        $user = $this->request->user;
        
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
            
            // 3. 记录积分变动（使用Db保持在同一事务）
            Db::name('tc_points_record')->insert([
                'user_id' => $user['sub'],
                'action' => '塔罗占卜消耗',
                'points' => -self::TAROT_POINTS_COST,
                'type' => 'tarot',
                'related_id' => 0,
                'remark' => "牌阵: {$spread}",
                'created_at' => date('Y-m-d H:i:s'),
            ]);
            
            Db::commit();
            
            return $this->success([
                'cards' => $cards,
                'spread' => $spread,
                'points_cost' => self::TAROT_POINTS_COST,
                'remaining_points' => $userData['points'] - self::TAROT_POINTS_COST,
            ]);
        } catch (\Exception $e) {
            Db::rollback();
            Log::error('塔罗抽牌失败: ' . $e->getMessage(), [
                'user_id' => $user['sub'] ?? 0,
                'spread' => $spread,
                'trace' => $e->getTraceAsString()
            ]);
            return $this->error('抽牌失败，请稍后重试', 500);
        }
    }
    
    /**
     * 解读牌阵
     */
    public function interpret()
    {
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
    }
    
    /**
     * 抽取指定数量的牌
     */
    protected function drawCards(int $num): array
    {
        $cards = [];
        $keys = array_rand($this->tarotCards, $num);
        
        if (!is_array($keys)) {
            $keys = [$keys];
        }
        
        foreach ($keys as $key) {
            $card = $this->tarotCards[$key];
            $cards[] = [
                'name' => $card['name'],
                'meaning' => $card['meaning'],
                'reversed_meaning' => $card['reversed_meaning'] ?? '',
                'emoji' => $card['emoji'],
                'color' => $card['color'],
                'element' => $card['element'],
                'reversed' => mt_rand(0, 1) === 1, // 随机正逆位
            ];
        }
        
        return $cards;
    }
    
    /**
     * 生成解读
     */
    protected function generateInterpretation(array $cards, string $question): string
    {
        $count = count($cards);
        
        $interpretation = "针对您的问题：「{$question}」\n\n";
        
        if ($count === 1) {
            $card = $cards[0];
            $position = $card['reversed'] ? '逆位' : '正位';
            $meaning = $card['reversed'] ? ($card['reversed_meaning'] ?: '（逆位含义待补充）') : $card['meaning'];
            $interpretation .= "抽到的牌是【{$card['name']}】{$position}。\n";
            $interpretation .= "牌意：{$meaning}。\n";
            $interpretation .= "元素属性：{$card['element']}\n\n";
            $interpretation .= "这张牌指示您当前的状况与上述牌意相关，建议您仔细思考其中的启示。";
        } else {
            // 根据不同牌阵定义牌位
            $positions = match($count) {
                3 => ['过去', '现在', '未来'],
                10 => [
                    '当前状态', '障碍/挑战', '潜意识/基础', '过去', 
                    '目标/理想', '不久的将来', '自我表现', '环境影响', 
                    '希望与恐惧', '最终结果'
                ],
                default => ['过去', '现在', '未来', '原因', '环境', '建议', '结果']
            };
            
            $interpretation .= "您的【" . ($count == 10 ? "凯尔特十字" : ($count == 3 ? "三牌阵" : "通用")) . "】牌阵解读如下：\n\n";
            
            foreach ($cards as $index => $card) {
                $position = $card['reversed'] ? '逆位' : '正位';
                $posName = $positions[$index] ?? "位置" . ($index + 1);
                $meaning = $card['reversed'] ? ($card['reversed_meaning'] ?: '（逆位含义待补充）') : $card['meaning'];
                
                $interpretation .= "【{$posName}】{$card['name']} {$position}\n";
                $interpretation .= "代表：{$meaning} | 元素：{$card['element']}\n\n";
            }
            
            // 添加元素分析
            $elementAnalysis = $this->analyzeElements($cards);
            $interpretation .= "【元素分析】\n{$elementAnalysis}\n\n";
            
            // 添加关系分析
            if ($count >= 2) {
                $relationshipAnalysis = $this->analyzeCardRelationships($cards);
                $interpretation .= "【牌阵关系分析】\n{$relationshipAnalysis}\n\n";
            }
            
            $interpretation .= "综合来看，您的问题已经有了答案。建议您保持开放的心态，接受命运的指引。无论结果如何，都是您成长的机会。";
        }
        
        return $interpretation;
    }
    
    /**
     * 分析牌阵中的元素分布
     * 风(思想/沟通)、水(情感/直觉)、火(行动/能量)、土(物质/现实)
     */
    protected function analyzeElements(array $cards): string
    {
        $elements = ['风' => 0, '水' => 0, '火' => 0, '土' => 0];
        $elementMeanings = [
            '风' => '思维、沟通、变化、理智',
            '水' => '情感、直觉、潜意识、流动',
            '火' => '行动、能量、热情、意志',
            '土' => '物质、现实、稳定、身体',
        ];
        
        foreach ($cards as $card) {
            if (isset($elements[$card['element']])) {
                $elements[$card['element']]++;
            }
        }
        
        $total = count($cards);
        $analysis = [];
        
        // 计算各元素占比
        foreach ($elements as $element => $count) {
            if ($count > 0) {
                $percentage = round(($count / $total) * 100);
                $analysis[] = "{$element}元素({$elementMeanings[$element]})：{$count}张({$percentage}%)";
            }
        }
        
        // 分析主导元素
        arsort($elements);
        $dominant = array_key_first($elements);
        $dominantCount = $elements[$dominant];
        
        $result = implode("、", $analysis);
        $result .= "\n\n牌阵主导元素是【{$dominant}】，";
        
        $guidance = [
            '风' => '提示您需要更多理性思考和有效沟通来解决当前问题。',
            '水' => '暗示情感和直觉在当前处境中起主导作用，请倾听内心的声音。',
            '火' => '表明现在是需要行动和决断的时候，用热情和意志克服困难。',
            '土' => '强调物质基础和现实条件的重要性，务实稳健地处理问题。',
        ];
        
        $result .= $guidance[$dominant];
        
        // 检查元素缺失
        $missing = array_filter($elements, fn($v) => $v === 0);
        if (!empty($missing)) {
            $missingNames = array_keys($missing);
            $result .= "\n\n注意：牌阵中缺少" . implode('、', $missingNames) . "元素，";
            $missingGuidance = [
                '风' => '可能意味着思考不够清晰或沟通存在障碍。',
                '水' => '可能表示情感被压抑或忽视了直觉的作用。',
                '火' => '可能暗示行动力不足或缺乏足够的热情。',
                '土' => '可能说明对现实条件的关注不够或基础不够稳固。',
            ];
            $result .= $missingGuidance[$missingNames[0]];
        }
        
        return $result;
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
        
        $analysis = [];
        
        // 分析第一张和最后一张牌的关系（整体趋势）
        $firstCard = $cards[0];
        $lastCard = $cards[$count - 1];
        
        $analysis[] = "【首尾呼应】从「{$firstCard['name']}」到「{$lastCard['name']}」，";
        $analysis[] = $this->getCardRelationship($firstCard, $lastCard);
        
        // 分析相邻牌的关系
        if ($count >= 3) {
            $analysis[] = "\n【牌位流转】";
            for ($i = 0; $i < $count - 1; $i++) {
                $current = $cards[$i];
                $next = $cards[$i + 1];
                $position = $i + 1;
                $analysis[] = "第{$position}张到第" . ($position + 1) . "张：" . $this->getTransitionDesc($current, $next);
            }
        }
        
        // 分析元素互补或冲突
        $elementPairs = [];
        for ($i = 0; $i < min(3, $count - 1); $i++) {
            $pair = $cards[$i]['element'] . '-' . $cards[$i + 1]['element'];
            if (!in_array($pair, $elementPairs)) {
                $elementPairs[] = $pair;
                $relation = $this->getElementRelation($cards[$i]['element'], $cards[$i + 1]['element']);
                if ($relation) {
                    $analysis[] = "\n【元素互动】{$cards[$i]['name']}({$cards[$i]['element']})与{$cards[$i + 1]['name']}({$cards[$i + 1]['element']})：{$relation}";
                }
            }
        }
        
        // 正逆位分析
        $upright = count(array_filter($cards, fn($c) => !$c['reversed']));
        $reversed = $count - $upright;
        $analysis[] = "\n【正逆位分布】正位{$upright}张，逆位{$reversed}张。";
        if ($reversed > $upright) {
            $analysis[] = "逆位牌较多，提示当前可能存在阻碍或需要更多内省。";
        } elseif ($upright > $reversed) {
            $analysis[] = "正位牌较多，整体能量流动较为顺畅。";
        } else {
            $analysis[] = "正逆位平衡，暗示内在外在因素交织影响。";
        }
        
        return implode("", $analysis);
    }
    
    /**
     * 获取两张牌之间的关系描述
     */
    protected function getCardRelationship(array $card1, array $card2): string
    {
        $sameElement = $card1['element'] === $card2['element'];
        $bothReversed = $card1['reversed'] && $card2['reversed'];
        $bothUpright = !$card1['reversed'] && !$card2['reversed'];
        
        if ($sameElement) {
            $elementGuidance = [
                '风' => '思想层面的连贯性很强，说明您的思考模式较为一致。',
                '水' => '情感流动贯穿始终，内心深处有持续的感受在影响您。',
                '火' => '行动力和热情从开始到结束都很强烈，保持这种能量。',
                '土' => '现实基础稳固，整个过程都有物质层面的支撑。',
            ];
            return "同为{$card1['element']}元素，" . $elementGuidance[$card1['element']];
        }
        
        if ($bothUpright) {
            return "两张均为正位，能量流动顺畅，从{$card1['element']}过渡到{$card2['element']}，提示需要从" . $this->getElementAspect($card1['element']) . "转向" . $this->getElementAspect($card2['element']) . "。";
        }
        
        if ($bothReversed) {
            return "两张均为逆位，可能存在深层的阻碍需要面对，从{$card1['element']}的困境中寻求{$card2['element']}的突破。";
        }
        
        return "能量状态有所变化，从{$card1['name']}的" . ($card1['reversed'] ? '逆位' : '正位') . "状态转向{$card2['name']}的" . ($card2['reversed'] ? '逆位' : '正位') . "状态。";
    }
    
    /**
     * 获取元素代表的方面
     */
    protected function getElementAspect(string $element): string
    {
        return match($element) {
            '风' => '理性思考',
            '水' => '情感感受',
            '火' => '行动执行',
            '土' => '物质现实',
            default => '内在能量',
        };
    }
    
    /**
     * 获取两张牌过渡的描述
     */
    protected function getTransitionDesc(array $from, array $to): string
    {
        $sameElement = $from['element'] === $to['element'];
        
        if ($sameElement) {
            $fromMeaning = $from['reversed'] ? ($from['reversed_meaning'] ?: $from['meaning']) : $from['meaning'];
            $toMeaning = $to['reversed'] ? ($to['reversed_meaning'] ?: $to['meaning']) : $to['meaning'];
            return "同元素{$from['element']}的深化，从「{$fromMeaning}」发展为「{$toMeaning}」";
        }
        
        $transitions = [
            '风-水' => '从理性思考进入情感层面',
            '风-火' => '想法转化为行动',
            '风-土' => '构思落实为现实',
            '水-风' => '情感得到理性梳理',
            '水-火' => '情感激发行动力',
            '水-土' => '情感需要现实承载',
            '火-风' => '行动前需要更多思考',
            '火-水' => '行动影响情感状态',
            '火-土' => '行动力转化为实际成果',
            '土-风' => '现实需要重新规划',
            '土-水' => '物质影响情感体验',
            '土-火' => '现实条件激发行动',
        ];
        
        $key = $from['element'] . '-' . $to['element'];
        $desc = $transitions[$key] ?? "从{$from['element']}转向{$to['element']}";
        
        return "{$desc}，{$from['name']} → {$to['name']}";
    }
    
    /**
     * 获取元素之间的关系 (基于西方传统四元素尊严模型 - Elemental Dignities)
     */
    protected function getElementRelation(string $element1, string $element2): string
    {
        if ($element1 === $element2) {
            return '同元素（Mutual Dignity）：主题被持续放大，牌阵焦点十分明确。';
        }
        
        $pair = [$element1, $element2];
        sort($pair);
        $pairStr = implode('-', $pair);

        // 友方元素 (Friendly Dignity)
        $friendly = [
            '火-风' => '火与风形成 Friendly Dignity，意志得到思想鼓动，行动更容易迅速展开。',
            '水-土' => '水与土形成 Friendly Dignity，情绪获得承载，现实层面更容易稳步落地。',
        ];

        // 敌对元素 (Enemy Dignity)
        $hostile = [
            '水-火' => '水与火形成 Enemy Dignity，感受与行动互相牵制，局势容易出现内耗。',
            '土-风' => '土与风形成 Enemy Dignity，理念表达受现实框架压制，推进阻力较大。',
        ];

        // 中性元素 (Neutral Dignity)
        $neutral = [
            '火-土' => '火与土属于 Neutral Dignity，行动有落点，但速度会被现实节奏放缓。',
            '水-风' => '水与风属于 Neutral Dignity，理性与感受并行，需要额外整合后才能形成结论。',
        ];

        if (isset($friendly[$pairStr])) {
            return '友方元素：' . $friendly[$pairStr];
        }

        if (isset($hostile[$pairStr])) {
            return '敌对元素：' . $hostile[$pairStr];
        }

        if (isset($neutral[$pairStr])) {
            return '中性元素：' . $neutral[$pairStr];
        }
        
        return '';
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
        
        return $this->success([
            'spread_type' => $record->spread_type,
            'spread_name' => $record->getSpreadName(),
            'question' => $record->question,
            'cards' => is_string($record->cards) ? json_decode($record->cards, true) : $record->cards,
            'interpretation' => $record->interpretation,
            'view_count' => $record->view_count,
            'created_at' => $record->created_at,
        ]);
    }
}
