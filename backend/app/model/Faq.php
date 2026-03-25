<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * FAQ常见问题模型
 */
class Faq extends Model
{
    protected $table = 'tc_faq';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [
        'is_enabled' => 'integer',
        'sort_order' => 'integer',
        'view_count' => 'integer',
    ];

    /**
     * 获取后台管理列表（分页+筛选）
     */
    public static function getAdminList(array $params = []): array
    {
        $query = self::order('sort_order', 'asc')->order('id', 'asc');

        if (!empty($params['category'])) {
            $query->where('category', $params['category']);
        }
        if (isset($params['is_enabled']) && $params['is_enabled'] !== '') {
            $query->where('is_enabled', (int)$params['is_enabled']);
        }
        if (!empty($params['keyword'])) {
            $kw = $params['keyword'];
            $query->where(function ($q) use ($kw) {
                $q->whereLike('question', "%{$kw}%")
                  ->whereOr('answer', 'like', "%{$kw}%");
            });
        }

        $page     = max(1, (int)($params['page'] ?? 1));
        $pageSize = min(100, max(1, (int)($params['limit'] ?? $params['pageSize'] ?? 20)));

        $total = $query->count();
        $list  = $query->page($page, $pageSize)->select()->toArray();

        return [
            'list'  => $list,
            'total' => $total,
        ];
    }
}