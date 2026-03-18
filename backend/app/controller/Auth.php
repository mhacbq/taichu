<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\User;
use app\model\InviteRecord;
use app\model\PointsRecord;
use app\service\ConfigService;
use app\service\SmsService;

use Firebase\JWT\JWT;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Db;
use think\facade\Log;

class Auth extends BaseController
{
    protected $middleware = [
        \app\middleware\Auth::class => ['only' => ['userinfo', 'updateProfile', 'myInvites']],
    ];

    // 常量定义
    protected const MIN_PASSWORD_LENGTH = 6;
    protected const MAX_NICKNAME_LENGTH = 50;
    protected const REGISTER_POINTS = 100;
    
    /**
     * 兼容旧版登录接口
     */
    public function login()
    {
        return $this->phoneLogin();
    }

    /**
     * 手机号登录/注册
     */
    public function phoneLogin()
    {
        $data = $this->request->post();
        
        // 验证参数
        if (empty($data['phone'])) {
            return $this->error('请输入手机号');
        }
        if (empty($data['code'])) {
            return $this->error('请输入验证码');
        }
        
        $phone = $data['phone'];
        $code = $data['code'];
        
        // 验证手机号格式
        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
            return $this->error('手机号格式不正确');
        }
        
        // 验证短信验证码
        $verifyResult = SmsService::verifyCode($phone, $code, 'login');
        if (!$verifyResult) {
            return $this->error('验证码错误或已过期');
        }
        
        // 查找或创建用户
        $user = User::findByPhone($phone);
        $isNewUser = false;
        
        if (!$user) {
            if (!$this->isRegisterEnabled()) {
                return $this->error('当前暂未开放新用户注册', 403);
            }

            $isNewUser = true;
            
            // 使用事务包裹新用户创建相关操作
            Db::startTrans();

            try {
                $user = User::create([
                    'phone' => $phone,
                    'nickname' => '用户' . substr($phone, -4),
                    'avatar' => '',
                    'gender' => 0,
                ]);
                
                // 新用户赠送积分
                $registerPoints = $this->getRegisterRewardPoints();
                $user->addPoints($registerPoints);
                PointsRecord::record($user->id, '新用户注册奖励', $registerPoints, 'register');

                
                // 处理邀请码
                if (!empty($data['invite_code'])) {
                    $this->processInviteCode($user->id, $data['invite_code']);
                }
                
                Db::commit();
            } catch (\Exception $e) {
                Db::rollback();
                // 记录错误日志，但返回通用错误消息，避免泄露敏感信息
                Log::error('用户创建失败：' . $e->getMessage());
                return $this->error('用户创建失败，请稍后重试');
            }
        }
        
        // 更新登录时间
        $user->last_login_at = date('Y-m-d H:i:s');
        $user->save();
        
        return $this->generateTokenResponse($user, $isNewUser);
    }
    
    /**
     * 手机号注册
     */
    public function phoneRegister()
    {
        $data = $this->request->post();

        if (!$this->isRegisterEnabled()) {
            return $this->error('当前暂未开放新用户注册', 403);
        }
        
        // 验证参数
        if (empty($data['phone'])) {

            return $this->error('请输入手机号');
        }
        if (empty($data['code'])) {
            return $this->error('请输入验证码');
        }
        if (empty($data['password'])) {
            return $this->error('请设置密码');
        }
        
        $phone = $data['phone'];
        $code = $data['code'];
        $password = $data['password'];
        
        // 验证手机号格式
        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
            return $this->error('手机号格式不正确');
        }
        
        // 验证密码强度
        if (strlen($password) < self::MIN_PASSWORD_LENGTH) {
            return $this->error('密码长度不能少于' . self::MIN_PASSWORD_LENGTH . '位');
        }
        
        // 检查手机号是否已注册
        $existingUser = User::findByPhone($phone);
        if ($existingUser) {
            return $this->error('该手机号已注册，请直接登录');
        }
        
        // 验证短信验证码
        $verifyResult = SmsService::verifyCode($phone, $code, 'register');
        if (!$verifyResult) {
            return $this->error('验证码错误或已过期');
        }
        
        Db::startTrans();
        try {
            // 过滤昵称，防止XSS攻击
            $nickname = $data['nickname'] ?? '';
            $nickname = $this->filterXss($nickname);
            if (empty($nickname)) {
                $nickname = '用户' . substr($phone, -4);
            }
            
            // 过滤头像URL
            $avatar = $data['avatar'] ?? '';
            $avatar = $this->filterUrl($avatar);
            
            // 创建用户
            $user = User::create([
                'phone' => $phone,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'nickname' => $nickname,
                'avatar' => $avatar,
                'gender' => in_array($data['gender'] ?? 0, [0, 1, 2]) ? $data['gender'] : 0,
            ]);
            
            // 新用户赠送积分
            $registerPoints = $this->getRegisterRewardPoints();
            $user->addPoints($registerPoints);
            PointsRecord::record($user->id, '新用户注册奖励', $registerPoints, 'register');

            
            // 处理邀请码
            if (!empty($data['invite_code'])) {
                $this->processInviteCode($user->id, $data['invite_code']);
            }
            
            Db::commit();
            
            return $this->generateTokenResponse($user, true, '注册成功');
        } catch (\Exception $e) {
            Db::rollback();
            // 记录错误日志，但返回通用错误消息，避免泄露敏感信息
            Log::error('注册失败：' . $e->getMessage());
            return $this->error('注册失败，请稍后重试');
        }
    }
    
    /**
     * 过滤XSS内容
     */
    protected function filterXss(string $text): string
    {
        // 移除HTML标签
        $text = strip_tags($text);
        // 转义特殊字符
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        // 限制长度
        return substr($text, 0, self::MAX_NICKNAME_LENGTH);
    }
    
    /**
     * 过滤URL
     */
    protected function filterUrl(string $url): string
    {
        if (empty($url)) {
            return '';
        }
        
        // 只允许http和https协议
        $parsed = parse_url($url);
        if (!isset($parsed['scheme']) || !in_array($parsed['scheme'], ['http', 'https'])) {
            return '';
        }
        
        return $url;
    }

    /**
     * 读取后台可配置的注册赠送积分
     */
    protected function getRegisterRewardPoints(): int
    {
        $configuredPoints = ConfigService::get('register_points', self::REGISTER_POINTS);
        return max(0, (int) $configuredPoints);
    }

    protected function isRegisterEnabled(): bool
    {
        return (bool) ConfigService::isFeatureEnabled('register');
    }
    
    /**
     * 生成Token响应
     */


    protected function generateTokenResponse(User $user, bool $isNewUser = false, string $message = '登录成功')
    {
        $secret = Config::get('jwt.secret');
        $ttl = Config::get('jwt.ttl');
        
        $payload = [
            'iss' => Config::get('jwt.issuer'),
            'aud' => Config::get('jwt.audience'),
            'iat' => time(),
            'exp' => time() + $ttl,
            'sub' => $user->id,
        ];
        
        $token = JWT::encode($payload, $secret, 'HS256');
        
        return $this->success([
            'token' => $token,
            'expire' => $ttl,
            'is_new_user' => $isNewUser,
            'user' => [
                'id' => $user->id,
                'nickname' => $user->nickname,
                'avatar' => $user->avatar,
                'phone' => $user->phone,
                'points' => $user->points,
            ],
        ], $message);
    }
    
    /**
     * 处理邀请码
     */
    protected function processInviteCode(int $newUserId, string $inviteCode)
    {
        // 邀请码尝试次数限制 - 防止暴力枚举
        $ip = $this->request->ip();
        $attemptKey = 'invite_code_attempt:' . md5($ip);
        $attempts = Cache::get($attemptKey, 0);
        
        if ($attempts >= 10) {
            // 超过限制，阻止操作
            trace("邀请码尝试次数过多: IP={$ip}, UserId={$newUserId}", 'warning');
            return;
        }
        
        // 更新尝试次数
        Cache::set($attemptKey, $attempts + 1, 3600);
        
        $inviterId = InviteRecord::getInviterByCode($inviteCode);
        
        if (!$inviterId || $inviterId === $newUserId) {
            return;
        }
        
        // 验证成功，清除尝试记录
        Cache::delete($attemptKey);
        
        // 检查是否已经被邀请过
        $exists = InviteRecord::where('invitee_id', $newUserId)->find();
        if ($exists) {
            return;
        }
        
        $rewardPoints = 20;
        
        Db::startTrans();
        try {
            // 记录邀请关系
            InviteRecord::create([
                'inviter_id' => $inviterId,
                'invitee_id' => $newUserId,
                'invite_code' => $inviteCode,
                'points_reward' => $rewardPoints,
            ]);
            
            // 给邀请人增加积分
            $inviter = User::find($inviterId);
            if ($inviter) {
                $inviter->addPoints($rewardPoints);
                PointsRecord::record(
                    $inviterId, 
                    '邀请好友奖励', 
                    $rewardPoints, 
                    'invite',
                    $newUserId,
                    '邀请用户ID: ' . $newUserId
                );
            }
            
            // 给被邀请人也增加积分
            $invitee = User::find($newUserId);
            if ($invitee) {
                $invitee->addPoints($rewardPoints);
                PointsRecord::record(
                    $newUserId, 
                    '被邀请奖励', 
                    $rewardPoints, 
                    'invited',
                    $inviterId,
                    '填写邀请码奖励'
                );
            }
            
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
        }
    }

    /**
     * 获取当前登录用户载荷
     */
    protected function getAuthenticatedUserPayload(): ?array
    {
        $user = $this->request->user ?? null;

        if (!is_array($user) || empty($user['sub']) || !is_numeric($user['sub'])) {
            return null;
        }

        $user['sub'] = (int) $user['sub'];

        return $user;
    }
    
    /**
     * 获取用户信息
     */
    public function userinfo()
    {
        $user = $this->getAuthenticatedUserPayload();
        if (!$user) {
            return $this->error('请先登录', 401);
        }

        $userInfo = User::find($user['sub']);
        
        if (!$userInfo) {
            return $this->error('用户不存在', 404);
        }
        
        // 获取邀请统计
        $inviteStats = InviteRecord::getInviteStats($user['sub']);
        
        return $this->success([
            'id' => $userInfo->id,
            'nickname' => $userInfo->nickname,
            'avatar' => $userInfo->avatar,
            'gender' => $userInfo->gender,
            'phone' => $userInfo->phone,
            'points' => $userInfo->points,
            'last_login_at' => $userInfo->last_login_at,
            'invite_code' => InviteRecord::getOrCreateInviteCode($userInfo->id),
            'invite_count' => $inviteStats['invite_count'],
            'invite_points' => $inviteStats['total_points'],
        ]);
    }
    
    /**
     * 更新用户信息
     */
    public function updateProfile()
    {
        $user = $this->getAuthenticatedUserPayload();
        if (!$user) {
            return $this->error('请先登录', 401);
        }

        $data = $this->request->post();
        $userInfo = User::find($user['sub']);
        
        if (!$userInfo) {
            return $this->error('用户不存在', 404);
        }
        
        $allowFields = ['nickname', 'avatar', 'gender', 'phone'];
        $updateData = [];
        
        foreach ($allowFields as $field) {
            if (!isset($data[$field])) {
                continue;
            }

            $value = $data[$field];

            if ($field === 'nickname') {
                $value = $this->filterXss(trim((string) $value));
                if ($value === '') {
                    return $this->error('昵称不能为空');
                }
            }

            if ($field === 'avatar') {
                $value = $this->filterUrl((string) $value);
            }

            if ($field === 'gender') {
                $value = in_array((int) $value, [0, 1, 2], true) ? (int) $value : 0;
            }

            if ($field === 'phone') {
                $value = trim((string) $value);
                if (!preg_match('/^1[3-9]\d{9}$/', $value)) {
                    return $this->error('手机号格式不正确');
                }
            }
                
            $updateData[$field] = $value;
        }
        
        if (empty($updateData)) {
            return $this->error('没有要更新的数据');
        }
        
        $userInfo->save($updateData);
        
        return $this->success(null, '更新成功');
    }
    
    /**
     * 获取邀请排行榜
     */
    public function inviteLeaderboard()
    {
        $period = $this->request->get('period', 'all'); // all, month, week
        $limit = (int)$this->request->get('limit', 20);
        
        // 限制最大数量
        $limit = min($limit, 50);
        
        // 验证周期参数
        if (!in_array($period, ['all', 'month', 'week'])) {
            $period = 'all';
        }
        
        // 获取排行榜
        $leaderboard = InviteRecord::getLeaderboard($limit, $period);
        
        // 获取当前用户的排名
        $user = $this->request->user;
        $userRank = null;
        if ($user) {
            $userRank = InviteRecord::getUserRank($user['sub'], $period);
        }
        
        return $this->success([
            'period' => $period,
            'leaderboard' => $leaderboard,
            'user_rank' => $userRank,
            'total_count' => count($leaderboard),
        ]);
    }
    
    /**
     * 获取我的邀请记录
     */
    public function myInvites()
    {
        $user = $this->getAuthenticatedUserPayload();
        if (!$user) {
            return $this->error('请先登录', 401);
        }

        $pagination = $this->normalizePagination(
            $this->request->get('page', 1),
            $this->request->get('limit', 10),
            10,
            50
        );
        $page = $pagination['page'];
        $limit = $pagination['pageSize'];
        
        // 获取邀请记录
        $invites = InviteRecord::where('inviter_id', $user['sub'])
            ->where('status', 1)
            ->order('created_at', 'desc')
            ->page($page, $limit)
            ->select();

        $inviteList = $invites->toArray();
        $inviteeIds = array_values(array_unique(array_filter(array_column($inviteList, 'invitee_id'))));
        $inviteeInfos = [];

        if (!empty($inviteeIds)) {
            $inviteeInfos = User::whereIn('id', $inviteeIds)
                ->column('nickname,avatar,created_at', 'id');
        }
        
        $result = [];
        foreach ($inviteList as $invite) {
            $inviteeInfo = $inviteeInfos[$invite['invitee_id']] ?? [];
            $result[] = [
                'invitee_id' => $invite['invitee_id'],
                'nickname' => $inviteeInfo['nickname'] ?? '神秘用户',
                'avatar' => $inviteeInfo['avatar'] ?? '',
                'points_reward' => $invite['points_reward'],
                'created_at' => $invite['created_at'],
            ];
        }
        
        // 获取总数
        $total = InviteRecord::where('inviter_id', $user['sub'])
            ->where('status', 1)
            ->count();
        
        return $this->success([
            'list' => $result,
            'total' => (int) $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => $limit > 0 ? (int) ceil($total / $limit) : 0,
        ]);
    }
}
