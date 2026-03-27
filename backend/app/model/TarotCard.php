<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 塔罗牌模�?
 */
class TarotCard extends Model
{
    protected $table = 'tc_tarot_card';
    
    // 自动写入时间戳
    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = false;
    
    // 字段类型转换
    protected $type = [
        'is_major'   => 'integer',
        'is_enabled' => 'integer',
        'number'     => 'integer',
        'sort'       => 'integer',
    ];
    
    // 含义维度常量
    const TYPE_GENERAL = 'general';
    const TYPE_LOVE    = 'love';
    const TYPE_CAREER  = 'career';
    const TYPE_HEALTH  = 'health';
    const TYPE_WEALTH  = 'wealth';
    
    const MEANING_TYPES = [
        self::TYPE_GENERAL => '综合',
        self::TYPE_LOVE    => '感情',
        self::TYPE_CAREER  => '事业',
        self::TYPE_HEALTH  => '健康',
        self::TYPE_WEALTH  => '财运',
    ];

    // 花色常量
    const SUIT_WANDS     = 'wands';
    const SUIT_CUPS      = 'cups';
    const SUIT_SWORDS    = 'swords';
    const SUIT_PENTACLES = 'pentacles';

    const SUIT_LABELS = [
        self::SUIT_WANDS     => '权杖',
        self::SUIT_CUPS      => '圣杯',
        self::SUIT_SWORDS    => '宝剑',
        self::SUIT_PENTACLES => '星币',
    ];
    
    /**
     * 获取大阿尔卡那牌
     */
    public static function getMajorArcana(): array
    {
        return self::where('is_major', 1)
            ->where('is_enabled', 1)
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }
    
    /**
     * 获取所有启用的牌（用于抽牌逻辑�?
     */
    public static function getAllEnabled(): array
    {
        return self::where('is_enabled', 1)
            ->order('is_major', 'desc')
            ->order('sort', 'asc')
            ->order('id', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 获取管理端列表（支持分页和筛选）
     */
    public static function getAdminList(array $params = []): array
    {
        $query = self::order('is_major', 'desc')
            ->order('sort', 'asc')
            ->order('id', 'asc');

        if (!empty($params['keyword'])) {
            $kw = $params['keyword'];
            $query->where(function ($q) use ($kw) {
                $q->whereLike('name', "%{$kw}%")
                  ->whereOr('name_en', 'like', "%{$kw}%");
            });
        }
        if (isset($params['arcana']) && $params['arcana'] !== '') {
            $query->where('arcana', $params['arcana']);
        }
        if (isset($params['suit']) && $params['suit'] !== '') {
            $query->where('suit', $params['suit']);
        }
        if (isset($params['is_enabled']) && $params['is_enabled'] !== '') {
            $query->where('is_enabled', (int)$params['is_enabled']);
        }

        $page     = max(1, (int)($params['page'] ?? 1));
        $pageSize = min(100, max(1, (int)($params['pageSize'] ?? 20)));

        $total = $query->count();
        $list  = $query->page($page, $pageSize)->select()->toArray();

        return [
            'list'     => $list,
            'total'    => $total,
            'page'     => $page,
            'pageSize' => $pageSize,
        ];
    }
    
    /**
     * 随机抽取一张牌
     */
    public static function drawRandom(): ?self
    {
        $count = self::where('is_enabled', 1)->count();
        if ($count === 0) {
            return null;
        }
        $offset = mt_rand(0, $count - 1);
        return self::where('is_enabled', 1)
            ->limit($offset, 1)
            ->find();
    }

    /**
     * 将数据库记录转换为抽牌所需格式
     */
    public function toDrawFormat(): array
    {
        return [
            'id'               => $this->id,
            'name'             => $this->name,
            'name_en'          => $this->name_en ?? '',
            'meaning'          => $this->meaning ?? '',
            'reversed_meaning' => $this->reversed_meaning ?? '',
            'love_meaning'     => $this->love_meaning ?? '',
            'love_reversed'    => $this->love_reversed ?? '',
            'career_meaning'   => $this->career_meaning ?? '',
            'career_reversed'  => $this->career_reversed ?? '',
            'health_meaning'   => $this->health_meaning ?? '',
            'health_reversed'  => $this->health_reversed ?? '',
            'wealth_meaning'   => $this->wealth_meaning ?? '',
            'wealth_reversed'  => $this->wealth_reversed ?? '',
            'emoji'            => $this->emoji ?? '🃏',
            'color'            => $this->color ?? '#666666',
            'element'          => $this->element ?? '',
            'is_major'         => (int)$this->is_major,
            'arcana'           => $this->arcana ?? 'minor',
            'suit'             => $this->suit ?? '',
        ];
    }
}