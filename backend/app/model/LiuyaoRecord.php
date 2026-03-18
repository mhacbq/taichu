<?php
declare(strict_types=1);

namespace app\model;

use app\service\SchemaInspector;
use think\Model;

/**
 * 六爻占卜记录模型
 */
class LiuyaoRecord extends Model
{
    protected static ?string $resolvedTable = null;

    protected $name = 'tc_liuyao_record';

    protected $pk = 'id';

    protected $autoWriteTimestamp = 'datetime';
    protected $createTime = 'created_at';
    protected $updateTime = false;

    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'consumed_points' => 'integer',
        'is_ai_analysis' => 'boolean',
    ];

    protected $hidden = [];

    public function __construct(array|object $data = [])
    {
        parent::__construct($data);
        $this->table = self::resolveTableName();
    }

    /**
     * 获取用户占卜次数
     */
    public static function getUserCount(int $userId): int
    {
        return (new self())
            ->where('user_id', $userId)
            ->count();
    }

    /**
     * 获取用户的最新记录
     */
    public static function getUserLatest(int $userId, int $limit = 10): array
    {
        return (new self())
            ->where('user_id', $userId)
            ->order('created_at', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    protected static function resolveTableName(): string
    {
        if (self::$resolvedTable !== null) {
            return self::$resolvedTable;
        }

        foreach (['tc_liuyao_record', 'liuyao_records'] as $table) {
            $columns = SchemaInspector::getTableColumns($table);
            if (!empty($columns) && isset($columns['user_id']) && isset($columns['question'])) {
                self::$resolvedTable = $table;
                return $table;
            }
        }

        self::$resolvedTable = 'tc_liuyao_record';
        return self::$resolvedTable;
    }
}
