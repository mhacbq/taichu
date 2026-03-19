<?php
declare(strict_types=1);

namespace app\model;

use app\service\SchemaInspector;
use think\facade\Db;
use think\Model;

/**
 * 塔罗占卜记录模型
 */
class TarotRecord extends Model
{
    protected $table = 'tc_tarot_record';
    protected $pk = 'id';

    protected $autoWriteTimestamp = false;

    protected static array $tableColumnsCache = [];

    // 牌阵类型
    public const SPREAD_SINGLE = 'single';
    public const SPREAD_THREE = 'three';
    public const SPREAD_CELTIC = 'celtic';
    public const SPREAD_RELATIONSHIP = 'relationship';

    protected $schema = [
        'id' => 'int',
        'user_id' => 'int',
        'spread_type' => 'string',
        'type' => 'string',
        'question' => 'string',
        'topic' => 'string',
        'cards' => 'json',
        'interpretation' => 'text',
        'ai_analysis' => 'text',
        'is_public' => 'int',
        'share_code' => 'string',
        'view_count' => 'int',
        'client_ip' => 'string',
        'created_at' => 'datetime',
        'create_time' => 'datetime',
    ];

    protected $json = ['cards'];

    /**
     * 获取记录表字段集合。
     */
    public static function getTableColumns(): array
    {
        if (!isset(self::$tableColumnsCache[self::class])) {
            self::$tableColumnsCache[self::class] = SchemaInspector::tableExists(self::resolveTableName())
                ? SchemaInspector::getTableColumns(self::resolveTableName())
                : [];
        }

        return self::$tableColumnsCache[self::class];
    }

    /**
     * 获取用户的塔罗历史（分页）。
     */
    public static function getUserHistory(int $userId, int $page = 1, int $pageSize = 10): array
    {
        $table = self::resolveTableName();
        $timeField = self::getCreateTimeField();

        $query = Db::name($table)
            ->where('user_id', $userId)
            ->order($timeField, 'desc');

        $total = (int) (clone $query)->count();
        $rows = $query->page($page, $pageSize)->select()->toArray();

        $list = [];
        foreach ($rows as $row) {
            $list[] = (new self($row))->toApiArray(true);
        }

        return [
            'list' => $list,
            'pagination' => [
                'page' => $page,
                'page_size' => $pageSize,
                'total' => $total,
                'total_pages' => (int) ceil($total / max($pageSize, 1)),
            ],
        ];
    }

    /**
     * 创建塔罗记录。
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
        $columns = self::getTableColumns();
        if (empty($columns)) {
            return null;
        }

        $payload = self::buildCreatePayload(
            $columns,
            $userId,
            $spreadType,
            $question,
            $cards,
            $interpretation,
            $aiAnalysis,
            $clientIp
        );

        try {
            $id = (int) Db::name(self::resolveTableName())->insertGetId($payload);
        } catch (\Throwable $e) {
            return null;
        }

        return $id > 0 ? self::find($id) : null;
    }

    /**
     * 根据分享码查找公开记录。
     */
    public static function findByShareCode(string $shareCode): ?self
    {
        $shareCode = trim($shareCode);
        if ($shareCode === '' || !self::hasColumn('share_code')) {
            return null;
        }

        $query = Db::name(self::resolveTableName())
            ->where('share_code', $shareCode);

        if (self::hasColumn('is_public')) {
            $query->where('is_public', 1);
        }

        $row = $query->find();
        if (!is_array($row) || empty($row['id'])) {
            return null;
        }

        return self::find((int) $row['id']);
    }

    /**
     * 获取单条记录（检查权限）。
     */
    public static function getByIdWithAuth(int $id, int $userId): ?self
    {
        $record = self::find($id);
        if (!$record) {
            return null;
        }

        if ((int) $record->getAttr('user_id') === $userId || $record->isPublicRecord()) {
            return $record;
        }

        return null;
    }

    /**
     * 设置公开状态。
     */
    public function setPublic(bool $isPublic): bool
    {
        $columns = self::getTableColumns();
        $hasIsPublic = isset($columns['is_public']);
        $hasShareCode = isset($columns['share_code']);

        if (!$hasIsPublic && !$hasShareCode) {
            throw new \RuntimeException('当前塔罗记录表缺少分享字段，请先补齐 share_code / is_public 结构后再使用分享功能。');
        }

        $updates = [];
        if ($hasIsPublic) {
            $updates['is_public'] = $isPublic ? 1 : 0;
        }

        if ($hasShareCode) {
            $currentShareCode = $this->getShareCodeValue();
            if ($isPublic) {
                $updates['share_code'] = $currentShareCode !== '' ? $currentShareCode : self::generateShareCode();
            } elseif (!$hasIsPublic) {
                $updates['share_code'] = '';
            }
        }

        if (empty($updates)) {
            return true;
        }

        $result = Db::name(self::resolveTableName())
            ->where('id', (int) $this->getAttr('id'))
            ->update($updates);

        if ($result === false) {
            return false;
        }

        foreach ($updates as $field => $value) {
            $this->setAttr($field, $value);
        }

        return true;
    }

    /**
     * 增加查看次数。
     */
    public function incrementViewCount(): void
    {
        if (!self::hasColumn('view_count')) {
            return;
        }

        $nextCount = $this->getViewCountValue() + 1;
        $result = Db::name(self::resolveTableName())
            ->where('id', (int) $this->getAttr('id'))
            ->update(['view_count' => $nextCount]);

        if ($result !== false) {
            $this->setAttr('view_count', $nextCount);
        }
    }

    /**
     * 获取牌阵名称。
     */
    public function getSpreadName(): string
    {
        return self::getSpreadNameByType($this->getSpreadTypeValue());
    }

    /**
     * 删除记录。
     */
    public static function deleteById(int $id, int $userId): bool
    {
        return Db::name(self::resolveTableName())
            ->where('id', $id)
            ->where('user_id', $userId)
            ->delete() > 0;
    }

    /**
     * 记录是否支持公开分享。
     */
    public function canShare(): bool
    {
        return self::hasColumn('share_code');
    }

    /**
     * 当前记录是否公开。
     */
    public function isPublicRecord(): bool
    {
        if (self::hasColumn('is_public')) {
            return (int) ($this->getAttr('is_public') ?? 0) === 1;
        }

        return $this->getShareCodeValue() !== '';
    }

    /**
     * 获取记录的 API 输出。
     */
    public function toApiArray(bool $truncateInterpretation = false): array
    {
        $interpretation = trim((string) ($this->getAttr('interpretation') ?? ''));
        if ($truncateInterpretation && $interpretation !== '' && mb_strlen($interpretation) > 100) {
            $interpretation = mb_substr($interpretation, 0, 100) . '...';
        }

        return [
            'id' => (int) ($this->getAttr('id') ?? 0),
            'user_id' => (int) ($this->getAttr('user_id') ?? 0),
            'spread_type' => $this->getSpreadTypeValue(),
            'spread_name' => $this->getSpreadName(),
            'question' => $this->getQuestionValue(),
            'cards' => $this->getCardsValue(),
            'interpretation' => $interpretation,
            'ai_analysis' => $this->getAttr('ai_analysis') ?? '',
            'is_public' => $this->isPublicRecord() ? 1 : 0,
            'share_code' => $this->getShareCodeValue(),
            'view_count' => $this->getViewCountValue(),
            'created_at' => $this->getCreatedAtValue(),
            'share_supported' => $this->canShare(),
        ];
    }


    /**
     * 获取牌阵类型值。
     */
    public function getSpreadTypeValue(): string
    {
        $value = trim((string) ($this->getAttr('spread_type') ?? ''));
        if ($value !== '') {
            return $value;
        }

        return trim((string) ($this->getAttr('type') ?? ''));
    }

    /**
     * 获取占问文本。
     */
    public function getQuestionValue(): string
    {
        $value = trim((string) ($this->getAttr('question') ?? ''));
        if ($value !== '') {
            return $value;
        }

        return trim((string) ($this->getAttr('topic') ?? ''));
    }

    /**
     * 获取牌阵卡牌数据，兼容模型属性与原始数据两种口径。
     */
    public function getCardsValue(): array
    {
        $candidates = [];

        foreach (['getAttr', 'getData'] as $method) {
            try {
                $candidates[] = $this->{$method}('cards');
            } catch (\Throwable $e) {
                continue;
            }
        }

        $candidates[] = $this->cards ?? null;

        foreach ($candidates as $candidate) {
            $normalized = self::normalizeCards($candidate);
            if ($normalized !== []) {
                return $normalized;
            }
        }

        return [];
    }

    /**
     * 获取分享码。
     */
    public function getShareCodeValue(): string
    {
        return trim((string) ($this->getAttr('share_code') ?? ''));
    }


    /**
     * 获取浏览次数。
     */
    public function getViewCountValue(): int
    {
        return (int) ($this->getAttr('view_count') ?? 0);
    }

    /**
     * 获取创建时间文本。
     */
    public function getCreatedAtValue(): string
    {
        $timeField = self::getCreateTimeField();
        $value = trim((string) ($this->getAttr($timeField) ?? ''));
        if ($value !== '') {
            return $value;
        }

        foreach (['created_at', 'create_time'] as $field) {
            $value = trim((string) ($this->getAttr($field) ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * 关联用户。
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * 生成插入 payload，兼容新旧表字段。
     */
    protected static function buildCreatePayload(
        array $columns,
        int $userId,
        string $spreadType,
        string $question,
        array $cards,
        string $interpretation,
        string $aiAnalysis,
        string $clientIp
    ): array {
        $payload = [];

        if (isset($columns['user_id'])) {
            $payload['user_id'] = $userId;
        }
        if (isset($columns['spread_type'])) {
            $payload['spread_type'] = $spreadType;
        }
        if (isset($columns['type'])) {
            $payload['type'] = $spreadType;
        }
        if (isset($columns['question'])) {
            $payload['question'] = $question;
        }
        if (isset($columns['topic'])) {
            $payload['topic'] = $question;
        }
        if (isset($columns['cards'])) {
            $payload['cards'] = json_encode($cards, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        if (isset($columns['interpretation'])) {
            $payload['interpretation'] = $interpretation;
        }
        if (isset($columns['ai_analysis'])) {
            $payload['ai_analysis'] = $aiAnalysis;
        }
        if (isset($columns['client_ip'])) {
            $payload['client_ip'] = $clientIp;
        }
        if (isset($columns['is_public'])) {
            $payload['is_public'] = 0;
        }
        if (isset($columns['share_code'])) {
            $payload['share_code'] = isset($columns['is_public']) ? self::generateShareCode() : '';
        }
        if (isset($columns['view_count'])) {
            $payload['view_count'] = 0;
        }

        $timeField = self::getCreateTimeField();
        if (isset($columns[$timeField])) {
            $payload[$timeField] = date('Y-m-d H:i:s');
        }

        return $payload;
    }

    /**
     * 生成分享码（带最大重试次数限制）。
     */
    protected static function generateShareCode(int $maxRetries = 10): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $table = self::resolveTableName();

        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }

            if (!self::hasColumn('share_code')) {
                return $code;
            }

            $exists = Db::name($table)->where('share_code', $code)->find();
            if (!$exists) {
                return $code;
            }
        }

        return uniqid('tarot_', true);
    }

    /**
     * 获取创建时间字段。
     */
    protected static function getCreateTimeField(): string
    {
        $columns = self::getTableColumns();
        foreach (['created_at', 'create_time'] as $field) {
            if (isset($columns[$field])) {
                return $field;
            }
        }

        return 'created_at';
    }

    /**
     * 当前表是否包含指定字段。
     */
    protected static function hasColumn(string $column): bool
    {
        return isset(self::getTableColumns()[$column]);
    }

    /**
     * 解析牌阵名称。
     */
    protected static function getSpreadNameByType(string $spreadType): string
    {
        $names = [
            self::SPREAD_SINGLE => '单牌占卜',
            self::SPREAD_THREE => '三张牌阵',
            self::SPREAD_CELTIC => '凯尔特十字',
            self::SPREAD_RELATIONSHIP => '关系牌阵',
        ];

        return $names[$spreadType] ?? '未知牌阵';
    }

    /**
     * 统一解析 cards 字段。
     */
    protected static function normalizeCards($cards): array
    {
        if (is_array($cards)) {
            return $cards;
        }

        if (!is_string($cards) || trim($cards) === '') {
            return [];
        }

        $decoded = json_decode($cards, true);
        return is_array($decoded) ? $decoded : [];
    }

    protected static function resolveTableName(): string
    {
        return 'tc_tarot_record';
    }
}
