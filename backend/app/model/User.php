<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

class User extends Model
{
    protected $table = 'tc_user';
    protected $pk = 'id';
    
    // 自动时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    
    // 字段类型
    protected $schema = [
        'id' => 'int',
        'openid' => 'string',
        'unionid' => 'string',
        'nickname' => 'string',
        'avatar' => 'string',
        'gender' => 'int',
        'phone' => 'string',
        'points' => 'int',
        'status' => 'int',
        'last_login_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    
    // 隐藏字段
    protected $hidden = ['openid', 'unionid'];
    
    // 默认值
    protected $defaultValues = [
        'points' => 0,
        'status' => 1,
    ];
    
    /**
     * 根据OpenID查找用户
     */
    public static function findByOpenid(string $openid): ?self
    {
        return self::where('openid', $openid)->where('status', 1)->find();
    }
    
    /**
     * 增加积分
     */
    public function addPoints(int $points): bool
    {
        $this->points += $points;
        return $this->save();
    }
    
    /**
     * 扣除积分
     */
    public function deductPoints(int $points): bool
    {
        if ($this->points < $points) {
            return false;
        }
        $this->points -= $points;
        return $this->save();
    }
}
