<?php
declare(strict_types=1);

namespace app\model;

use app\service\SchemaInspector;
use think\facade\Db;
use think\Model;

class BaziRecord extends Model
{
    public const TABLE_NAME = 'tc_bazi_record';
    public const SHARE_LOG_TABLE = 'tc_share_log';

    protected $table = self::TABLE_NAME;
    protected $pk = 'id';

    protected $autoWriteTimestamp = false;

    protected static array $tableColumnsCache = [];
    protected static array $shareLogColumnsCache = [];

    protected $schema = [
        'id' => 'int',
        'user_id' => 'int',
        'name' => 'string',
        'gender' => 'string',
        'birth_date' => 'string',
        'birth_time' => 'string',
        'location' => 'string',
        'birth_place' => 'string',
        'longitude' => 'float',
        'latitude' => 'float',
        'year_gan' => 'string',
        'year_zhi' => 'string',
        'month_gan' => 'string',
        'month_zhi' => 'string',
        'day_gan' => 'string',
        'day_zhi' => 'string',
        'hour_gan' => 'string',
        'hour_zhi' => 'string',
        'year_pillar' => 'string',
        'month_pillar' => 'string',
        'day_pillar' => 'string',
        'hour_pillar' => 'string',
        'analysis' => 'string',
        'report_data' => 'string',
        'is_first' => 'int',
        'is_paid' => 'int',
        'points_used' => 'int',
        'ai_analysis' => 'string',
        'dayun_scores' => 'string',
        'is_public' => 'int',
        'share_code' => 'string',
        'view_count' => 'int',
        'created_at' => 'datetime',
        'create_time' => 'datetime',
    ];

    /**
     * 获取记录表字段集合。
     */
    public static function getTableColumns(): array
    {
        if (!isset(self::$tableColumnsCache[self::class])) {
            self::$tableColumnsCache[self::class] = SchemaInspector::tableExists(self::TABLE_NAME)
                ? SchemaInspector::getTableColumns(self::TABLE_NAME)
                : [];
        }

        return self::$tableColumnsCache[self::class];
    }

    /**
     * 对外暴露字段存在性判断，便于控制器复用。
     */
    public static function hasTableColumn(string $column): bool
    {
        return isset(self::getTableColumns()[$column]);
    }

    /**
     * 创建兼容新旧表结构的八字记录。
     */
    public static function createRecord(array $input): ?self
    {
        $columns = self::getTableColumns();
        if (empty($columns)) {
            return null;
        }

        $payload = self::buildCreatePayload($columns, $input);
        if (empty($payload)) {
            return null;
        }

        try {
            $id = (int) Db::name(self::TABLE_NAME)->insertGetId($payload);
        } catch (\Throwable $e) {
            return null;
        }

        return $id > 0 ? self::find($id) : null;
    }

    /**
     * 获取用户的排盘历史（过滤敏感字段）。
     */
    public static function getUserHistory(int $userId, int $limit = 20): array
    {
        $result = self::getUserHistoryPaged($userId, 1, $limit);
        return $result['list'] ?? [];
    }

    /**
     * 获取用户的排盘历史（分页，兼容字段漂移）。
     */
    public static function getUserHistoryPaged(int $userId, int $page = 1, int $pageSize = 10): array
    {
        $query = Db::name(self::TABLE_NAME)
            ->where('user_id', $userId)
            ->order(self::getOrderField(), 'desc');

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
     * 设置分享状态。
     */
    public function setSharePublic(bool $isPublic): bool
    {
        $columns = self::getTableColumns();
        $hasNativeShare = isset($columns['is_public']) || isset($columns['share_code']);

        if ($hasNativeShare) {
            $updates = [];

            if (isset($columns['is_public'])) {
                $updates['is_public'] = $isPublic ? 1 : 0;
            }

            if (isset($columns['share_code'])) {
                $currentShareCode = $this->getShareCodeValue();
                if ($isPublic) {
                    $updates['share_code'] = $currentShareCode !== '' ? $currentShareCode : self::generateShareCode();
                } elseif (!isset($columns['is_public'])) {
                    $updates['share_code'] = '';
                }
            }

            if (!empty($updates)) {
                $result = Db::name(self::TABLE_NAME)
                    ->where('id', (int) $this->getAttr('id'))
                    ->update($updates);

                if ($result === false) {
                    return false;
                }

                foreach ($updates as $field => $value) {
                    $this->setAttr($field, $value);
                }
            }

            if (!self::shareLogTableExists()) {
                return true;
            }
        }

        return $this->syncShareLogVisibility($isPublic);
    }

    /**
     * 根据分享码查找记录。
     */
    public static function findByShareCode(string $shareCode): ?self
    {
        $shareCode = trim($shareCode);
        if ($shareCode === '') {
            return null;
        }

        if (self::hasTableColumn('share_code')) {
            $query = Db::name(self::TABLE_NAME)->where('share_code', $shareCode);
            if (self::hasTableColumn('is_public')) {
                $query->where('is_public', 1);
            }

            $row = $query->find();
            if (is_array($row) && !empty($row['id'])) {
                return self::find((int) $row['id']);
            }
        }

        $shareLog = self::findShareLogRow($shareCode);
        if (!is_array($shareLog) || empty($shareLog['content_id'])) {
            return null;
        }

        return self::find((int) $shareLog['content_id']);
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

        if ((int) ($record->getAttr('user_id') ?? 0) === $userId || $record->isPublicRecord()) {
            return $record;
        }

        return null;
    }

    /**
     * 增加查看次数。
     */
    public function incrementViewCount(): void
    {
        if (!self::hasTableColumn('view_count')) {
            return;
        }

        $nextCount = $this->getViewCountValue() + 1;
        $result = Db::name(self::TABLE_NAME)
            ->where('id', (int) $this->getAttr('id'))
            ->update(['view_count' => $nextCount]);

        if ($result !== false) {
            $this->setAttr('view_count', $nextCount);
        }
    }

    /**
     * 更新 AI 解盘结果缓存。
     */
    public static function saveAiAnalysis(int $recordId, int $userId, string $aiAnalysis): bool
    {
        if ($recordId <= 0 || !self::hasTableColumn('ai_analysis')) {
            return false;
        }

        $result = Db::name(self::TABLE_NAME)
            ->where('id', $recordId)
            ->where('user_id', $userId)
            ->update(['ai_analysis' => $aiAnalysis]);

        return $result !== false;
    }

    /**
     * 更新 AI 大运评分缓存。
     */
    public static function saveDayunScores(int $recordId, int $userId, array $scores): bool
    {
        if ($recordId <= 0 || !self::hasTableColumn('dayun_scores')) {
            return false;
        }

        $result = Db::name(self::TABLE_NAME)
            ->where('id', $recordId)
            ->where('user_id', $userId)
            ->update(['dayun_scores' => json_encode($scores, JSON_UNESCAPED_UNICODE)]);

        return $result !== false;
    }

    /**
     * 获取记录的 API 输出。
     */
    public function toApiArray(bool $truncateAnalysis = false): array
    {
        $analysis = $this->getAnalysisValue();
        if ($truncateAnalysis && $analysis !== '' && mb_strlen($analysis) > 120) {
            $analysis = mb_substr($analysis, 0, 120) . '...';
        }

        return [
            'id' => (int) ($this->getAttr('id') ?? 0),
            'user_id' => (int) ($this->getAttr('user_id') ?? 0),
            'birth_date' => $this->getBirthDateValue(),
            'gender' => $this->getGenderValue(),
            'location' => $this->getLocationValue(),
            'bazi' => $this->getBaziValue(),
            'analysis' => $analysis,
            'ai_analysis' => $truncateAnalysis ? null : $this->getAiAnalysisValue(),
            'dayun_scores' => $this->getDayunScoresValue(),
            'is_public' => $this->isPublicRecord() ? 1 : 0,
            'share_code' => $this->getShareCodeValue(),
            'view_count' => $this->getViewCountValue(),
            'created_at' => $this->getCreatedAtValue(),
            'share_supported' => $this->canShare(),
        ];
    }

    /**
     * 获取分享码。
     */
    public function getShareCodeValue(): string
    {
        $native = trim((string) ($this->getAttr('share_code') ?? ''));
        if ($native !== '') {
            return $native;
        }

        if (!self::shareLogTableExists()) {
            return '';
        }

        $row = self::findShareLogRowByRecordId((int) ($this->getAttr('id') ?? 0));
        return trim((string) ($row['share_code'] ?? ''));
    }

    /**
     * 获取浏览次数。
     */
    public function getViewCountValue(): int
    {
        return (int) ($this->getAttr('view_count') ?? 0);
    }

    /**
     * 当前记录是否可分享。
     */
    public function canShare(): bool
    {
        return self::hasTableColumn('share_code') || self::shareLogTableExists();
    }

    /**
     * 当前记录是否公开。
     */
    public function isPublicRecord(): bool
    {
        if (self::hasTableColumn('is_public')) {
            return (int) ($this->getAttr('is_public') ?? 0) === 1;
        }

        return $this->getShareCodeValue() !== '';
    }

    /**
     * 获取创建时间文本。
     */
    public function getCreatedAtValue(): string
    {
        foreach (['created_at', 'create_time'] as $field) {
            $value = trim((string) ($this->getAttr($field) ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    /**
     * 删除记录。
     */
    public static function deleteById(int $id, int $userId): bool
    {
        $record = self::where('id', $id)
            ->where('user_id', $userId)
            ->find();

        if (!$record) {
            return false;
        }

        if (self::shareLogTableExists()) {
            Db::name(self::SHARE_LOG_TABLE)
                ->where('type', 'bazi')
                ->where('content_id', $id)
                ->delete();
        }

        return $record->delete() !== false;
    }

    /**
     * 关联用户。
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    protected static function buildCreatePayload(array $columns, array $input): array
    {
        $birthDate = trim((string) ($input['birth_date'] ?? ''));
        $datePart = self::extractDatePart($birthDate);
        $timePart = self::extractTimePart($birthDate);
        $genderText = self::normalizeGenderText((string) ($input['gender'] ?? ''));
        $genderCode = self::genderToInt($genderText);
        $location = trim((string) ($input['location'] ?? ''));
        $pointsCost = (int) ($input['points_cost'] ?? 0);
        $bazi = is_array($input['bazi'] ?? null) ? $input['bazi'] : [];
        $reportData = self::buildReportData($input);
        $useLegacyGender = self::shouldUseLegacyGender($columns);

        $payload = [];

        if (isset($columns['user_id'])) {
            $payload['user_id'] = (int) ($input['user_id'] ?? 0);
        }
        if (isset($columns['name'])) {
            $payload['name'] = trim((string) ($input['name'] ?? ''));
        }
        if (isset($columns['gender'])) {
            $payload['gender'] = $useLegacyGender ? $genderText : $genderCode;
        }
        if (isset($columns['birth_date'])) {
            $payload['birth_date'] = $useLegacyGender ? $birthDate : $datePart;
        }
        if (isset($columns['birth_time'])) {
            $payload['birth_time'] = $timePart;
        }
        if (isset($columns['location'])) {
            $payload['location'] = $location;
        }
        if (isset($columns['birth_place'])) {
            $payload['birth_place'] = $location;
        }
        if (isset($columns['longitude']) && isset($input['longitude']) && is_numeric($input['longitude'])) {
            $payload['longitude'] = (float) $input['longitude'];
        }
        if (isset($columns['latitude']) && isset($input['latitude']) && is_numeric($input['latitude'])) {
            $payload['latitude'] = (float) $input['latitude'];
        }

        foreach (['year', 'month', 'day', 'hour'] as $pillar) {
            $ganField = $pillar . '_gan';
            $zhiField = $pillar . '_zhi';
            $pillarField = $pillar . '_pillar';
            $gan = trim((string) ($bazi[$pillar]['gan'] ?? ''));
            $zhi = trim((string) ($bazi[$pillar]['zhi'] ?? ''));

            if (isset($columns[$ganField])) {
                $payload[$ganField] = $gan;
            }
            if (isset($columns[$zhiField])) {
                $payload[$zhiField] = $zhi;
            }
            if (isset($columns[$pillarField])) {
                $payload[$pillarField] = $gan . $zhi;
            }
        }

        if (isset($columns['analysis'])) {
            $payload['analysis'] = trim((string) ($input['analysis'] ?? ''));
        }
        if (isset($columns['report_data'])) {
            $payload['report_data'] = json_encode($reportData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        if (isset($columns['dayun'])) {
            $payload['dayun'] = json_encode($input['dayun'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        if (isset($columns['liunian'])) {
            $payload['liunian'] = json_encode($input['liunian'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        if (isset($columns['is_first'])) {
            $payload['is_first'] = !empty($input['is_first']) ? 1 : 0;
        }
        if (isset($columns['is_paid'])) {
            $payload['is_paid'] = $pointsCost > 0 ? 1 : 0;
        }
        if (isset($columns['points_used'])) {
            $payload['points_used'] = $pointsCost;
        }
        if (isset($columns['is_public'])) {
            $payload['is_public'] = 0;
        }
        if (isset($columns['share_code'])) {
            $payload['share_code'] = '';
        }
        if (isset($columns['view_count'])) {
            $payload['view_count'] = 0;
        }
        if (isset($columns['created_at'])) {
            $payload['created_at'] = date('Y-m-d H:i:s');
        }
        if (isset($columns['create_time'])) {
            $payload['create_time'] = date('Y-m-d H:i:s');
        }

        return $payload;
    }

    protected static function buildReportData(array $input): array
    {
        return [
            'birth_date' => (string) ($input['birth_date'] ?? ''),
            'gender' => self::normalizeGenderText((string) ($input['gender'] ?? '')),
            'location' => (string) ($input['location'] ?? ''),
            'mode' => (string) ($input['mode'] ?? 'pro'),
            'bazi' => $input['bazi'] ?? [],
            'analysis' => (string) ($input['analysis'] ?? ''),
            'simpleInterpretation' => $input['simple_interpretation'] ?? null,
            'fullInterpretation' => $input['full_interpretation'] ?? null,
            'dayun' => $input['dayun'] ?? [],
            'liunian' => $input['liunian'] ?? [],
            'fortune_analysis' => $input['fortune_analysis'] ?? null,
            'true_solar_time' => $input['true_solar_time'] ?? null,
            'points_cost' => (int) ($input['points_cost'] ?? 0),
            'is_first_bazi' => !empty($input['is_first']),
        ];
    }

    protected static function shouldUseLegacyGender(array $columns): bool
    {
        return !(isset($columns['birth_time']) || isset($columns['birth_place']) || isset($columns['year_pillar']));
    }

    protected static function normalizeGenderText(string $gender): string
    {
        $gender = trim($gender);
        if (in_array($gender, ['1', 'male', '男'], true)) {
            return 'male';
        }
        if (in_array($gender, ['2', 'female', '女'], true)) {
            return 'female';
        }

        return $gender !== '' ? $gender : 'unknown';
    }

    protected static function genderToInt(string $gender): int
    {
        return $gender === 'female' ? 2 : 1;
    }

    protected static function extractDatePart(string $birthDate): string
    {
        $birthDate = trim($birthDate);
        if ($birthDate === '') {
            return '';
        }

        try {
            return (new \DateTimeImmutable($birthDate))->format('Y-m-d');
        } catch (\Throwable $e) {
            return strlen($birthDate) >= 10 ? substr($birthDate, 0, 10) : $birthDate;
        }
    }

    protected static function extractTimePart(string $birthDate): string
    {
        $birthDate = trim($birthDate);
        if ($birthDate === '') {
            return '00:00:00';
        }

        try {
            return (new \DateTimeImmutable($birthDate))->format('H:i:s');
        } catch (\Throwable $e) {
            if (preg_match('/\b(\d{2}:\d{2}(?::\d{2})?)\b/', $birthDate, $matches)) {
                return strlen($matches[1]) === 5 ? $matches[1] . ':00' : $matches[1];
            }
        }

        return '00:00:00';
    }

    protected function getBirthDateValue(): string
    {
        $birthDate = trim((string) ($this->getAttr('birth_date') ?? ''));
        $birthTime = trim((string) ($this->getAttr('birth_time') ?? ''));

        if ($birthDate !== '' && $birthTime !== '' && $birthTime !== '00:00:00' && !str_contains($birthDate, ':')) {
            return $birthDate . ' ' . $birthTime;
        }

        if ($birthDate !== '') {
            return $birthDate;
        }

        $reportData = $this->getReportDataValue();
        return trim((string) ($reportData['birth_date'] ?? ''));
    }

    protected function getGenderValue(): string
    {
        $gender = trim((string) ($this->getAttr('gender') ?? ''));
        if ($gender !== '') {
            return self::normalizeGenderText($gender);
        }

        $reportData = $this->getReportDataValue();
        return self::normalizeGenderText((string) ($reportData['gender'] ?? 'unknown'));
    }

    protected function getLocationValue(): string
    {
        foreach (['location', 'birth_place'] as $field) {
            $value = trim((string) ($this->getAttr($field) ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        $reportData = $this->getReportDataValue();
        return trim((string) ($reportData['location'] ?? ''));
    }

    protected function getBaziValue(): array
    {
        $reportData = $this->getReportDataValue();
        $reportBazi = is_array($reportData['bazi'] ?? null) ? $reportData['bazi'] : [];

        $bazi = [];
        foreach (['year', 'month', 'day', 'hour'] as $pillar) {
            $gan = $this->getPillarGan($pillar);
            $zhi = $this->getPillarZhi($pillar);
            if ($gan === '' && $zhi === '' && isset($reportBazi[$pillar])) {
                $gan = trim((string) ($reportBazi[$pillar]['gan'] ?? ''));
                $zhi = trim((string) ($reportBazi[$pillar]['zhi'] ?? ''));
            }

            $bazi[$pillar] = [
                'gan' => $gan,
                'zhi' => $zhi,
            ];
        }

        return $bazi;
    }

    protected function getPillarGan(string $pillar): string
    {
        $value = trim((string) ($this->getAttr($pillar . '_gan') ?? ''));
        if ($value !== '') {
            return $value;
        }

        $pillarValue = trim((string) ($this->getAttr($pillar . '_pillar') ?? ''));
        return mb_substr($pillarValue, 0, 1);
    }

    protected function getPillarZhi(string $pillar): string
    {
        $value = trim((string) ($this->getAttr($pillar . '_zhi') ?? ''));
        if ($value !== '') {
            return $value;
        }

        $pillarValue = trim((string) ($this->getAttr($pillar . '_pillar') ?? ''));
        return mb_substr($pillarValue, 1, 1);
    }

    protected function getAiAnalysisValue(): ?string
    {
        $value = trim((string) ($this->getAttr('ai_analysis') ?? ''));
        return $value !== '' ? $value : null;
    }

    protected function getDayunScoresValue(): array
    {
        $raw = trim((string) ($this->getAttr('dayun_scores') ?? ''));
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    protected function getAnalysisValue(): string
    {
        $analysis = trim((string) ($this->getAttr('analysis') ?? ''));
        if ($analysis !== '') {
            return $analysis;
        }

        $reportData = $this->getReportDataValue();
        $analysis = trim((string) ($reportData['analysis'] ?? ''));
        if ($analysis !== '') {
            return $analysis;
        }

        $fullInterpretation = $reportData['fullInterpretation'] ?? null;
        if (is_array($fullInterpretation)) {
            return json_encode($fullInterpretation, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        }

        return '';
    }

    protected function getReportDataValue(): array
    {
        $reportData = $this->getAttr('report_data');
        if (is_array($reportData)) {
            return $reportData;
        }

        if (is_string($reportData) && trim($reportData) !== '') {
            $decoded = json_decode($reportData, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return [];
    }

    protected static function getOrderField(): string
    {
        foreach (['created_at', 'create_time', 'id'] as $field) {
            if (self::hasTableColumn($field)) {
                return $field;
            }
        }

        return 'id';
    }

    protected static function shareLogTableExists(): bool
    {
        return SchemaInspector::tableExists(self::SHARE_LOG_TABLE);
    }

    protected static function getShareLogColumns(): array
    {
        if (!isset(self::$shareLogColumnsCache[self::class])) {
            self::$shareLogColumnsCache[self::class] = self::shareLogTableExists()
                ? SchemaInspector::getTableColumns(self::SHARE_LOG_TABLE)
                : [];
        }

        return self::$shareLogColumnsCache[self::class];
    }

    protected static function hasShareLogColumn(string $column): bool
    {
        return isset(self::getShareLogColumns()[$column]);
    }

    protected static function findShareLogRow(string $shareCode): ?array
    {
        if (!self::shareLogTableExists() || !self::hasShareLogColumn('share_code')) {
            return null;
        }

        $query = Db::name(self::SHARE_LOG_TABLE)
            ->where('share_code', $shareCode);

        if (self::hasShareLogColumn('type')) {
            $query->where('type', 'bazi');
        }
        if (self::hasShareLogColumn('content_id')) {
            $query->where('content_id', '>', 0);
        }

        $row = $query->order(self::getShareLogOrderField(), 'desc')->find();
        return is_array($row) ? $row : null;
    }

    protected static function findShareLogRowByRecordId(int $recordId): ?array
    {
        if ($recordId <= 0 || !self::shareLogTableExists()) {
            return null;
        }

        $query = Db::name(self::SHARE_LOG_TABLE)->where('content_id', $recordId);
        if (self::hasShareLogColumn('type')) {
            $query->where('type', 'bazi');
        }

        $row = $query->order(self::getShareLogOrderField(), 'desc')->find();
        return is_array($row) ? $row : null;
    }

    protected function syncShareLogVisibility(bool $isPublic): bool
    {
        if (!self::shareLogTableExists()) {
            throw new \RuntimeException('当前八字记录表缺少分享字段，且 tc_share_log 不存在，请先执行数据库兼容 SQL。');
        }

        $recordId = (int) ($this->getAttr('id') ?? 0);
        if ($recordId <= 0) {
            return false;
        }

        $query = Db::name(self::SHARE_LOG_TABLE)->where('content_id', $recordId);
        if (self::hasShareLogColumn('type')) {
            $query->where('type', 'bazi');
        }

        if (!$isPublic) {
            $result = $query->delete();
            if ($result === false) {
                return false;
            }
            if (self::hasTableColumn('share_code')) {
                $this->setAttr('share_code', '');
            }
            if (self::hasTableColumn('is_public')) {
                $this->setAttr('is_public', 0);
            }
            return true;
        }

        $shareCode = $this->getShareCodeValue();
        if ($shareCode === '') {
            $shareCode = self::generateShareCode();
        }

        $query->delete();
        $result = Db::name(self::SHARE_LOG_TABLE)->insert(self::buildShareLogPayload($recordId, $shareCode));
        if ($result === 0) {
            return false;
        }

        if (self::hasTableColumn('share_code')) {
            $this->setAttr('share_code', $shareCode);
        }
        if (self::hasTableColumn('is_public')) {
            $this->setAttr('is_public', 1);
        }

        return true;
    }

    protected static function buildShareLogPayload(int $recordId, string $shareCode): array
    {
        $columns = self::getShareLogColumns();
        $now = date('Y-m-d H:i:s');
        $payload = [];

        if (isset($columns['user_id'])) {
            $payload['user_id'] = (int) Db::name(self::TABLE_NAME)->where('id', $recordId)->value('user_id');
        }
        if (isset($columns['type'])) {
            $payload['type'] = 'bazi';
        }
        if (isset($columns['platform'])) {
            $payload['platform'] = 'record_public';
        }
        if (isset($columns['content_id'])) {
            $payload['content_id'] = $recordId;
        }
        if (isset($columns['content_type'])) {
            $payload['content_type'] = 'bazi';
        }
        if (isset($columns['share_code'])) {
            $payload['share_code'] = $shareCode;
        }
        if (isset($columns['points_reward'])) {
            $payload['points_reward'] = 0;
        }
        if (isset($columns['ip'])) {
            $payload['ip'] = '';
        }
        if (isset($columns['created_at'])) {
            $payload['created_at'] = $now;
        }
        if (isset($columns['updated_at'])) {
            $payload['updated_at'] = $now;
        }

        return $payload;
    }

    protected static function generateShareCode(int $maxRetries = 10): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

        for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
            $code = '';
            for ($i = 0; $i < 8; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }

            if (self::findByShareCode($code) === null) {
                return $code;
            }
        }

        return uniqid('bazi_', true);
    }

    protected static function getShareLogOrderField(): string
    {
        foreach (['created_at', 'id'] as $field) {
            if (self::hasShareLogColumn($field)) {
                return $field;
            }
        }

        return 'id';
    }
}
