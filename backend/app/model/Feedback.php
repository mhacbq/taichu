<?php
declare(strict_types=1);

namespace app\model;

use think\Model;

/**
 * 用户反馈模型
 */
class Feedback extends Model
{
    public const STATUS_PENDING = 0;
    public const STATUS_PROCESSING = 1;
    public const STATUS_RESOLVED = 2;
    public const STATUS_CLOSED = 3;

    protected const STATUS_CODE_MAP = [
        self::STATUS_PENDING => 'pending',
        self::STATUS_PROCESSING => 'processing',
        self::STATUS_RESOLVED => 'resolved',
        self::STATUS_CLOSED => 'closed',
    ];

    protected $table = 'feedback';

    protected $autoWriteTimestamp = true;

    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    protected $type = [
        'id' => 'integer',
        'user_id' => 'integer',
        'status' => 'integer',
    ];

    /**
     * 获取反馈列表
     */
    public static function getList($params = [])
    {
        $query = self::order('id', 'desc');

        if (!empty($params['type'])) {
            $query->where('type', $params['type']);
        }

        if (($params['status'] ?? '') !== '') {
            $statusValue = self::normalizeStatusValue($params['status']);
            if ($statusValue !== null) {
                $statusCode = self::normalizeStatusCode($statusValue);
                $query->where(function ($subQuery) use ($statusValue, $statusCode) {
                    $subQuery->where('status', $statusValue)
                        ->whereOr('status', $statusCode);
                });
            }
        }

        if (!empty($params['user_id'])) {
            $query->where('user_id', $params['user_id']);
        }

        if (!empty($params['keyword'])) {
            $query->where('content|contact', 'like', "%{$params['keyword']}%");
        }

        return $query;
    }

    /**
     * 获取用户的反馈列表
     */
    public static function getUserFeedback($userId, $params = [])
    {
        $query = self::where('user_id', $userId)
            ->order('id', 'desc');

        if (!empty($params['type'])) {
            $query->where('type', $params['type']);
        }

        if (($params['status'] ?? '') !== '') {
            $statusValue = self::normalizeStatusValue($params['status']);
            if ($statusValue !== null) {
                $statusCode = self::normalizeStatusCode($statusValue);
                $query->where(function ($subQuery) use ($statusValue, $statusCode) {
                    $subQuery->where('status', $statusValue)
                        ->whereOr('status', $statusCode);
                });
            }
        }

        return $query;
    }

    /**
     * 创建反馈
     */
    public static function createFeedback($data)
    {
        return self::create([
            'user_id' => $data['user_id'] ?? 0,
            'type' => $data['type'] ?? 'other',
            'content' => $data['content'] ?? '',
            'contact' => $data['contact'] ?? '',
            'images' => $data['images'] ?? '',
            'status' => self::STATUS_PENDING,
            'reply' => '',
            'replied_at' => null,
        ]);
    }

    /**
     * 回复反馈
     */
    public function reply($reply, $status = 'resolved')
    {
        $this->reply = $reply;
        $this->status = self::normalizeStatusValue($status, self::STATUS_RESOLVED);
        $this->replied_at = date('Y-m-d H:i:s');
        return $this->save();
    }

    /**
     * 归一化反馈状态为整型值
     */
    public static function normalizeStatusValue(mixed $status, ?int $default = null): ?int
    {
        if ($status === null || $status === '') {
            return $default;
        }

        if (is_numeric($status)) {
            $normalized = (int) $status;
            return array_key_exists($normalized, self::STATUS_CODE_MAP) ? $normalized : $default;
        }

        $normalized = strtolower(trim((string) $status));
        $map = array_flip(self::STATUS_CODE_MAP);

        return $map[$normalized] ?? $default;
    }

    /**
     * 归一化反馈状态为对外字符串编码
     */
    public static function normalizeStatusCode(mixed $status, string $default = 'pending'): string
    {
        $statusValue = self::normalizeStatusValue($status);
        if ($statusValue === null) {
            return $default;
        }

        return self::STATUS_CODE_MAP[$statusValue] ?? $default;
    }
}
