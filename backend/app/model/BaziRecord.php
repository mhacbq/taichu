<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

class BaziRecord extends Model
{
    protected $table = 'tc_bazi_record';
    protected $pk = 'id';
    
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = false;
    
    protected $schema = [
        'id' => 'int',
        'user_id' => 'int',
        'birth_date' => 'string',
        'gender' => 'string',
        'location' => 'string',
        'year_gan' => 'string',
        'year_zhi' => 'string',
        'month_gan' => 'string',
        'month_zhi' => 'string',
        'day_gan' => 'string',
        'day_zhi' => 'string',
        'hour_gan' => 'string',
        'hour_zhi' => 'string',
        'analysis' => 'string',
        'is_first' => 'int',
        'is_public' => 'int',          // 是否公开
        'share_code' => 'string',      // 分享码
        'view_count' => 'int',         // 查看次数
        'created_at' => 'datetime',
    ];
    
    protected $defaultValues = [
        'is_public' => 0,
        'view_count' => 0,
    ];
    
    /**
     * 获取用户的排盘历史（过滤敏感字段）
     */
    public static function getUserHistory(int $userId, int $limit = 20): array
    {
        return self::where('user_id', $userId)
            ->order('created_at', 'desc')
            ->limit($limit)
            ->field('id,birth_date,gender,location,year_gan,year_zhi,month_gan,month_zhi,day_gan,day_zhi,hour_gan,hour_zhi,analysis,is_public,share_code,view_count,created_at')
            ->select()
            ->toArray();
    }
    
    /**
     * 获取用户的排盘历史（分页，过滤敏感字段）
     */
    public static function getUserHistoryPaged(int $userId, int $page = 1, int $pageSize = 10): array
    {
        $query = self::where('user_id', $userId)
            ->order('created_at', 'desc')
            ->field('id,birth_date,gender,location,year_gan,year_zhi,month_gan,month_zhi,day_gan,day_zhi,hour_gan,hour_zhi,analysis,is_public,share_code,view_count,created_at');
        
        $total = $query->count();
        $list = $query->page($page, $pageSize)->select()->toArray();
        
        return [
            'list' => $list,
            'pagination' => [
                'page' => $page,
                'page_size' => $pageSize,
                'total' => $total,
                'total_pages' => (int)ceil($total / $pageSize),
            ],
        ];
    }
    
    /**
     * 生成分享码（带最大重试次数限制）
     */
    protected static function generateShareCode(int $maxRetries = 10): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        
        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
            
            // 检查是否重复
            if (!self::where('share_code', $code)->find()) {
                return $code;
            }
        }
        
        // 达到最大重试次数，使用UUID风格保证唯一性
        return uniqid('bazi_', true);
    }
    
    /**
     * 设置分享状态
     */
    public function setSharePublic(bool $isPublic): bool
    {
        if ($isPublic && empty($this->share_code)) {
            $this->share_code = self::generateShareCode();
        }
        $this->is_public = $isPublic ? 1 : 0;
        return $this->save();
    }
    
    /**
     * 根据分享码查找记录
     */
    public static function findByShareCode(string $shareCode): ?self
    {
        return self::where('share_code', $shareCode)
            ->where('is_public', 1)
            ->find();
    }
    
    /**
     * 获取单条记录（检查权限）
     */
    public static function getByIdWithAuth(int $id, int $userId): ?self
    {
        $record = self::find($id);
        
        if (!$record) {
            return null;
        }
        
        // 是自己的记录或是公开的记录
        if ($record->user_id === $userId || $record->is_public === 1) {
            return $record;
        }
        
        return null;
    }
    
    /**
     * 增加查看次数
     */
    public function incrementViewCount(): void
    {
        $this->view_count++;
        $this->save();
    }
    
    /**
     * 删除记录
     */
    public static function deleteById(int $id, int $userId): bool
    {
        $record = self::where('id', $id)
            ->where('user_id', $userId)
            ->find();
        
        if ($record) {
            return $record->delete();
        }
        
        return false;
    }
}
