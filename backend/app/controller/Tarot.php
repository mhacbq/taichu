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
    
    // 塔罗牌数组（包含图片/emoji和颜色）
    protected $tarotCards = [
        ['name' => '愚者', 'meaning' => '新的开始，冒险，纯真', 'emoji' => '🃏', 'color' => '#ff9f43', 'element' => '风'],
        ['name' => '魔术师', 'meaning' => '创造力，技巧，意志力', 'emoji' => '🎩', 'color' => '#ff6b6b', 'element' => '风'],
        ['name' => '女祭司', 'meaning' => '直觉，神秘，潜意识', 'emoji' => '🌙', 'color' => '#4834d4', 'element' => '水'],
        ['name' => '皇后', 'meaning' => '丰饶，母性，自然', 'emoji' => '👑', 'color' => '#6ab04c', 'element' => '土'],
        ['name' => '皇帝', 'meaning' => '权威，结构，父亲', 'emoji' => '⚡', 'color' => '#eb4d4b', 'element' => '火'],
        ['name' => '教皇', 'meaning' => '传统，精神指导，信仰', 'emoji' => '🔑', 'color' => '#f9ca24', 'element' => '土'],
        ['name' => '恋人', 'meaning' => '爱情，选择，和谐', 'emoji' => '💕', 'color' => '#ff7979', 'element' => '风'],
        ['name' => '战车', 'meaning' => '意志力，胜利，控制', 'emoji' => '🏆', 'color' => '#22a6b3', 'element' => '水'],
        ['name' => '力量', 'meaning' => '勇气，耐心，内在力量', 'emoji' => '🦁', 'color' => '#f0932b', 'element' => '火'],
        ['name' => '隐者', 'meaning' => '内省，孤独，指引', 'emoji' => '🕯️', 'color' => '#535c68', 'element' => '土'],
        ['name' => '命运之轮', 'meaning' => '命运，周期，转折点', 'emoji' => '☸️', 'color' => '#be2edd', 'element' => '火'],
        ['name' => '正义', 'meaning' => '公正，真理，因果', 'emoji' => '⚖️', 'color' => '#30336b', 'element' => '风'],
        ['name' => '倒吊人', 'meaning' => '牺牲，等待，新视角', 'emoji' => '🙃', 'color' => '#16a085', 'element' => '水'],
        ['name' => '死神', 'meaning' => '结束，转变，新生', 'emoji' => '💀', 'color' => '#2f3542', 'element' => '水'],
        ['name' => '节制', 'meaning' => '平衡，调和，耐心', 'emoji' => '🏺', 'color' => '#7bed9f', 'element' => '火'],
        ['name' => '恶魔', 'meaning' => '束缚，物质主义，诱惑', 'emoji' => '👿', 'color' => '#8B0000', 'element' => '土'],
        ['name' => '塔', 'meaning' => '突然变化，觉醒，破坏', 'emoji' => '🗼', 'color' => '#e74c3c', 'element' => '火'],
        ['name' => '星星', 'meaning' => '希望，灵感，宁静', 'emoji' => '⭐', 'color' => '#74b9ff', 'element' => '风'],
        ['name' => '月亮', 'meaning' => '幻觉，恐惧，潜意识', 'emoji' => '🌕', 'color' => '#dfe6e9', 'element' => '水'],
        ['name' => '太阳', 'meaning' => '快乐，成功，活力', 'emoji' => '☀️', 'color' => '#f1c40f', 'element' => '火'],
        ['name' => '审判', 'meaning' => '重生，觉醒，宽恕', 'emoji' => '📯', 'color' => '#9b59b6', 'element' => '火'],
        ['name' => '世界', 'meaning' => '完成，成就，旅行', 'emoji' => '🌍', 'color' => '#3498db', 'element' => '土'],
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
            $interpretation .= "抽到的牌是【{$card['name']}】{$position}。\n";
            $interpretation .= "牌意：{$card['meaning']}。\n";
            $interpretation .= "元素属性：{$card['element']}\n\n";
            $interpretation .= "这张牌指示您当前的状况与上述牌意相关，建议您仔细思考其中的启示。";
        } else {
            $positions = ['过去', '现在', '未来', '原因', '环境', '建议', '结果'];
            
            $interpretation .= "您的牌阵解读如下：\n\n";
            
            foreach ($cards as $index => $card) {
                $position = $card['reversed'] ? '逆位' : '正位';
                $posName = $positions[$index] ?? "位置" . ($index + 1);
                
                $interpretation .= "【{$posName}】{$card['name']} {$position}\n";
                $interpretation .= "代表：{$card['meaning']} | 元素：{$card['element']}\n\n";
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
            return "同元素{$from['element']}的深化，从「{$from['meaning']}」发展为「{$to['meaning']}」";
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
     * 获取元素之间的关系
     */
    protected function getElementRelation(string $element1, string $element2): string
    {
        if ($element1 === $element2) {
            return '同元素强化，能量叠加增强';
        }
        
        // 元素相生关系
        $generating = [
            '风' => '火',
            '火' => '土',
            '土' => '水',
            '水' => '风',
        ];
        
        // 元素相克关系
        $restraining = [
            '风' => '土',
            '土' => '水',
            '水' => '火',
            '火' => '风',
        ];
        
        if ($generating[$element1] === $element2) {
            return "{$element1}生{$element2}，能量滋养流动，利于发展";
        }
        
        if ($restraining[$element1] === $element2) {
            return "{$element1}克{$element2}，存在制约关系，需要调和";
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
