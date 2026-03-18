<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\InviteRecord;
use think\facade\Cache;
use think\facade\Db;

/**
 * 分享控制器
 */
class Share extends BaseController
{
    protected $middleware = [\app\middleware\Auth::class];
    
    /**
     * 获取用户的分享邀请码和链接
     */
    public function getInviteInfo()
    {
        $user = $this->request->user;
        $userId = $user['sub'];
        
        // 获取或创建邀请码
        $inviteCode = InviteRecord::getOrCreateInviteCode($userId);
        
        // 获取邀请统计
        $stats = InviteRecord::getInviteStats($userId);
        
        // 获取邀请配置
        $invitePoints = config('app.invite_points', 50);
        
        // 生成邀请链接
        $baseUrl = request()->scheme() . '://' . request()->host();
        $inviteUrl = $baseUrl . '/?invite=' . $inviteCode;
        
        return $this->success([
            'invite_code' => $inviteCode,
            'invite_url' => $inviteUrl,
            'invite_count' => $stats['invite_count'],
            'total_points' => $stats['total_points'],
            'invite_points' => $invitePoints,
            'share_title' => '加入太初命理，探索命运的奥秘',
            'share_desc' => '我在使用太初命理APP，可以八字排盘、AI解盘，快来试试吧！',
            'share_image' => $baseUrl . '/static/share-logo.png',
        ]);
    }
    
    /**
     * 记录分享行为（可积分）
     */
    public function recordShare()
    {
        $user = $this->request->user;
        $userId = $user['sub'];
        $platform = $this->request->post('platform', '');
        $shareType = $this->request->post('type', 'link');
        
        // 验证平台
        $validPlatforms = ['wechat', 'qq', 'weibo', 'clipboard', 'poster'];
        if (!in_array($platform, $validPlatforms)) {
            return $this->error('无效的分享平台');
        }
        
        // 每日分享积分限制
        $today = date('Y-m-d');
        $cacheKey = "share_points:{$userId}:{$today}";
        $shareCount = Cache::get($cacheKey, 0);
        
        $maxSharePerDay = 5; // 每日最多5次分享积分
        $pointsEarned = 0;
        
        if ($shareCount < $maxSharePerDay) {
            // 每次分享获得2积分
            $pointsEarned = 2;
            
            // 更新分享计数
            Cache::set($cacheKey, $shareCount + 1, 86400);
            
            // 增加用户积分
            $userModel = \app\model\User::find($userId);
            if ($userModel) {
                $userModel->addPoints($pointsEarned);
                
                // 记录积分变动
                \app\model\PointsRecord::record(
                    $userId,
                    '每日分享奖励',
                    $pointsEarned,
                    'share',
                    0,
                    "分享到{$platform}"
                );
            }
        }
        
        // 记录分享日志
        Db::table('tc_share_log')->insert([
            'user_id' => $userId,
            'platform' => $platform,
            'type' => $shareType,
            'points_reward' => $pointsEarned,
            'ip' => $this->request->ip(),
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        
        return $this->success([
            'points_earned' => $pointsEarned,
            'share_count' => $shareCount + 1,
            'max_share' => $maxSharePerDay,
        ], '分享成功');
    }
    
    /**
     * 获取邀请排行榜
     */
    public function getLeaderboard()
    {
        $period = $this->request->get('period', 'all');
        $limit = (int) $this->request->get('limit', 20);
        
        // 限制最大返回数量
        $limit = min($limit, 100);
        
        $leaderboard = InviteRecord::getLeaderboard($limit, $period);
        
        // 获取当前用户排名
        $user = $this->request->user;
        $userRank = InviteRecord::getUserRank($user['sub'], $period);
        
        return $this->success([
            'leaderboard' => $leaderboard,
            'my_rank' => $userRank,
        ]);
    }
    
    /**
     * 获取邀请记录列表
     */
    public function getInviteList()
    {
        $user = $this->request->user;
        $userId = $user['sub'];
        $page = (int) $this->request->get('page', 1);
        $limit = (int) $this->request->get('limit', 20);
        
        $query = Db::table('tc_invite_record')
            ->where('inviter_id', $userId)
            ->where('status', 1)
            ->order('created_at', 'desc');

        
        $total = $query->count();
        $list = $query->page($page, $limit)->select()->toArray();
        
        // 获取被邀请人信息
        foreach ($list as &$item) {
            $invitedUserId = (int) ($item['invited_id'] ?? 0);
            if ($invitedUserId > 0) {
                $invitee = Db::table('tc_user')
                    ->where('id', $invitedUserId)
                    ->field('nickname, avatar, created_at')
                    ->find();
                $item['invitee_id'] = $invitedUserId;
                $item['invitee'] = $invitee ?: null;
            } else {
                $item['invitee_id'] = 0;
                $item['invitee'] = null;
            }
        }

        
        return $this->success([
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
        ]);
    }
    
    /**
     * 生成分享海报
     */
    public function generatePoster()
    {
        $user = $this->request->user;
        $userId = $user['sub'];
        
        // 获取邀请码
        $inviteCode = InviteRecord::getOrCreateInviteCode($userId);
        
        // 生成海报数据（前端根据数据生成图片）
        $baseUrl = request()->scheme() . '://' . request()->host();
        
        // 生成二维码URL（调用外部API或本地生成）
        $qrCodeUrl = $this->generateQrCodeUrl($inviteCode);
        
        return $this->success([
            'poster_data' => [
                'background' => $baseUrl . '/static/poster-bg.png',
                'qr_code' => $qrCodeUrl,
                'invite_code' => $inviteCode,
                'title' => '太初命理',
                'subtitle' => '扫码加入，开启命理之旅',
                'slogan' => '八字排盘 | AI解盘 | 每日运势',
            ],
            'share_text' => "我正在使用太初命理，八字排盘、AI解盘超准！\n邀请码：{$inviteCode}\n快来试试吧：{$baseUrl}/?invite={$inviteCode}",
        ]);
    }
    
    /**
     * 生成二维码URL
     */
    protected function generateQrCodeUrl(string $inviteCode): string
    {
        $baseUrl = request()->scheme() . '://' . request()->host();
        $inviteUrl = urlencode($baseUrl . '/?invite=' . $inviteCode);
        
        // 使用Google Charts API生成二维码（可替换为其他服务）
        return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={$inviteUrl}";
    }
}
