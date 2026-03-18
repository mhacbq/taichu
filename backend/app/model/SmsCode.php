<?php
declare(strict_types=1);

namespace app\model;

use app\service\SchemaInspector;
use think\Model;


/**
 * 短信验证码模型
 */
class SmsCode extends Model
{
    protected static ?string $resolvedTable = null;

    // 使用 tc_ 前缀，与数据库表名统一
    protected $name = 'tc_sms_code';
    
    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = false;


    public function __construct(array|object $data = [])
    {
        parent::__construct($data);
        $this->table = self::resolveTableName();
    }

    
    // 验证码类型常量
    const TYPE_REGISTER = 'register';  // 注册
    const TYPE_LOGIN = 'login';        // 登录
    const TYPE_RESET = 'reset';        // 重置密码
    
    protected $schema = [
        'id' => 'int',
        'phone' => 'string',
        'code' => 'string',
        'type' => 'string',
        'expire_time' => 'datetime',
        'is_used' => 'boolean',
        'ip' => 'string',
        'created_at' => 'datetime',
    ];
    
    /**
     * 生成验证码
     */
    public static function generateCode(): string
    {
        return (string) mt_rand(100000, 999999);
    }
    
    /**
     * 创建验证码
     */
    public static function createCode(string $phone, string $type, string $ip = '', int $expireMinutes = 5): string
    {
        // 将同类型的旧验证码标记为已使用
        self::where('phone', $phone)
            ->where('type', $type)
            ->where('is_used', 0)
            ->update(['is_used' => 1]);
        
        $code = self::generateCode();

        $smsCode = new self();
        $smsCode->save([
            'phone' => $phone,
            'code' => $code,
            'type' => $type,
            'expire_time' => date('Y-m-d H:i:s', strtotime("+{$expireMinutes} minutes")),
            'is_used' => 0,
            'ip' => $ip,
        ]);

        return $code;

    }
    
    /**
     * 验证验证码
     */
    public static function verifyCode(string $phone, string $code, string $type): bool
    {
        $record = self::where('phone', $phone)
            ->where('code', $code)
            ->where('type', $type)
            ->where('is_used', 0)
            ->where('expire_time', '>', date('Y-m-d H:i:s'))
            ->order('id', 'desc')
            ->find();
        
        if (!$record) {
            return false;
        }
        
        // 标记为已使用
        $record->is_used = 1;
        $record->save();
        
        return true;
    }
    
    /**
     * 获取今日发送次数
     */
    public static function getTodaySendCount(string $phone, string $ip = ''): int
    {
        $query = self::where('created_at', '>=', date('Y-m-d 00:00:00'));
        
        if ($phone) {
            $query->where('phone', $phone);
        } elseif ($ip) {
            $query->where('ip', $ip);
        }
        
        return $query->count();
    }
    
    /**
     * 获取最近一次发送时间
     */
    public static function getLastSendTime(string $phone, string $type): ?string
    {
        $record = self::where('phone', $phone)
            ->where('type', $type)
            ->order('id', 'desc')
            ->find();
        
        return $record ? $record->created_at : null;
    }
    
    /**
     * 清理过期验证码
     */
    public static function clearExpired(): int
    {
        return self::where('expire_time', '<', date('Y-m-d H:i:s'))
            ->where('is_used', 0)
            ->update(['is_used' => 1]);
    }

    /**
     * 物理删除历史过期验证码，降低表膨胀风险
     */
    public static function purgeExpired(int $retentionDays = 7): int
    {
        $retentionDays = max(0, $retentionDays);
        $deadline = date('Y-m-d H:i:s', strtotime(sprintf('-%d days', $retentionDays)));

        return self::where('expire_time', '<', $deadline)->delete();
    }

    protected static function resolveTableName(): string
    {
        if (self::$resolvedTable !== null) {
            return self::$resolvedTable;
        }

        foreach (['tc_sms_code', 'sms_codes'] as $table) {
            if (SchemaInspector::tableExists($table)) {
                self::$resolvedTable = $table;
                return $table;
            }
        }

        self::$resolvedTable = 'tc_sms_code';
        return self::$resolvedTable;
    }
}

