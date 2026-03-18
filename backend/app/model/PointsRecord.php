<?php
declare(strict_types=1);

namespace app\model;

use app\service\SchemaInspector;
use think\facade\Db;
use think\Model;

class PointsRecord extends Model
{
    protected $table = 'tc_points_record';
    protected $pk = 'id';

    protected $autoWriteTimestamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = false;

    protected static array $tableColumnsCache = [];

    protected $schema = [
        'id' => 'int',
        'user_id' => 'int',
        'action' => 'string',
        'points' => 'int',
        'amount' => 'int',
        'balance' => 'int',
        'type' => 'string',
        'related_id' => 'int',
        'source_id' => 'int',
        'source_type' => 'string',
        'reason' => 'string',
        'remark' => 'string',
        'description' => 'string',
        'created_at' => 'datetime',
    ];

    /**
     * 获取用户积分记录
     */
    public static function getUserRecords(int $userId, int $limit = 50): array
    {
        return self::where('user_id', $userId)
            ->order('created_at', 'desc')
            ->limit($limit)
            ->select()
            ->toArray();
    }

    /**
     * 获取指定用户的当前积分余额映射
     */
    public static function getCurrentBalanceMap(array $userIds): array
    {
        $normalizedIds = array_values(array_unique(array_filter(array_map('intval', $userIds), static fn (int $value): bool => $value > 0)));
        if (empty($normalizedIds) || !SchemaInspector::tableExists('tc_user')) {
            return [];
        }

        $rows = Db::name('tc_user')->whereIn('id', $normalizedIds)->column('points', 'id');
        $balanceMap = [];
        foreach ($rows as $userId => $points) {
            $balanceMap[(int) $userId] = (int) $points;
        }

        return $balanceMap;
    }

    /**
     * 统一归一化积分记录列表，补齐 amount / balance / reason / direction 字段
     */
    public static function normalizeRecordList(array $rows, array $currentBalanceMap = []): array
    {
        $runningBalances = [];
        foreach ($currentBalanceMap as $userId => $balance) {
            $runningBalances[(int) $userId] = (int) $balance;
        }

        $normalized = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $normalized[] = self::normalizeRecordForDisplay($row, $runningBalances);
        }

        return $normalized;
    }

    /**
     * 归一化单条积分记录展示结构
     */
    public static function normalizeRecordForDisplay(array $row, array &$runningBalances = []): array
    {
        $userId = (int) ($row['user_id'] ?? 0);
        $delta = self::extractDelta($row);
        $direction = self::resolveDirection($delta, (string) ($row['type'] ?? ''));
        $businessType = trim((string) ($row['source_type'] ?? ($row['type'] ?? '')));
        $businessLabel = self::firstNonEmptyText([
            $row['action'] ?? null,
            $row['description'] ?? null,
            $businessType,
        ], '积分变动');
        $reason = self::firstNonEmptyText([
            $row['reason'] ?? null,
            $row['remark'] ?? null,
            $row['description'] ?? null,
            $row['action'] ?? null,
            $businessType,
        ], '积分变动');
        $amount = self::resolveDisplayAmount($row, $delta);

        $balance = self::resolveDisplayBalance($row, $userId, $delta, $runningBalances);

        return array_merge($row, [
            'points' => $delta,
            'type' => $direction,
            'direction' => $direction,
            'direction_label' => $direction === 'reduce' ? '减少' : '增加',
            'business_type' => $businessType,
            'business_label' => $businessLabel !== '' ? $businessLabel : '积分变动',
            'amount' => $amount,
            'balance' => $balance,
            'reason' => $reason !== '' ? $reason : '积分变动',
            'created_at' => (string) ($row['created_at'] ?? ''),
        ]);
    }

    /**
     * 记录积分变动
     */
    public static function record(
        int $userId,
        string $action,
        int $points,
        string $type = '',
        int $relatedId = 0,
        string $remark = '',
        array $extra = []
    ): self {
        return self::create(self::buildRecordPayload($userId, $action, $points, $type, $relatedId, $remark, $extra));
    }

    /**
     * 构建兼容新旧表结构的积分流水数据
     */
    public static function buildRecordPayload(
        int $userId,
        string $action,
        int $points,
        string $type = '',
        int $relatedId = 0,
        string $remark = '',
        array $extra = []
    ): array {
        $columns = self::getTableColumns();
        $normalizedType = trim($type);
        $direction = self::resolveDirection($points, $normalizedType);
        $reason = trim((string) ($extra['reason'] ?? ''));
        if ($reason === '') {
            $reason = trim($remark) !== '' ? trim($remark) : trim($action);
        }
        $description = trim((string) ($extra['description'] ?? ''));
        if ($description === '') {
            $description = $reason !== '' ? $reason : trim($action);
        }

        $payload = [
            'user_id' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        if (isset($columns['action'])) {
            $payload['action'] = trim($action);
        }
        if (isset($columns['points'])) {
            $payload['points'] = $points;
        }
        if (isset($columns['amount'])) {
            $payload['amount'] = abs($points);
        }
        if (isset($columns['type'])) {
            $payload['type'] = $normalizedType !== '' ? $normalizedType : $direction;
        }
        if (isset($columns['related_id'])) {
            $payload['related_id'] = max(0, $relatedId);
        }
        if (isset($columns['source_id'])) {
            $payload['source_id'] = max(0, (int) ($extra['source_id'] ?? $relatedId));
        }
        if (isset($columns['source_type'])) {
            $payload['source_type'] = self::resolveSourceType($normalizedType, $direction, $extra);
        }
        if (isset($columns['remark'])) {
            $payload['remark'] = trim($remark);
        }
        if (isset($columns['reason'])) {
            $payload['reason'] = $reason;
        }
        if (isset($columns['description'])) {
            $payload['description'] = $description;
        }
        if (isset($columns['balance'])) {
            $balance = $extra['balance'] ?? self::resolveCurrentBalance($userId);
            $payload['balance'] = is_numeric($balance) ? (int) $balance : null;
        }

        return $payload;
    }

    protected static function getTableColumns(): array
    {
        if (!isset(self::$tableColumnsCache[self::class])) {
            self::$tableColumnsCache[self::class] = SchemaInspector::tableExists('tc_points_record')
                ? SchemaInspector::getTableColumns('tc_points_record')
                : [];
        }

        return self::$tableColumnsCache[self::class];
    }

    protected static function resolveCurrentBalance(int $userId): ?int
    {
        if ($userId <= 0 || !SchemaInspector::tableExists('tc_user')) {
            return null;
        }

        $balance = Db::name('tc_user')->where('id', $userId)->value('points');
        return $balance === null ? null : (int) $balance;
    }

    protected static function extractDelta(array $row): int
    {
        foreach (['points', 'change_points'] as $field) {
            if (array_key_exists($field, $row) && is_numeric($row[$field])) {
                return (int) $row[$field];
            }
        }

        $amount = array_key_exists('amount', $row) && is_numeric($row['amount']) ? (int) $row['amount'] : 0;
        if ($amount === 0) {
            return 0;
        }

        $direction = self::resolveDirection(0, (string) ($row['type'] ?? $row['direction'] ?? ''));
        return $direction === 'reduce' ? -abs($amount) : abs($amount);
    }

    protected static function resolveDisplayAmount(array $row, int $delta): int
    {
        if (array_key_exists('amount', $row) && is_numeric($row['amount']) && (int) $row['amount'] !== 0) {
            return abs((int) $row['amount']);
        }

        return abs($delta);
    }

    protected static function resolveDisplayBalance(array $row, int $userId, int $delta, array &$runningBalances): ?int
    {
        foreach (['balance', 'after_balance', 'after_points', 'current_points'] as $field) {
            if (array_key_exists($field, $row) && $row[$field] !== null && $row[$field] !== '' && is_numeric($row[$field])) {
                $balance = (int) $row[$field];
                if ($userId > 0) {
                    $runningBalances[$userId] = $balance - $delta;
                }
                return $balance;
            }
        }

        if ($userId > 0 && array_key_exists($userId, $runningBalances)) {
            $balance = (int) $runningBalances[$userId];
            $runningBalances[$userId] = $balance - $delta;
            return $balance;
        }

        $estimated = self::estimateBalanceFromCurrentSnapshot($row, $userId);
        if ($estimated !== null && $userId > 0) {
            $runningBalances[$userId] = $estimated - $delta;
        }

        return $estimated;
    }

    protected static function estimateBalanceFromCurrentSnapshot(array $row, int $userId): ?int
    {
        if ($userId <= 0) {
            return null;
        }

        $columns = self::getTableColumns();
        if (!isset($columns['points'])) {
            return null;
        }

        $currentBalance = self::resolveCurrentBalance($userId);
        if ($currentBalance === null) {
            return null;
        }

        $laterQuery = Db::table('tc_points_record')->where('user_id', $userId);
        $createdAt = trim((string) ($row['created_at'] ?? ''));
        $recordId = (int) ($row['id'] ?? 0);

        if ($createdAt !== '') {
            $laterQuery->where(function ($query) use ($createdAt, $recordId) {
                $query->where('created_at', '>', $createdAt);
                if ($recordId > 0) {
                    $query->whereOr(function ($orQuery) use ($createdAt, $recordId) {
                        $orQuery->where('created_at', '=', $createdAt)->where('id', '>', $recordId);
                    });
                }
            });
        } elseif ($recordId > 0) {
            $laterQuery->where('id', '>', $recordId);
        }

        $laterDelta = (int) ($laterQuery->sum('points') ?? 0);
        return $currentBalance - $laterDelta;
    }

    protected static function firstNonEmptyText(array $values, string $default = ''): string
    {
        foreach ($values as $value) {
            $text = trim((string) ($value ?? ''));
            if ($text !== '') {
                return $text;
            }
        }

        return $default;
    }

    protected static function resolveDirection(int $points, string $type): string
    {
        if ($points < 0) {
            return 'reduce';
        }
        if ($points > 0) {
            return 'add';
        }

        return in_array(strtolower(trim($type)), [
            'reduce', 'sub', 'subtract', 'minus', 'consume', 'deduct', 'expense', 'cost', 'exchange', 'redeem', 'refund', 'tarot', 'bazi', 'hehun', 'liuyao'
        ], true) ? 'reduce' : 'add';
    }

    protected static function resolveSourceType(string $type, string $direction, array $extra): string

    {
        $sourceType = trim((string) ($extra['source_type'] ?? ''));
        if ($sourceType !== '') {
            return $sourceType;
        }

        if ($type !== '') {
            return $type;
        }

        return $direction;
    }
}
