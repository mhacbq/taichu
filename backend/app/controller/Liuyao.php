<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\LiuyaoRecord;
use app\model\User;
use app\service\AiService;
use app\service\CacheService;
use think\facade\Db;

/**
 * 六爻占卜控制器
 * 
 * 六爻是中国传统占卜方法，通过六次掷钱币得到六个爻，组成一个卦象
 * 每个爻可以是：老阴(0)、少阴(1)、少阳(2)、老阳(3)
 * 老阴和老阳为动爻，会产生变卦
 */
class Liuyao extends BaseController
{
    // 六爻占卜所需积分
    const LIUYAO_POINTS_COST = 15;
    
    // 是否启用缓存
    const ENABLE_CACHE = true;
    
    protected $middleware = [\app\middleware\Auth::class];
    
    /**
     * 六爻名称映射
     */
    protected $yaoNames = [
        0 => '老阴',  // 变爻，阴变阳
        1 => '少阴',  // 静爻，保持阴
        2 => '少阳',  // 静爻，保持阳
        3 => '老阳',  // 变爻，阳变阴
    ];
    
    /**
     * 爻符号
     */
    protected $yaoSymbols = [
        0 => '⚋',  // 老阴 ×
        1 => '⚋',  // 少阴 -
        2 => '⚊',  // 少阳 -
        3 => '⚊',  // 老阳 ○
    ];
    
    /**
     * 获取六爻定价
     */
    public function getPricing()
    {
        $user = $this->request->user;
        $userModel = User::find($user['sub']);
        $isVip = $userModel->is_vip ?? false;
        
        $basePoints = config('app.points_cost_liuyao', self::LIUYAO_POINTS_COST);
        
        // VIP免费
        if ($isVip) {
            return $this->success([
                'cost' => 0,
                'original_cost' => $basePoints,
                'is_vip_free' => true,
                'message' => 'VIP用户免费',
            ]);
        }
        
        // 首次免费
        $isFirst = LiuyaoRecord::where('user_id', $user['sub'])->count() === 0;
        
        return $this->success([
            'cost' => $isFirst ? 0 : $basePoints,
            'original_cost' => $basePoints,
            'is_first_free' => $isFirst,
            'is_vip_free' => false,
            'message' => $isFirst ? '首次占卜免费' : "每次消耗{$basePoints}积分",
        ]);
    }
    
    /**
     * 执行六爻占卜
     */
    public function divination()
    {
        $data = $this->request->post();
        $user = $this->request->user;
        
        // 验证参数
        if (empty($data['question'])) {
            return $this->error('请输入占卜问题');
        }
        
        $question = trim($data['question']);
        $useAi = $data['useAi'] ?? true;
        
        if (mb_strlen($question) < 2 || mb_strlen($question) > 100) {
            return $this->error('问题长度应在2-100字之间');
        }
        
        // 生成六爻结果（模拟掷钱币）
        $yaoResult = $this->generateYaoResult();
        
        // 计算卦象
        $guaInfo = $this->calculateGua($yaoResult);
        
        // 使用行锁处理积分
        Db::startTrans();
        try {
            // 1. 查询用户（带行锁）
            $userData = Db::name('tc_user')
                ->where('id', $user['sub'])
                ->where('status', 1)
                ->lock(true)
                ->find();
            
            if (!$userData) {
                Db::rollback();
                return $this->error('用户不存在', 404);
            }
            
            // 2. 检查是否是首次
            $recordCount = LiuyaoRecord::where('user_id', $user['sub'])->count();
            $isFirst = $recordCount === 0;
            $isVip = $userData['is_vip'] ?? false;
            $needPoints = !$isFirst && !$isVip;
            $pointsCost = $needPoints ? self::LIUYAO_POINTS_COST : 0;
            
            // 3. 检查积分
            if ($needPoints && $userData['points'] < $pointsCost) {
                Db::rollback();
                return $this->error('积分不足，请先充值', 403, [
                    'need_points' => $pointsCost,
                    'current_points' => $userData['points'],
                ]);
            }
            
            // 4. 扣除积分
            if ($needPoints) {
                $updateResult = Db::name('tc_user')
                    ->where('id', $user['sub'])
                    ->dec('points', $pointsCost)
                    ->update();
                
                if ($updateResult === 0) {
                    Db::rollback();
                    return $this->error('积分扣除失败');
                }
                
                // 记录积分变动
                Db::name('tc_points_record')->insert([
                    'user_id' => $user['sub'],
                    'action' => '六爻占卜消耗',
                    'points' => -$pointsCost,
                    'type' => 'liuyao',
                    'related_id' => 0,
                    'remark' => "问题: " . mb_substr($question, 0, 20),
                    'created_at' => date('Y-m-d H:i:s'),
                ]);
            }
            
            // 5. AI分析
            $aiAnalysis = null;
            if ($useAi) {
                $aiAnalysis = $this->generateAiAnalysis($question, $guaInfo, $yaoResult);
            }
            
            // 6. 保存记录
            $record = LiuyaoRecord::create([
                'user_id' => $user['sub'],
                'question' => $question,
                'yao_result' => implode('', $yaoResult),
                'gua_code' => $guaInfo['code'],
                'gua_name' => $guaInfo['name'],
                'gua_ci' => $guaInfo['gua_ci'] ?? '',
                'yao_ci' => json_encode($guaInfo['yao_ci'] ?? []),
                'interpretation' => $this->generateInterpretation($question, $guaInfo, $yaoResult),
                'ai_analysis' => $aiAnalysis ? json_encode($aiAnalysis) : null,
                'is_ai_analysis' => $useAi,
                'consumed_points' => $pointsCost,
            ]);
            
            Db::commit();
            
            // 查询最新积分
            $remainingPoints = Db::name('tc_user')
                ->where('id', $user['sub'])
                ->value('points');
            
            return $this->success([
                'id' => $record->id,
                'question' => $question,
                'yao_result' => $yaoResult,
                'yao_names' => array_map(function($yao) {
                    return $this->yaoNames[$yao];
                }, $yaoResult),
                'gua' => $guaInfo,
                'interpretation' => $record->interpretation,
                'ai_analysis' => $aiAnalysis,
                'points_cost' => $pointsCost,
                'remaining_points' => $remainingPoints,
                'is_first' => $isFirst,
            ]);
            
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('占卜失败: ' . $e->getMessage());
        }
    }
    
    /**
     * 生成六爻结果（模拟钱币占卜）
     * 
     * 三个钱币：
     * - 三个正面（字）：老阳（3）
     * - 两个正面一个反面：少阴（1）
     * - 一个正面两个反面：少阳（2）
     * - 三个反面：老阴（0）
     * 
     * 记录顺序：从下到上（初爻到上爻）
     */
    protected function generateYaoResult(): array
    {
        $result = [];
        for ($i = 0; $i < 6; $i++) {
            // 模拟三个钱币：0=反面，1=正面
            $coins = [
                random_int(0, 1),
                random_int(0, 1),
                random_int(0, 1),
            ];
            $sum = array_sum($coins);
            
            // 根据传统钱币占卜规则
            switch ($sum) {
                case 0: // 三个反面（背）
                    $result[] = 0; // 老阴
                    break;
                case 1: // 一个正面
                    $result[] = 2; // 少阳
                    break;
                case 2: // 两个正面
                    $result[] = 1; // 少阴
                    break;
                case 3: // 三个正面
                    $result[] = 3; // 老阳
                    break;
            }
        }
        return $result;
    }
    
    /**
     * 计算卦象
     * 
     * 六爻组成一个卦，每两爻组成一爻（初爻+二爻=下卦，四爻+五爻+上爻=上卦）
     * 阳爻(2,3)记为1，阴爻(0,1)记为0
     * 三爻组成一个八卦：000=坤，001=震，010=坎，011=兑，100=艮，101=离，110=巽，111=乾
     */
    protected function calculateGua(array $yaoResult): array
    {
        // 下卦（初爻、二爻、三爻）- 从下到上
        $lower = ($yaoResult[0] >= 2 ? 1 : 0) 
               + ($yaoResult[1] >= 2 ? 2 : 0) 
               + ($yaoResult[2] >= 2 ? 4 : 0);
        
        // 上卦（四爻、五爻、上爻）
        $upper = ($yaoResult[3] >= 2 ? 1 : 0) 
               + ($yaoResult[4] >= 2 ? 2 : 0) 
               + ($yaoResult[5] >= 2 ? 4 : 0);
        
        // 卦象代码：上卦+下卦
        $guaCode = $upper . $lower;
        
        // 查询卦象信息
        $guaInfo = Db::name('liuyao_gua')
            ->where('gua_code', $guaCode)
            ->find();
        
        if (!$guaInfo) {
            // 默认返回坤卦
            return [
                'code' => '00',
                'name' => '坤为地',
                'gua_ci' => '元亨，利牝马之贞。',
                'wuxing' => '土',
                'gong' => '坤宫',
            ];
        }
        
        // 提取动爻信息
        $movingYao = [];
        foreach ($yaoResult as $index => $yao) {
            if ($yao == 0 || $yao == 3) {
                $yaoNumber = $index + 1;
                $movingYao[] = [
                    'position' => $yaoNumber,
                    'name' => ['初爻', '二爻', '三爻', '四爻', '五爻', '上爻'][$index],
                    'type' => $yao == 0 ? '老阴' : '老阳',
                    'ci' => $guaInfo['yao_' . $yaoNumber] ?? '',
                ];
            }
        }
        
        return [
            'code' => $guaInfo['gua_code'],
            'name' => $guaInfo['gua_name'],
            'gua_ci' => $guaInfo['gua_ci'] ?? '',
            'xiang_ci' => $guaInfo['xiang_ci'] ?? '',
            'tuan_ci' => $guaInfo['tuan_ci'] ?? '',
            'wuxing' => $guaInfo['wuxing'] ?? '',
            'gong' => $guaInfo['gong'] ?? '',
            'description' => $guaInfo['description'] ?? '',
            'yao_ci' => [
                $guaInfo['yao_1'] ?? '',
                $guaInfo['yao_2'] ?? '',
                $guaInfo['yao_3'] ?? '',
                $guaInfo['yao_4'] ?? '',
                $guaInfo['yao_5'] ?? '',
                $guaInfo['yao_6'] ?? '',
            ],
            'moving_yao' => $movingYao,
        ];
    }
    
    /**
     * 生成基础解读
     */
    protected function generateInterpretation(string $question, array $guaInfo, array $yaoResult): string
    {
        $movingCount = count(array_filter($yaoResult, function($yao) {
            return $yao == 0 || $yao == 3;
        }));
        
        $interpretation = "占问：{$question}\n\n";
        $interpretation .= "所得卦象：{$guaInfo['name']}\n";
        $interpretation .= "卦辞：{$guaInfo['gua_ci']}\n\n";
        
        if (!empty($guaInfo['description'])) {
            $interpretation .= "卦象含义：{$guaInfo['description']}\n\n";
        }
        
        if ($movingCount > 0) {
            $interpretation .= "动爻分析：\n";
            foreach ($guaInfo['moving_yao'] ?? [] as $yao) {
                $interpretation .= "{$yao['name']}（{$yao['type']}）：{$yao['ci']}\n";
            }
            $interpretation .= "\n";
        } else {
            $interpretation .= "此卦无动爻，为静卦，表示事情发展平稳。\n\n";
        }
        
        $interpretation .= "五行属性：{$guaInfo['wuxing']}\n";
        $interpretation .= "所属宫位：{$guaInfo['gong']}\n";
        
        return $interpretation;
    }
    
    /**
     * AI深度分析
     */
    protected function generateAiAnalysis(string $question, array $guaInfo, array $yaoResult): ?array
    {
        try {
            $movingYaoStr = '';
            foreach ($guaInfo['moving_yao'] ?? [] as $yao) {
                $movingYaoStr .= $yao['name'] . '（' . $yao['type'] . '）：' . mb_substr($yao['ci'], 0, 50) . "\n";
            }
            
            $prompt = "占问：{$question}\n\n";
            $prompt .= "卦名：{$guaInfo['name']}\n";
            $prompt .= "卦辞：{$guaInfo['gua_ci']}\n";
            $prompt .= "动爻：\n{$movingYaoStr}\n";
            
            $systemPrompt = '你是一位精通六爻占卜的易学大师。请根据提供的卦象信息，为用户提供详细、准确、有指导意义的解读。解读应包括：1.卦象整体分析 2.针对问题的具体解答 3.行动建议 4.注意事项。语言要专业但易懂，避免过于玄奥。';
            
            $response = AiService::chat($prompt, $systemPrompt);
            
            return [
                'content' => $response['content'] ?? 'AI分析暂时不可用',
                'model' => $response['model'] ?? 'unknown',
            ];
        } catch (\Exception $e) {
            return [
                'content' => 'AI分析暂时不可用，请稍后重试',
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * 获取历史记录
     */
    public function history()
    {
        $user = $this->request->user;
        $page = (int)$this->request->get('page', 1);
        $pageSize = (int)$this->request->get('page_size', 20);
        
        $pageSize = min(max($pageSize, 1), 100);
        
        $query = LiuyaoRecord::where('user_id', $user['sub'])
            ->order('created_at', 'desc');
        
        $total = $query->count();
        $list = $query->page($page, $pageSize)->select()->toArray();
        
        // 格式化数据
        foreach ($list as &$item) {
            $item['yao_result'] = str_split($item['yao_result']);
            if ($item['yao_ci']) {
                $item['yao_ci'] = json_decode($item['yao_ci'], true);
            }
            if ($item['ai_analysis']) {
                $item['ai_analysis'] = json_decode($item['ai_analysis'], true);
            }
        }
        
        return $this->success([
            'list' => $list,
            'pagination' => [
                'page' => $page,
                'page_size' => $pageSize,
                'total' => $total,
                'total_pages' => (int)ceil($total / $pageSize),
            ],
        ]);
    }
    
    /**
     * 获取记录详情
     */
    public function detail()
    {
        $user = $this->request->user;
        $id = (int)$this->request->get('id');
        
        if (!$id) {
            return $this->error('参数错误');
        }
        
        $record = LiuyaoRecord::where('id', $id)
            ->where('user_id', $user['sub'])
            ->find();
        
        if (!$record) {
            return $this->error('记录不存在', 404);
        }
        
        $record->yao_result = str_split($record->yao_result);
        if ($record->yao_ci) {
            $record->yao_ci = json_decode($record->yao_ci, true);
        }
        if ($record->ai_analysis) {
            $record->ai_analysis = json_decode($record->ai_analysis, true);
        }
        
        return $this->success($record);
    }
    
    /**
     * 删除记录
     */
    public function delete()
    {
        $user = $this->request->user;
        $id = (int)$this->request->post('id');
        
        if (!$id) {
            return $this->error('参数错误');
        }
        
        $record = LiuyaoRecord::where('id', $id)
            ->where('user_id', $user['sub'])
            ->find();
        
        if (!$record) {
            return $this->error('记录不存在', 404);
        }
        
        $record->delete();
        
        return $this->success(['message' => '删除成功']);
    }
}
