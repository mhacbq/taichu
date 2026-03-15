<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 塔罗占卜记录模型
 */
class TarotRecord extends Model
{
    protected $name = 'tc_tarot_record';
    
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = false;
    
    // 牌阵类型
    const SPREAD_SINGLE = 'single';       // 单牌
    const SPREAD_THREE = 'three';         // 三张牌
    const SPREAD_CELTIC = 'celtic';       // 凯尔特十字
    const SPREAD_RELATIONSHIP = 'relationship'; // 关系牌阵
    
    protected $schema = [
        'id' => 'int',
        'user_id' => 'int',
        'spread_type' => 'string',          // 牌阵类型
        'question' => 'string',             // 问题
        'cards' => 'json',                  // 抽到的牌（JSON数组）
        'interpretation' => 'text',         // 解读内容
        'ai_analysis' => 'text',            // AI深度分析
        'is_public' => 'int',               // 是否公开（1=公开，0=私密）
        'share_code' => 'string',           // 分享码
        'view_count' => 'int',              // 查看次数
        'client_ip' => 'string',
        'created_at' => 'datetime',
    ];
    
    protected $json = ['cards'];
    
    protected $defaultValues = [
        'is_public' => 0,
        'view_count' => 0,
    ];
    
    /**
     * 获取用户的塔罗历史（分页）
     */
    public static function getUserHistory(int $userId, int $page = 1, int $pageSize = 10): array
    {
        $query = self::where('user_id', $userId)
            ->order('created_at', 'desc');
        
        $total = $query->count();
        $list = $query->page($page, $pageSize)
            ->field('id, spread_type, question, cards, interpretation, is_public, share_code, view_count, created_at')
            ->select()
            ->toArray();
        
        // 处理cards字段，简化返回
        foreach ($list as &$item) {
            if (is_string($item['cards'])) {
                $item['cards'] = json_decode($item['cards'], true);
            }
            // 限制解读内容长度
            if (!empty($item['interpretation']) && mb_strlen($item['interpretation']) > 100) {
                $item['interpretation'] = mb_substr($item['interpretation'], 0, 100) . '...';
            }
        }
        
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
     * 创建塔罗记录
     */
    public static function createRecord(
        int $userId,
        string $spreadType,
        string $question,
        array $cards,
        string $interpretation = '',
        string $aiAnalysis = '',
        string $clientIp = ''
    ): ?self {
        $record = new self();
        $record->user_id = $userId;
        $record->spread_type = $spreadType;
        $record->question = $question;
        $record->cards = $cards;
        $record->interpretation = $interpretation;
        $record->ai_analysis = $aiAnalysis;
        $record->client_ip = $clientIp;
        $record->share_code = self::generateShareCode();
        
        return $record->save() ? $record : null;
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
        return uniqid('tarot_', true);
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
     * 设置公开状态
     */
    public function setPublic(bool $isPublic): bool
    {
        $this->is_public = $isPublic ? 1 : 0;
        return $this->save();
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
     * 获取牌阵名称
     */
    public function getSpreadName(): string
    {
        $names = [
            self::SPREAD_SINGLE => '单牌占卜',
            self::SPREAD_THREE => '三张牌阵',
            self::SPREAD_CELTIC => '凯尔特十字',
            self::SPREAD_RELATIONSHIP => '关系牌阵',
        ];
        
        return $names[$this->spread_type] ?? '未知牌阵';
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
    
    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
