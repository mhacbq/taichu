<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\PointsRecord;
use app\model\BaziRecord;

class Points extends BaseController
{
    protected $middleware = [\app\middleware\Auth::class];
    
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
            'tarotCount' => 0, // 可以添加塔罗记录表后统计
            'totalSpent' => PointsRecord::where('user_id', $user['sub'])
                ->where('points', '<', 0)
                ->sum('points'),
            'totalEarned' => PointsRecord::where('user_id', $user['sub'])
                ->where('points', '>', 0)
                ->sum('points'),
        ]);
    }
    
    /**
     * 获取积分历史
     */
    public function history()
    {
        $user = $this->request->user;
        $limit = $this->request->get('limit', 50);
        
        $records = PointsRecord::getUserRecords($user['sub'], (int)$limit);
        
        return $this->success($records);
    }
    
    /**
     * 消耗积分
     */
    public function consume()
    {
        $data = $this->request->post();
        $user = $this->request->user;
        
        if (empty($data['points']) || empty($data['action'])) {
            return $this->error('缺少必要参数');
        }
        
        $points = (int) $data['points'];
        $action = $data['action'];
        
        if ($points <= 0) {
            return $this->error('积分数量无效');
        }
        
        $userModel = \app\model\User::find($user['sub']);
        
        if (!$userModel) {
            return $this->error('用户不存在', 404);
        }
        
        if ($userModel->points < $points) {
            return $this->error('积分不足', 403);
        }
        
        // 扣除积分
        if ($userModel->deductPoints($points)) {
            PointsRecord::record(
                $user['sub'],
                $action,
                -$points,
                $data['type'] ?? 'consume',
                $data['related_id'] ?? 0,
                $data['remark'] ?? ''
            );
            
            return $this->success([
                'balance' => $userModel->points,
                'consumed' => $points,
            ], '积分扣除成功');
        }
        
        return $this->error('积分扣除失败');
    }
    
    /**
     * 充值积分（测试用）
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
        
        $userModel = \app\model\User::find($user['sub']);
        
        if (!$userModel) {
            return $this->error('用户不存在', 404);
        }
        
        // 增加积分
        if ($userModel->addPoints($points)) {
            PointsRecord::record(
                $user['sub'],
                $data['action'] ?? '积分充值',
                $points,
                'recharge',
                0,
                $data['remark'] ?? ''
            );
            
            return $this->success([
                'balance' => $userModel->points,
                'recharged' => $points,
            ], '充值成功');
        }
        
        return $this->error('充值失败');
    }
}
