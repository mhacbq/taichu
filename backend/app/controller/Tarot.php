<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\PointsRecord;
use think\facade\Db;

class Tarot extends BaseController
{
    // 塔罗占卜所需积分
    const TAROT_POINTS_COST = 5;
    
    protected $middleware = [\app\middleware\Auth::class];
    
    // 塔罗牌数组
    protected $tarotCards = [
        ['name' => '愚者', 'meaning' => '新的开始，冒险，纯真'],
        ['name' => '魔术师', 'meaning' => '创造力，技巧，意志力'],
        ['name' => '女祭司', 'meaning' => '直觉，神秘，潜意识'],
        ['name' => '皇后', 'meaning' => '丰饶，母性，自然'],
        ['name' => '皇帝', 'meaning' => '权威，结构，父亲'],
        ['name' => '教皇', 'meaning' => '传统，精神指导，信仰'],
        ['name' => '恋人', 'meaning' => '爱情，选择，和谐'],
        ['name' => '战车', 'meaning' => '意志力，胜利，控制'],
        ['name' => '力量', 'meaning' => '勇气，耐心，内在力量'],
        ['name' => '隐者', 'meaning' => '内省，孤独，指引'],
        ['name' => '命运之轮', 'meaning' => '命运，周期，转折点'],
        ['name' => '正义', 'meaning' => '公正，真理，因果'],
        ['name' => '倒吊人', 'meaning' => '牺牲，等待，新视角'],
        ['name' => '死神', 'meaning' => '结束，转变，新生'],
        ['name' => '节制', 'meaning' => '平衡，调和，耐心'],
        ['name' => '恶魔', 'meaning' => '束缚，物质主义，诱惑'],
        ['name' => '塔', 'meaning' => '突然变化，觉醒，破坏'],
        ['name' => '星星', 'meaning' => '希望，灵感，宁静'],
        ['name' => '月亮', 'meaning' => '幻觉，恐惧，潜意识'],
        ['name' => '太阳', 'meaning' => '快乐，成功，活力'],
        ['name' => '审判', 'meaning' => '重生，觉醒，宽恕'],
        ['name' => '世界', 'meaning' => '完成，成就，旅行'],
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
        
        // 扣除积分
        Db::startTrans();
        try {
            $userModel->deductPoints(self::TAROT_POINTS_COST);
            
            PointsRecord::record(
                $user['sub'],
                '塔罗占卜消耗',
                -self::TAROT_POINTS_COST,
                'tarot',
                0,
                "牌阵: {$spread}"
            );
            
            Db::commit();
            
            return $this->success([
                'cards' => $cards,
                'spread' => $spread,
                'points_cost' => self::TAROT_POINTS_COST,
                'remaining_points' => $userModel->points,
            ]);
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('抽牌失败: ' . $e->getMessage());
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
            $interpretation .= "牌意：{$card['meaning']}。\n\n";
            $interpretation .= "这张牌指示您当前的状况与上述牌意相关，建议您仔细思考其中的启示。";
        } else {
            $positions = ['过去', '现在', '未来', '原因', '环境', '建议', '结果'];
            
            $interpretation .= "您的牌阵解读如下：\n\n";
            
            foreach ($cards as $index => $card) {
                $position = $card['reversed'] ? '逆位' : '正位';
                $posName = $positions[$index] ?? "位置" . ($index + 1);
                
                $interpretation .= "【{$posName}】{$card['name']} {$position}\n";
                $interpretation .= "代表：{$card['meaning']}\n\n";
            }
            
            $interpretation .= "综合来看，您的问题已经有了答案。建议您保持开放的心态，接受命运的指引。无论结果如何，都是您成长的机会。";
        }
        
        return $interpretation;
    }
}
