<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\User;
use app\model\InviteRecord;
use app\service\SmsService;
use Firebase\JWT\JWT;
use think\facade\Cache;
use think\facade\Config;
use think\facade\Db;

class Auth extends BaseController
{
    /**
     * 微信登录
     */
    public function login()
    {
        $data = $this->request->post();
        
        // 验证参数
        if (empty($data['code'])) {
            return $this->error('缺少code参数');
        }
        
        // 实际项目中应该调用微信API换取openid
        // 这里模拟登录过程
        $openid = 'wx_' . md5($data['code']);
        
        // 查找或创建用户
        $user = User::findByOpenid($openid);
        $isNewUser = false;
        
        if (!$user) {
            $isNewUser = true;
            
            // 过滤昵称，防止XSS攻击
            $nickname = $data['nickname'] ?? '';
            $nickname = $this->filterXss($nickname);
            if (empty($nickname)) {
                $nickname = '微信用户' . random_int(1000, 9999);
            }
            
            // 过滤头像URL
            $avatar = $data['avatar'] ?? '';
            $avatar = $this->filterUrl($avatar);
            
            $user = User::create([
                'openid' => $openid,
                'nickname' => $nickname,
                'avatar' => $avatar,
                'gender' => in_array($data['gender'] ?? 0, [0, 1, 2]) ? $data['gender'] : 0,
            ]);
            
            // 新用户赠送积分
            $user->addPoints(100);
            \app\model\PointsRecord::record($user->id, '新用户注册奖励', 100, 'register');
            
            // 处理邀请码
            if (!empty($data['invite_code'])) {
                $this->processInviteCode($user->id, $data['invite_code']);
            }
        }
        
        // 更新登录时间
        $user->last_login_at = date('Y-m-d H:i:s');
        $user->save();
        
        return $this->generateTokenResponse($user, $isNewUser);
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
            $isNewUser = true;
            $user = User::create([
                'phone' => $phone,
                'nickname' => '用户' . substr($phone, -4),
                'avatar' => '',
                'gender' => 0,
            ]);
            
            // 新用户赠送积分
            $user->addPoints(100);
            \app\model\PointsRecord::record($user->id, '新用户注册奖励', 100, 'register');
            
            // 处理邀请码
            if (!empty($data['invite_code'])) {
                $this->processInviteCode($user->id, $data['invite_code']);
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
        if (strlen($password) < 6) {
            return $this->error('密码长度不能少于6位');
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
            $user->addPoints(100);
            \app\model\PointsRecord::record($user->id, '新用户注册奖励', 100, 'register');
            
            // 处理邀请码
            if (!empty($data['invite_code'])) {
                $this->processInviteCode($user->id, $data['invite_code']);
            }
            
            Db::commit();
            
            return $this->generateTokenResponse($user, true, '注册成功');
        } catch (\Exception $e) {
            Db::rollback();
            return $this->error('注册失败：' . $e->getMessage());
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
        return substr($text, 0, 50);
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
                \app\model\PointsRecord::record(
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
                \app\model\PointsRecord::record(
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
     * 获取用户信息
     */
    public function userinfo()
    {
        $user = $this->request->user;
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
        $user = $this->request->user;
        $data = $this->request->post();
        
        $userInfo = User::find($user['sub']);
        
        if (!$userInfo) {
            return $this->error('用户不存在', 404);
        }
        
        $allowFields = ['nickname', 'avatar', 'gender', 'phone'];
        $updateData = [];
        
        foreach ($allowFields as $field) {
            if (isset($data[$field])) {
                $value = $data[$field];
                
                // 对昵称进行XSS过滤
                if ($field === 'nickname') {
                    $value = $this->filterXss($value);
                }
                
                // 对头像URL进行过滤
                if ($field === 'avatar') {
                    $value = $this->filterUrl($value);
                }
                
                // 验证性别范围
                if ($field === 'gender') {
                    $value = in_array($value, [0, 1, 2]) ? $value : 0;
                }
                
                $updateData[$field] = $value;
            }
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
        $user = $this->request->user;
        $page = (int)$this->request->get('page', 1);
        $limit = (int)$this->request->get('limit', 10);
        
        $offset = ($page - 1) * $limit;
        
        // 获取邀请记录
        $invites = InviteRecord::where('inviter_id', $user['sub'])
            ->where('status', 1)
            ->order('created_at', 'desc')
            ->limit($offset, $limit)
            ->select();
        
        // 获取被邀请人信息
        $inviteeIds = array_column($invites->toArray(), 'invitee_id');
        $inviteeInfos = User::whereIn('id', $inviteeIds)
            ->column('nickname,avatar,created_at', 'id');
        
        $result = [];
        foreach ($invites as $invite) {
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
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'total_pages' => ceil($total / $limit),
        ]);
    }
}
