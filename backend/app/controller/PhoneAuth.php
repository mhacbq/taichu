<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\User;
use app\model\InviteRecord;
use app\service\SmsService;
use Firebase\JWT\JWT;
use think\facade\Config;
use think\facade\Db;

/**
 * 手机号认证控制器
 */
class PhoneAuth extends BaseController
{
    /**
     * 手机号注册
     */
    public function register()
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
        
        // 验证手机号格式
        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
            return $this->error('手机号格式不正确');
        }
        
        // 验证短信验证码
        if (!SmsService::verifyCode($phone, $data['code'], 'register')) {
            return $this->error('验证码错误或已过期');
        }
        
        // 检查手机号是否已注册
        $existingUser = User::where('phone', $phone)->find();
        if ($existingUser) {
            return $this->error('该手机号已注册');
        }
        
        Db::startTrans();
        try {
            // 创建用户
            $user = User::create([
                'phone' => $phone,
                'nickname' => $data['nickname'] ?? '用户' . substr($phone, -4),
                'avatar' => $data['avatar'] ?? '',
                'gender' => $data['gender'] ?? 0,
                'last_login_at' => date('Y-m-d H:i:s'),
            ]);
            
            // 新用户赠送积分
            $user->addPoints(100);
            \app\model\PointsRecord::record($user->id, '新用户注册奖励', 100, 'register');
            
            // 处理邀请码
            if (!empty($data['invite_code'])) {
                $this->processInviteCode($user->id, $data['invite_code']);
            }
            
            Db::commit();
            
            // 生成JWT Token
            $token = $this->generateToken($user);
            
            return $this->success([
                'token' => $token,
                'expire' => Config::get('jwt.ttl'),
                'is_new_user' => true,
                'user' => [
                    'id' => $user->id,
                    'nickname' => $user->nickname,
                    'avatar' => $user->avatar,
                    'points' => $user->points,
                ],
            ], '注册成功');
            
        } catch (\Exception $e) {
            Db::rollback();
            trace('手机号注册失败: ' . $e->getMessage(), 'error');
            return $this->error('注册失败，请稍后重试');
        }
    }
    
    /**
     * 手机号登录
     */
    public function login()
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
        
        // 验证手机号格式
        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
            return $this->error('手机号格式不正确');
        }
        
        // 验证短信验证码
        if (!SmsService::verifyCode($phone, $data['code'], 'login')) {
            return $this->error('验证码错误或已过期');
        }
        
        // 查找用户
        $user = User::where('phone', $phone)->find();
        
        if (!$user) {
            return $this->error('该手机号未注册');
        }
        
        // 更新登录时间
        $user->last_login_at = date('Y-m-d H:i:s');
        $user->save();
        
        // 生成JWT Token
        $token = $this->generateToken($user);
        
        return $this->success([
            'token' => $token,
            'expire' => Config::get('jwt.ttl'),
            'is_new_user' => false,
            'user' => [
                'id' => $user->id,
                'nickname' => $user->nickname,
                'avatar' => $user->avatar,
                'points' => $user->points,
            ],
        ], '登录成功');
    }
    
    /**
     * 快捷登录（未注册自动注册）
     */
    public function quickLogin()
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
        
        // 验证手机号格式
        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
            return $this->error('手机号格式不正确');
        }
        
        // 验证短信验证码
        if (!SmsService::verifyCode($phone, $data['code'], 'login')) {
            return $this->error('验证码错误或已过期');
        }
        
        // 查找用户
        $user = User::where('phone', $phone)->find();
        $isNewUser = false;
        
        if (!$user) {
            // 自动注册
            Db::startTrans();
            try {
                $user = User::create([
                    'openid' => null,
                    'unionid' => null,
                    'phone' => $phone,
                    'nickname' => $data['nickname'] ?? '用户' . substr($phone, -4),
                    'avatar' => $data['avatar'] ?? '',
                    'gender' => $data['gender'] ?? 0,
                    'last_login_at' => date('Y-m-d H:i:s'),
                ]);

                
                // 新用户赠送积分
                $user->addPoints(100);
                \app\model\PointsRecord::record($user->id, '新用户注册奖励', 100, 'register');
                
                // 处理邀请码
                if (!empty($data['invite_code'])) {
                    $this->processInviteCode($user->id, $data['invite_code']);
                }
                
                Db::commit();
                $isNewUser = true;
                
            } catch (\Exception $e) {
                Db::rollback();
                trace('快捷登录注册失败: ' . $e->getMessage(), 'error');
                return $this->error('登录失败，请稍后重试');
            }
        } else {
            // 更新登录时间
            $user->last_login_at = date('Y-m-d H:i:s');
            $user->save();
        }
        
        // 生成JWT Token
        $token = $this->generateToken($user);
        
        return $this->success([
            'token' => $token,
            'expire' => Config::get('jwt.ttl'),
            'is_new_user' => $isNewUser,
            'user' => [
                'id' => $user->id,
                'nickname' => $user->nickname,
                'avatar' => $user->avatar,
                'points' => $user->points,
            ],
        ], $isNewUser ? '注册成功' : '登录成功');
    }
    
    /**
     * 重置密码
     */
    public function resetPassword()
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
            return $this->error('请输入新密码');
        }
        
        $phone = $data['phone'];
        
        // 验证手机号格式
        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
            return $this->error('手机号格式不正确');
        }
        
        // 验证短信验证码
        if (!SmsService::verifyCode($phone, $data['code'], 'reset')) {
            return $this->error('验证码错误或已过期');
        }
        
        // 查找用户
        $user = User::where('phone', $phone)->find();
        
        if (!$user) {
            return $this->error('该手机号未注册');
        }
        
        // 更新密码
        $user->password = password_hash($data['password'], PASSWORD_DEFAULT);
        $user->save();
        
        return $this->success(null, '密码重置成功');
    }
    
    /**
     * 处理邀请码
     */
    protected function processInviteCode(int $newUserId, string $inviteCode)
    {
        $inviterId = InviteRecord::getInviterByCode($inviteCode);
        
        if (!$inviterId || $inviterId === $newUserId) {
            return;
        }
        
        // 检查是否已经被邀请过
        $exists = InviteRecord::where('invited_id', $newUserId)->find();
        if ($exists) {
            return;
        }
        
        $rewardPoints = 20;
        
        Db::startTrans();
        try {
            // 记录邀请关系
            InviteRecord::create([
                'inviter_id' => $inviterId,
                'invited_id' => $newUserId,
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
     * 生成JWT Token
     */
    protected function generateToken(User $user): string
    {
        $secret = Config::get('jwt.secret');
        $ttl = Config::get('jwt.ttl');
        
        $payload = [
            'iss' => Config::get('jwt.issuer'),
            'aud' => Config::get('jwt.audience'),
            'iat' => time(),
            'exp' => time() + $ttl,
            'sub' => $user->id,
            'phone' => $user->phone,
        ];
        
        return JWT::encode($payload, $secret, 'HS256');
    }
}
