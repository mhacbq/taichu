<?php
declare(strict_types=1);

namespace app\controller;

use app\BaseController;
use app\model\User;
use Firebase\JWT\JWT;
use think\facade\Config;

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
        
        if (!$user) {
            $user = User::create([
                'openid' => $openid,
                'nickname' => $data['nickname'] ?? '微信用户' . mt_rand(1000, 9999),
                'avatar' => $data['avatar'] ?? '',
                'gender' => $data['gender'] ?? 0,
            ]);
            
            // 新用户赠送积分
            $user->addPoints(100);
            \app\model\PointsRecord::record($user->id, '新用户注册奖励', 100, 'register');
        }
        
        // 更新登录时间
        $user->last_login_at = date('Y-m-d H:i:s');
        $user->save();
        
        // 生成JWT Token
        $secret = Config::get('jwt.secret');
        $ttl = Config::get('jwt.ttl');
        
        $payload = [
            'iss' => Config::get('jwt.issuer'),
            'aud' => Config::get('jwt.audience'),
            'iat' => time(),
            'exp' => time() + $ttl,
            'sub' => $user->id,
            'openid' => $openid,
        ];
        
        $token = JWT::encode($payload, $secret, 'HS256');
        
        return $this->success([
            'token' => $token,
            'expire' => $ttl,
            'user' => [
                'id' => $user->id,
                'nickname' => $user->nickname,
                'avatar' => $user->avatar,
                'points' => $user->points,
            ],
        ], '登录成功');
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
        
        return $this->success([
            'id' => $userInfo->id,
            'nickname' => $userInfo->nickname,
            'avatar' => $userInfo->avatar,
            'gender' => $userInfo->gender,
            'phone' => $userInfo->phone,
            'points' => $userInfo->points,
            'last_login_at' => $userInfo->last_login_at,
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
                $updateData[$field] = $data[$field];
            }
        }
        
        if (empty($updateData)) {
            return $this->error('没有要更新的数据');
        }
        
        $userInfo->save($updateData);
        
        return $this->success(null, '更新成功');
    }
}
