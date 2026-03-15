<?php
declare(strict_types=1);

namespace app\model;

use think\Model;
use think\facade\Db;

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
     * 根据手机号查找用户
     */
    public static function findByPhone(string $phone): ?self
    {
        return self::where('phone', $phone)->where('status', 1)->find();
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
     * 扣除积分（使用乐观锁防止并发问题）
     */
    public function deductPoints(int $points): bool
    {
        if ($points <= 0) {
            return false;
        }
        
        // 使用数据库原生操作确保原子性
        $result = Db::name('tc_user')
            ->where('id', $this->id)
            ->where('points', '>=', $points)
            ->dec('points', $points)
            ->update();
        
        // 如果更新失败（影响行数为0），说明积分不足或并发冲突
        if ($result === 0) {
            return false;
        }
        
        // 刷新模型中的积分数据
        $this->refresh();
        
        return true;
    }
    
    /**
     * 增加积分（使用原子操作）
     */
    public function addPointsAtomic(int $points): bool
    {
        if ($points <= 0) {
            return false;
        }
        
        $result = Db::name('tc_user')
            ->where('id', $this->id)
            ->inc('points', $points)
            ->update();
        
        if ($result === 0) {
            return false;
        }
        
        $this->refresh();
        return true;
    }
}
