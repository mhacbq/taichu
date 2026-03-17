<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\PointsRecord;
use app\model\BaziRecord;
use app\service\PointsService;

class Points extends BaseController
{
    protected $middleware = [\app\middleware\Auth::class];
    
    /**
     * @var PointsService
     */
    protected $pointsService;
    
    /**
     * 构造函数
     */
    public function initialize()
    {
        parent::initialize();
        $this->pointsService = new PointsService();
    }
    
    /**
     * 获取积分余额
     */
    public function balance()
    {
        $user = $this->request->user;
        $userModel = \app\model\User::find($user['sub']);
        
        if (!$userModel) {
            return $this->error('用户不存在', 404);
        }
        
        // 获取使用统计
        $baziCount = BaziRecord::where('user_id', $user['sub'])->count();
        
        return $this->success([
            'balance' => $userModel->points,
            'baziCount' => $baziCount,
            'first_bazi' => $baziCount === 0, // 首次排盘标记
            'tarotCount' => \app\model\TarotRecord::where('user_id', $user['sub'])->count(),
            'totalSpent' => abs(PointsRecord::where('user_id', $user['sub'])
                ->where('points', '<', 0)
                ->sum('points')),
            'totalEarned' => PointsRecord::where('user_id', $user['sub'])
                ->where('points', '>', 0)
                ->sum('points'),
        ]);
    }
    
    /**
     * 获取积分历史（支持分页）
     */
    public function history()
    {
        $user = $this->request->user;
        $page = (int)$this->request->get('page', 1);
        $pageSize = (int)$this->request->get('page_size', 20);
        
        // 限制最大页大小
        $pageSize = min(max($pageSize, 1), 100);
        
        $query = PointsRecord::where('user_id', $user['sub'])
            ->order('created_at', 'desc');
        
        $total = $query->count();
        $list = $query->page($page, $pageSize)->select()->toArray();
        
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
     * 消耗积分（事务完整版）
     */
    public function consume()
    {
        $data = $this->request->post();
        $user = $this->request->user;
        
        // 参数验证
        if (empty($data['points']) || empty($data['action'])) {
            return $this->error('缺少必要参数');
        }
        
        $points = (int) $data['points'];
        $action = $data['action'];
        
        if ($points <= 0) {
            return $this->error('积分数量无效');
        }
        
        if ($points > 10000) {
            return $this->error('单次扣除积分不能超过10000');
        }
        
        try {
            $result = $this->pointsService->consume(
                $user['sub'],
                $points,
                $action,
                $data['type'] ?? 'consume',
                $data['related_id'] ?? 0,
                $data['remark'] ?? ''
            );
            
            if ($result['success']) {
                return $this->success([
                    'balance' => $result['balance'],
                    'consumed' => $points,
                    'record_id' => $result['record_id'],
                ], $result['message']);
            } else {
                $code = $result['message'] === '积分不足' ? 403 : 500;
                return $this->error($result['message'], $code);
            }
        } catch (\Throwable $e) {
            return $this->respondSystemException(
                '积分扣除',
                $e,
                '积分扣除失败，请稍后重试',
                [
                    'user_id' => (int) ($user['sub'] ?? 0),
                    'points' => $points,
                    'action' => $action,
                    'type' => (string) ($data['type'] ?? 'consume'),
                    'related_id' => (int) ($data['related_id'] ?? 0),
                    'remark_length' => mb_strlen((string) ($data['remark'] ?? '')),
                ]
            );
        }

    }
    
    /**
     * 充值积分（事务完整版）
     */
    public function recharge()
    {
        $data = $this->request->post();
        $user = $this->request->user;
        
        if (empty($data['points'])) {
            return $this->error('请提供充值积分数量');
        }
        
        $points = (int) $data['points'];
        
        if ($points <= 0) {
            return $this->error('充值积分数量无效');
        }
        
        if ($points > 100000) {
            return $this->error('单次充值积分不能超过100000');
        }
        
        try {
            $result = $this->pointsService->add(
                $user['sub'],
                $points,
                $data['action'] ?? '积分充值',
                'recharge',
                0,
                $data['remark'] ?? ''
            );
            
            if ($result['success']) {
                return $this->success([
                    'balance' => $result['balance'],
                    'recharged' => $points,
                    'record_id' => $result['record_id'],
                ], $result['message']);
            } else {
                return $this->error($result['message'], 500);
            }
        } catch (\Throwable $e) {
            return $this->respondSystemException(
                '积分充值',
                $e,
                '充值失败，请稍后重试',
                [
                    'user_id' => (int) ($user['sub'] ?? 0),
                    'points' => $points,
                    'action' => (string) ($data['action'] ?? '积分充值'),
                    'remark_length' => mb_strlen((string) ($data['remark'] ?? '')),
                ]
            );
        }

    }
}
