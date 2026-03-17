<?php
declare(strict_types=1);

namespace app\model;

use app\service\SchemaInspector;
use think\facade\Db;
use think\Model;

/**
 * 八字合婚记录模型
 */
class HehunRecord extends Model
{
    protected $table = 'hehun_records';
    protected $pk = 'id';

    /**
     * 兼容新旧表名。
     */
    public static function resolveTableName(): string
    {
        foreach (['hehun_records', 'tc_hehun_record'] as $candidate) {
            if (SchemaInspector::tableExists($candidate)) {
                return $candidate;
            }
        }

        return 'hehun_records';
    }

    /**
     * 当前表字段集合。
     */
    public static function getTableColumns(): array
    {
        return SchemaInspector::getTableColumns(self::resolveTableName());
    }

    /**
     * 记录表是否已经可写。
     */
    public static function getStorageStatus(): array
    {
        $table = self::resolveTableName();
        $columns = self::getTableColumns();

        if (empty($columns)) {
            return [
                'ready' => false,
                'table' => $table,
                'message' => '未检测到可用的合婚记录表，请先初始化数据库。',
            ];
        }

        $hasLegacyBirth = isset($columns['male_birth'], $columns['female_birth']);
        $hasSplitBirth = isset($columns['male_birth_date'], $columns['female_birth_date']);

        if (!$hasLegacyBirth && !$hasSplitBirth) {
            return [
                'ready' => false,
                'table' => $table,
                'message' => '合婚记录表缺少出生时间字段，详细报告暂不可解锁。',
            ];
        }

        return [
            'ready' => true,
            'table' => $table,
            'message' => '合婚详细报告链路可用。',
        ];
    }

    /**
     * 获取记录创建时间字段。
     */
    public static function getCreateTimeField(): string
    {
        $columns = self::getTableColumns();

        foreach (['create_time', 'created_at'] as $field) {
            if (isset($columns[$field])) {
                return $field;
            }
        }

        return 'created_at';
    }

    /**
     * 获取积分字段。
     */
    protected static function getPointsField(): ?string
    {
        $columns = self::getTableColumns();

        foreach (['points_cost', 'consumed_points', 'points_used'] as $field) {
            if (isset($columns[$field])) {
                return $field;
            }
        }

        return null;
    }

    /**
     * 兼容不同表结构写入记录。
     */
    public static function createCompatible(array $payload): int
    {
        $status = self::getStorageStatus();
        if (!$status['ready']) {
            throw new \RuntimeException($status['message']);
        }

        $table = self::resolveTableName();
        $columns = self::getTableColumns();
        $birth = self::normalizeBirthPayload($payload);
        $insert = [];

        if (isset($columns['user_id'])) {
            $insert['user_id'] = (int)($payload['user_id'] ?? 0);
        }
        if (isset($columns['male_name'])) {
            $insert['male_name'] = (string)($payload['male_name'] ?? '男方');
        }
        if (isset($columns['female_name'])) {
            $insert['female_name'] = (string)($payload['female_name'] ?? '女方');
        }

        if (isset($columns['male_birth'])) {
            $insert['male_birth'] = $birth['male']['datetime'];
        }
        if (isset($columns['female_birth'])) {
            $insert['female_birth'] = $birth['female']['datetime'];
        }
        if (isset($columns['male_birth_date'])) {
            $insert['male_birth_date'] = $birth['male']['date'];
        }
        if (isset($columns['female_birth_date'])) {
            $insert['female_birth_date'] = $birth['female']['date'];
        }
        if (isset($columns['male_birth_time'])) {
            $insert['male_birth_time'] = $birth['male']['time'];
        }
        if (isset($columns['female_birth_time'])) {
            $insert['female_birth_time'] = $birth['female']['time'];
        }

        if (isset($columns['male_bazi'])) {
            $insert['male_bazi'] = json_encode($payload['male_bazi'] ?? [], JSON_UNESCAPED_UNICODE);
        }
        if (isset($columns['female_bazi'])) {
            $insert['female_bazi'] = json_encode($payload['female_bazi'] ?? [], JSON_UNESCAPED_UNICODE);
        }
        if (isset($columns['bazi_match'])) {
            $insert['bazi_match'] = json_encode($payload['result'] ?? [], JSON_UNESCAPED_UNICODE);
        }
        if (isset($columns['wuxing_match'])) {
            $insert['wuxing_match'] = json_encode([
                'score' => $payload['result']['details']['wuxing']['score'] ?? null,
                'detail' => $payload['result']['details']['wuxing']['description'] ?? ($payload['result']['details']['wuxing'] ?? null),
            ], JSON_UNESCAPED_UNICODE);
        }
        if (isset($columns['result'])) {
            $insert['result'] = json_encode($payload['result'] ?? [], JSON_UNESCAPED_UNICODE);
        }
        if (isset($columns['analysis'])) {
            $insert['analysis'] = self::buildAnalysisText($payload['result'] ?? [], $payload['ai_analysis'] ?? null);
        }
        if (isset($columns['score'])) {
            $insert['score'] = (int)($payload['score'] ?? ($payload['result']['score'] ?? 0));
        }
        if (isset($columns['level'])) {
            $insert['level'] = (string)($payload['level'] ?? ($payload['result']['level'] ?? ''));
        }
        if (isset($columns['ai_analysis'])) {
            $insert['ai_analysis'] = !empty($payload['ai_analysis'])
                ? json_encode($payload['ai_analysis'], JSON_UNESCAPED_UNICODE)
                : null;
        }
        if (isset($columns['is_ai_analysis'])) {
            $insert['is_ai_analysis'] = !empty($payload['is_ai_analysis']) ? 1 : 0;
        }

        $pointsField = self::getPointsField();
        if ($pointsField !== null) {
            $insert[$pointsField] = (int)($payload['points_cost'] ?? 0);
        }

        $createTimeField = self::getCreateTimeField();
        if (isset($columns[$createTimeField])) {
            $insert[$createTimeField] = date('Y-m-d H:i:s');
        }

        return (int)Db::name($table)->insertGetId($insert);
    }

    /**
     * 获取用户的合婚历史。
     */
    public static function getUserHistory(int $userId, int $limit = 20): array
    {
        $table = self::resolveTableName();
        $createTimeField = self::getCreateTimeField();

        $rows = Db::name($table)
            ->where('user_id', $userId)
            ->order($createTimeField, 'desc')
            ->limit($limit)
            ->select()
            ->toArray();

        return array_map([self::class, 'normalizeRecordRow'], $rows);
    }

    /**
     * 获取用户的合婚次数。
     */
    public static function getUserCount(int $userId): int
    {
        return (int)Db::name(self::resolveTableName())
            ->where('user_id', $userId)
            ->count();
    }

    /**
     * 获取今日合婚次数。
     */
    public static function getTodayCount(int $userId): int
    {
        $createTimeField = self::getCreateTimeField();
        $start = date('Y-m-d 00:00:00');
        $end = date('Y-m-d 23:59:59');

        return (int)Db::name(self::resolveTableName())
            ->where('user_id', $userId)
            ->where($createTimeField, '>=', $start)
            ->where($createTimeField, '<=', $end)
            ->count();
    }

    /**
     * 按用户读取单条记录。
     */
    public static function findUserRecord(int $recordId, int $userId): ?array
    {
        $row = Db::name(self::resolveTableName())
            ->where('id', $recordId)
            ->where('user_id', $userId)
            ->find();

        if (!$row) {
            return null;
        }

        return self::normalizeRecordRow((array)$row);
    }

    /**
     * 等级文本。
     */
    public static function getLevelText(string $level): string
    {
        $levels = [
            'excellent' => '天作之合',
            'good' => '佳偶天成',
            'medium' => '中等婚配',
            'fair' => '需加经营',
            'poor' => '谨慎考虑',
        ];

        return $levels[$level] ?? '未知';
    }

    /**
     * 规范记录结构，统一新旧表返回口径。
     */
    protected static function normalizeRecordRow(array $row): array
    {
        $createTimeField = self::getCreateTimeField();
        $pointsField = self::getPointsField();
        $result = self::decodeJsonField($row['result'] ?? null);

        if (empty($result) && isset($row['bazi_match'])) {
            $result = self::decodeJsonField($row['bazi_match']);
        }

        $level = (string)($row['level'] ?? ($result['level'] ?? ''));
        $score = (int)($row['score'] ?? ($result['score'] ?? 0));
        $pointsCost = $pointsField !== null ? (int)($row[$pointsField] ?? 0) : 0;
        $maleBirthDate = self::resolveBirthDateValue($row, 'male');
        $femaleBirthDate = self::resolveBirthDateValue($row, 'female');
        $hasAiAnalysis = self::resolveAiAnalysisFlag($row);
        $isPremium = self::resolvePremiumFlag($row, $result, $pointsCost, $hasAiAnalysis);
        $tier = $isPremium ? ($pointsCost > 0 ? 'premium' : 'vip') : 'free';
        $createdAt = (string)($row[$createTimeField] ?? '');

        return [
            'id' => (int)($row['id'] ?? 0),
            'user_id' => (int)($row['user_id'] ?? 0),
            'male_name' => (string)($row['male_name'] ?? '男方'),
            'female_name' => (string)($row['female_name'] ?? '女方'),
            'male_birth_date' => $maleBirthDate,
            'female_birth_date' => $femaleBirthDate,
            'male_bazi' => self::decodeJsonField($row['male_bazi'] ?? null),
            'female_bazi' => self::decodeJsonField($row['female_bazi'] ?? null),
            'result' => $result,
            'ai_analysis' => self::decodeJsonField($row['ai_analysis'] ?? null),
            'score' => $score,
            'level' => $level,
            'level_text' => self::getLevelText($level),
            'is_ai_analysis' => $hasAiAnalysis,
            'points_cost' => $pointsCost,
            'is_premium' => $isPremium,
            'tier' => $tier,
            'create_time' => $createdAt,
            'created_at' => $createdAt,
        ];
    }

    /**
     * 出生时间拆分为旧表/新表兼容结构。
     */
    protected static function normalizeBirthPayload(array $payload): array
    {
        return [
            'male' => self::splitBirthDateTime((string)($payload['male_birth_date'] ?? '')),
            'female' => self::splitBirthDateTime((string)($payload['female_birth_date'] ?? '')),
        ];
    }

    protected static function splitBirthDateTime(string $value): array
    {
        $value = trim($value);
        if ($value === '') {
            return ['datetime' => '', 'date' => '', 'time' => '00:00:00'];
        }

        $parts = preg_split('/\s+/', $value);
        $date = $parts[0] ?? '';
        $time = $parts[1] ?? '00:00:00';

        if (strlen($time) === 5) {
            $time .= ':00';
        }

        return [
            'datetime' => trim($date . ' ' . $time),
            'date' => $date,
            'time' => $time,
        ];
    }

    protected static function resolveBirthDateValue(array $row, string $role): string
    {
        $splitField = $role . '_birth_date';
        if (!empty($row[$splitField])) {
            return (string)$row[$splitField];
        }

        $legacyField = $role . '_birth';
        $legacyValue = trim((string)($row[$legacyField] ?? ''));
        if ($legacyValue === '') {
            return '';
        }

        $parts = preg_split('/\s+/', $legacyValue);
        return (string)($parts[0] ?? $legacyValue);
    }

    protected static function resolveAiAnalysisFlag(array $row): bool
    {
        if (array_key_exists('is_ai_analysis', $row)) {
            return !empty($row['is_ai_analysis']);
        }

        return !empty(self::decodeJsonField($row['ai_analysis'] ?? null));
    }

    protected static function resolvePremiumFlag(array $row, array $result, int $pointsCost, bool $hasAiAnalysis): bool
    {
        if (array_key_exists('is_premium', $row)) {
            return !empty($row['is_premium']);
        }

        if ($pointsCost > 0 || $hasAiAnalysis) {
            return true;
        }

        if (!empty($result['details']) || !empty($result['scores']) || !empty($result['solutions']) || !empty($result['dimension_scores']) || !empty($result['detail_analysis'])) {
            return true;
        }

        $analysis = $row['analysis'] ?? null;
        if (is_array($analysis)) {
            return !empty($analysis);
        }

        return is_string($analysis) && trim($analysis) !== '';
    }

    /**
     * 生成旧表 analysis 文本。
     */
    protected static function buildAnalysisText(array $result, $aiAnalysis): string
    {
        $sections = [];

        if (!empty($result['comment'])) {
            $sections[] = '综合评语：' . $result['comment'];
        }
        if (!empty($result['suggestions']) && is_array($result['suggestions'])) {
            $sections[] = '建议：' . implode('；', $result['suggestions']);
        }
        if (!empty($aiAnalysis)) {
            $sections[] = 'AI补充：' . (is_string($aiAnalysis) ? $aiAnalysis : json_encode($aiAnalysis, JSON_UNESCAPED_UNICODE));
        }

        return implode("\n", $sections);
    }

    /**
     * 解码 JSON / 文本字段。
     */
    protected static function decodeJsonField($value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }
}
