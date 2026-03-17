<?php
declare(strict_types=1);

namespace app\controller\admin;

use app\BaseController;
use think\Request;
use think\facade\Db;

/**
 * 后台黄历管理控制器
 */
class Almanac extends BaseController
{
    protected $middleware = [\app\middleware\AdminAuth::class];

    protected const DEFAULT_PAGE_SIZE = 20;
    protected const MAX_PAGE_SIZE = 100;

    /**
     * 黄历列表
     */
    public function almanacList(Request $request)
    {
        if (!$this->hasAdminPermission('almanac_view')) {
            return $this->error('无权限查看黄历', 403);
        }

        try {
            $table = $this->resolveCompatibleTable(['tc_almanac', 'almanac'], 'tc_almanac');
            if (!$this->tableExists($table)) {
                return $this->error('黄历数据表不存在，请先初始化黄历表', 500);
            }

            $columns = $this->getCompatibleTableColumns($table);
            $dateColumn = $this->resolveAlmanacDateColumn($columns);
            if ($dateColumn === null) {
                return $this->error('黄历数据表缺少日期字段', 500);
            }

            $date = trim((string) $request->get('date', ''));
            $year = filter_var($request->get('year', date('Y')), FILTER_VALIDATE_INT) ?: (int) date('Y');
            $month = filter_var($request->get('month', date('m')), FILTER_VALIDATE_INT) ?: (int) date('m');
            $pagination = $this->normalizePagination(
                $request->get('page', 1),
                $request->get('limit', $request->get('pageSize', self::DEFAULT_PAGE_SIZE)),
                self::DEFAULT_PAGE_SIZE,
                self::MAX_PAGE_SIZE
            );

            $query = Db::table($table)->order($dateColumn, 'desc');
            if ($date !== '') {
                $query->where($dateColumn, $date);
            } else {
                $startDate = sprintf('%04d-%02d-01', $year, $month);
                $endDate = sprintf('%04d-%02d-%02d', $year, $month, cal_days_in_month(CAL_GREGORIAN, $month, $year));
                $query->where($dateColumn, '>=', $startDate)
                    ->where($dateColumn, '<=', $endDate);
            }

            $total = $query->count();
            $list = $query->page($pagination['page'], $pagination['pageSize'])->select()->toArray();
            $list = array_map(fn (array $row): array => $this->normalizeAlmanacRecord($row), $list);

            return $this->success([
                'list' => $list,
                'total' => $total,
                'year' => $year,
                'month' => $month,
                'page' => $pagination['page'],
                'limit' => $pagination['pageSize'],
                'pageSize' => $pagination['pageSize'],
            ]);
        } catch (\Throwable $e) {
            return $this->respondSystemException('almanac_list', $e, '获取黄历列表失败，请稍后重试', [
                'date' => $request->get('date', ''),
                'year' => $request->get('year', date('Y')),
                'month' => $request->get('month', date('m')),
            ]);
        }
    }

    /**
     * 保存黄历
     */
    public function saveAlmanac(Request $request)
    {
        if (!$this->hasAdminPermission('almanac_edit')) {
            return $this->error('无权限编辑黄历', 403);
        }

        return $this->persistAlmanacRecord($request->post(), (int) $request->post('id', 0));
    }

    /**
     * 更新黄历
     */
    public function updateAlmanac(Request $request, int $id)
    {
        if (!$this->hasAdminPermission('almanac_edit')) {
            return $this->error('无权限编辑黄历', 403);
        }

        $payload = $request->put();
        if (!is_array($payload)) {
            $payload = [];
        }
        $payload['id'] = $id;

        return $this->persistAlmanacRecord($payload, $id);
    }

    /**
     * 删除黄历
     */
    public function deleteAlmanac(int $id)
    {
        if (!$this->hasAdminPermission('almanac_edit')) {
            return $this->error('无权限删除黄历', 403);
        }

        try {
            $table = $this->resolveCompatibleTable(['tc_almanac', 'almanac'], 'tc_almanac');
            if (!$this->tableExists($table)) {
                return $this->error('黄历数据表不存在，请先初始化黄历表', 500);
            }

            $record = Db::table($table)->where('id', $id)->find();
            if (!$record) {
                return $this->error('黄历记录不存在', 404);
            }

            Db::table($table)->where('id', $id)->delete();

            $this->logOperation('delete_almanac', 'almanac', [
                'target_id' => $id,
                'detail' => '删除黄历: ' . (string) ($record['solar_date'] ?? $record['date'] ?? $id),
                'before_data' => $this->normalizeAlmanacRecord($record),
            ]);

            return $this->success([], '删除成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('almanac_delete', $e, '删除黄历失败，请稍后重试', [
                'id' => $id,
            ]);
        }
    }

    /**
     * 生成月历
     */
    public function generateAlmanacMonth(Request $request)
    {
        if (!$this->hasAdminPermission('almanac_edit')) {
            return $this->error('无权限生成黄历', 403);
        }

        $year = $request->post('year', date('Y'));
        $month = $request->post('month', date('m'));

        if (!is_numeric($year) || !is_numeric($month)) {
            return $this->error('年份和月份必须是数字', 400);
        }

        try {
            $table = $this->resolveCompatibleTable(['tc_almanac', 'almanac'], 'tc_almanac');
            if (!$this->tableExists($table)) {
                return $this->error('黄历数据表不存在，请先初始化黄历表', 500);
            }

            $columns = $this->getCompatibleTableColumns($table);
            $dateColumn = $this->resolveAlmanacDateColumn($columns);
            if ($dateColumn === null) {
                return $this->error('黄历数据表缺少日期字段', 500);
            }

            $daysInMonth = cal_days_in_month(CAL_GREGORIAN, (int) $month, (int) $year);
            $insertData = [];

            for ($day = 1; $day <= $daysInMonth; $day++) {
                $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
                $exists = Db::table($table)->where($dateColumn, $date)->find();

                if (!$exists) {
                    $lunarInfo = $this->solarToLunar($year, $month, $day);
                    $insertData[] = $this->buildAlmanacSavePayload([
                        'solar_date' => $date,
                        'lunar_date' => $lunarInfo['lunar_date'] ?? '',
                        'ganzhi' => $lunarInfo['ganzhi'] ?? '',
                        'yi' => $lunarInfo['yi'] ?? [],
                        'ji' => $lunarInfo['ji'] ?? [],
                        'jishen' => $lunarInfo['jishen'] ?? [],
                        'xiongsha' => $lunarInfo['xiongsha'] ?? [],
                        'sha' => $lunarInfo['sha'] ?? '',
                        'zhiri' => $lunarInfo['zhiri'] ?? '',
                        'shichen' => $lunarInfo['shichen'] ?? [],
                    ], $columns, $date);
                }
            }

            $insertData = array_values(array_filter($insertData));
            if (!empty($insertData)) {
                Db::table($table)->insertAll($insertData);
            }

            $this->logOperation('generate_almanac_month', 'almanac', [
                'target_id' => 0,
                'detail' => sprintf('生成 %s年%s月 黄历，共%s条', $year, $month, count($insertData)),
                'after_data' => [
                    'year' => (int) $year,
                    'month' => (int) $month,
                    'generated' => count($insertData),
                ],
            ]);

            return $this->success([
                'generated' => count($insertData),
            ], '生成成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('almanac_generate_month', $e, '生成黄历失败，请稍后重试', [
                'year' => $year,
                'month' => $month,
            ]);
        }
    }

    /**
     * 持久化黄历记录
     */
    protected function persistAlmanacRecord(array $data, int $id = 0)
    {
        $table = $this->resolveCompatibleTable(['tc_almanac', 'almanac'], 'tc_almanac');
        if (!$this->tableExists($table)) {
            return $this->error('黄历数据表不存在，请先初始化黄历表', 500);
        }

        $columns = $this->getCompatibleTableColumns($table);
        $dateColumn = $this->resolveAlmanacDateColumn($columns);
        if ($dateColumn === null) {
            return $this->error('黄历数据表缺少日期字段', 500);
        }

        try {
            $existing = null;
            if ($id > 0 && isset($columns['id'])) {
                $existing = Db::table($table)->where('id', $id)->find();
                if (!$existing) {
                    return $this->error('黄历记录不存在', 404);
                }
            }

            $recordDate = trim((string) ($data['solar_date'] ?? $data['date'] ?? ($existing[$dateColumn] ?? '')));
            if ($recordDate === '') {
                return $this->error('阳历日期不能为空', 400);
            }

            $saveData = $this->buildAlmanacSavePayload($data, $columns, $recordDate, $existing === null);
            if (empty($saveData)) {
                return $this->error('没有可保存的黄历字段', 400);
            }

            if ($existing) {
                Db::table($table)->where('id', $id)->update($saveData);
            } else {
                $target = Db::table($table)->where($dateColumn, $recordDate)->find();
                if ($target) {
                    Db::table($table)->where($dateColumn, $recordDate)->update($saveData);
                } else {
                    Db::table($table)->insert($saveData);
                }
            }

            $this->logOperation('save_almanac', 'almanac', [
                'target_id' => $id,
                'detail' => '保存黄历: ' . $recordDate,
                'after_data' => array_merge(['date' => $recordDate], $data),
            ]);

            return $this->success([
                'date' => $recordDate,
            ], '保存成功');
        } catch (\Throwable $e) {
            return $this->respondSystemException('almanac_save', $e, '保存黄历失败，请稍后重试', [
                'id' => $id,
                'date' => $data['solar_date'] ?? ($data['date'] ?? ''),
            ]);
        }
    }

    /**
     * 构建黄历存储数据
     */
    protected function buildAlmanacSavePayload(array $data, array $columns, string $recordDate, bool $isCreate = true): array
    {
        $saveData = [];
        if (isset($columns['solar_date'])) {
            $saveData['solar_date'] = $recordDate;
        }
        if (isset($columns['date'])) {
            $saveData['date'] = $recordDate;
        }
        if (isset($columns['lunar_date'])) {
            $saveData['lunar_date'] = (string) ($data['lunar_date'] ?? '');
        }
        if (isset($columns['ganzhi'])) {
            $saveData['ganzhi'] = (string) ($data['ganzhi'] ?? '');
        } elseif (isset($columns['ganzhi_day'])) {
            $saveData['ganzhi_day'] = (string) ($data['ganzhi'] ?? ($data['ganzhi_day'] ?? ''));
        }
        if (isset($columns['yi'])) {
            $saveData['yi'] = $this->normalizeAlmanacStorageValue($data['yi'] ?? ($data['suit'] ?? ''), $columns, 'yi');
        }
        if (isset($columns['ji'])) {
            $saveData['ji'] = $this->normalizeAlmanacStorageValue($data['ji'] ?? ($data['avoid'] ?? ''), $columns, 'ji');
        }
        if (isset($columns['jishen'])) {
            $saveData['jishen'] = $this->normalizeAlmanacStorageValue($data['jishen'] ?? '', $columns, 'jishen');
        }
        if (isset($columns['xiongsha'])) {
            $saveData['xiongsha'] = $this->normalizeAlmanacStorageValue($data['xiongsha'] ?? '', $columns, 'xiongsha');
        }
        if (isset($columns['sha'])) {
            $saveData['sha'] = (string) ($data['sha'] ?? '');
        }
        if (isset($columns['zhiri'])) {
            $saveData['zhiri'] = (string) ($data['zhiri'] ?? '');
        }
        if (isset($columns['shichen'])) {
            $saveData['shichen'] = $this->normalizeAlmanacStorageValue($data['shichen'] ?? '', $columns, 'shichen');
        }
        if (isset($columns['wuxing'])) {
            $saveData['wuxing'] = (string) ($data['wuxing'] ?? '');
        } elseif (isset($columns['nayin']) && !empty($data['wuxing']) && empty($data['nayin'])) {
            $saveData['nayin'] = (string) $data['wuxing'];
        }
        if (isset($columns['updated_at'])) {
            $saveData['updated_at'] = date('Y-m-d H:i:s');
        }
        if ($isCreate && isset($columns['created_at'])) {
            $saveData['created_at'] = date('Y-m-d H:i:s');
        }

        return $saveData;
    }

    /**
     * 兼容不同黄历表结构的字段输出
     */
    protected function normalizeAlmanacRecord(array $row): array
    {
        $date = (string) ($row['solar_date'] ?? ($row['date'] ?? ''));
        $ganzhi = (string) (($row['ganzhi'] ?? '') ?: ($row['ganzhi_day'] ?? ''));
        $wuxing = (string) (($row['wuxing'] ?? '') ?: ($row['nayin'] ?? ''));
        $suit = $this->stringifyAlmanacValue($row['yi'] ?? '');
        $avoid = $this->stringifyAlmanacValue($row['ji'] ?? '');

        return array_merge($row, [
            'date' => $date,
            'solar_date' => $date,
            'suit' => $suit,
            'avoid' => $avoid,
            'yi' => $row['yi'] ?? $suit,
            'ji' => $row['ji'] ?? $avoid,
            'ganzhi' => $ganzhi,
            'wuxing' => $wuxing,
        ]);
    }

    /**
     * 兼容字符串/JSON 黄历字段
     */
    protected function stringifyAlmanacValue(mixed $value): string
    {
        if (is_array($value)) {
            return implode(' ', array_map('strval', array_filter($value, static fn ($item): bool => $item !== null && $item !== '')));
        }

        $stringValue = trim((string) $value);
        if ($stringValue === '') {
            return '';
        }

        $decoded = json_decode($stringValue, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $flattened = [];
            foreach ($decoded as $item) {
                if (is_scalar($item)) {
                    $flattened[] = (string) $item;
                }
            }
            if (!empty($flattened)) {
                return implode(' ', $flattened);
            }
        }

        return $stringValue;
    }

    /**
     * 根据字段类型归一化黄历存储值
     */
    protected function normalizeAlmanacStorageValue(mixed $value, array $columns, string $field): string
    {
        if (is_array($value)) {
            $items = array_values(array_filter(array_map('strval', $value), static fn (string $item): bool => trim($item) !== ''));
            return $this->isJsonColumn($columns, $field)
                ? json_encode($items, JSON_UNESCAPED_UNICODE)
                : implode(' ', $items);
        }

        $stringValue = trim((string) $value);
        if ($stringValue === '') {
            return $this->isJsonColumn($columns, $field) ? '[]' : '';
        }

        if (!$this->isJsonColumn($columns, $field)) {
            return $stringValue;
        }

        $decoded = json_decode($stringValue, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return json_encode($decoded, JSON_UNESCAPED_UNICODE);
        }

        $parts = preg_split('/[\s,，]+/u', $stringValue, -1, PREG_SPLIT_NO_EMPTY);
        return json_encode(array_values($parts ?: [$stringValue]), JSON_UNESCAPED_UNICODE);
    }

    /**
     * 解析黄历日期字段名
     */
    protected function resolveAlmanacDateColumn(array $columns): ?string
    {
        foreach (['solar_date', 'date'] as $field) {
            if (isset($columns[$field])) {
                return $field;
            }
        }

        return null;
    }

    /**
     * 判断是否为 JSON 字段
     */
    protected function isJsonColumn(array $columns, string $field): bool
    {
        return isset($columns[$field]) && str_contains($columns[$field], 'json');
    }

    /**
     * 解析兼容表名
     */
    protected function resolveCompatibleTable(array $candidates, string $fallback): string
    {
        foreach ($candidates as $table) {
            if ($this->tableExists($table)) {
                return $table;
            }
        }

        return $fallback;
    }

    /**
     * 获取兼容表字段信息
     */
    protected function getCompatibleTableColumns(string $table): array
    {
        $escapedTable = str_replace('`', '``', $table);
        $columns = [];

        foreach (Db::query("SHOW COLUMNS FROM `{$escapedTable}`") as $column) {
            $field = (string) ($column['Field'] ?? '');
            if ($field !== '') {
                $columns[$field] = strtolower((string) ($column['Type'] ?? ''));
            }
        }

        return $columns;
    }

    /**
     * 判断数据表是否存在
     */
    protected function tableExists(string $table): bool
    {
        $escapedTable = addslashes($table);
        return !empty(Db::query("SHOW TABLES LIKE '{$escapedTable}'"));
    }

    /**
     * 阳历转农历（简化版）
     */
    private function solarToLunar($year, $month, $day)
    {
        $gan = ['甲', '乙', '丙', '丁', '戊', '己', '庚', '辛', '壬', '癸'];
        $zhi = ['子', '丑', '寅', '卯', '辰', '巳', '午', '未', '申', '酉', '戌', '亥'];
        $zhiri = ['建', '除', '满', '平', '定', '执', '破', '危', '成', '收', '开', '闭'];

        $baseDate = strtotime('1900-01-31');
        $targetDate = strtotime("{$year}-{$month}-{$day}");
        $daysDiff = floor(($targetDate - $baseDate) / 86400);

        $ganIndex = $daysDiff % 10;
        $zhiIndex = $daysDiff % 12;
        $yearGanIndex = ($year - 4) % 10;
        $yearZhiIndex = ($year - 4) % 12;

        $commonYi = ['祭祀', '祈福', '出行', '结婚', '开业', '动土', '安床', '纳财'];
        $commonJi = ['安葬', '破土', '开仓', '出货', '词讼', '求医'];

        $dayGan = $gan[$ganIndex];
        if (in_array($dayGan, ['甲', '乙', '丙', '丁'], true)) {
            $yi = array_slice($commonYi, 0, 4);
            $ji = array_slice($commonJi, 0, 2);
        } else {
            $yi = array_slice($commonYi, 2, 4);
            $ji = array_slice($commonJi, 2, 2);
        }

        return [
            'lunar_date' => '农历' . $month . '月' . $day,
            'ganzhi' => $gan[$yearGanIndex] . $zhi[$yearZhiIndex] . '年 ' . $gan[$ganIndex] . $zhi[$zhiIndex] . '日',
            'yi' => $yi,
            'ji' => $ji,
            'jishen' => ['天德', '月德'],
            'xiongsha' => ['五虚', '土符'],
            'sha' => ['东', '南', '西', '北'][$day % 4],
            'zhiri' => $zhiri[$day % 12],
            'shichen' => [
                ['name' => '子', 'time' => '23:00-01:00', 'type' => 'ji', 'yiji' => '宜祭祀 忌动土'],
                ['name' => '丑', 'time' => '01:00-03:00', 'type' => 'xiaoJi', 'yiji' => '宜安床 忌出行'],
                ['name' => '寅', 'time' => '03:00-05:00', 'type' => 'ping', 'yiji' => '平'],
                ['name' => '卯', 'time' => '05:00-07:00', 'type' => 'ji', 'yiji' => '宜出行 忌动土'],
                ['name' => '辰', 'time' => '07:00-09:00', 'type' => 'xiong', 'yiji' => '忌出行'],
                ['name' => '巳', 'time' => '09:00-11:00', 'type' => 'ping', 'yiji' => '平'],
                ['name' => '午', 'time' => '11:00-13:00', 'type' => 'ji', 'yiji' => '宜会友 忌词讼'],
                ['name' => '未', 'time' => '13:00-15:00', 'type' => 'xiaoJi', 'yiji' => '宜求财'],
                ['name' => '申', 'time' => '15:00-17:00', 'type' => 'ping', 'yiji' => '平'],
                ['name' => '酉', 'time' => '17:00-19:00', 'type' => 'ji', 'yiji' => '宜嫁娶'],
                ['name' => '戌', 'time' => '19:00-21:00', 'type' => 'xiong', 'yiji' => '忌安床'],
                ['name' => '亥', 'time' => '21:00-23:00', 'type' => 'ping', 'yiji' => '平'],
            ],
        ];
    }
}
