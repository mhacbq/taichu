<?php
declare(strict_types=1);

namespace app\service;

use think\facade\Db;

/**
 * 数据库结构探测器。
 *
 * 统一通过 ThinkORM 的 schema 能力探测表和字段，避免业务代码直接写死 MySQL SHOW 语句。
 */
class SchemaInspector
{
    protected static array $tableExistsCache = [];
    protected static array $tableColumnsCache = [];

    public static function tableExists(string $table): bool
    {
        if ($table === '') {
            return false;
        }

        if (array_key_exists($table, self::$tableExistsCache)) {
            return self::$tableExistsCache[$table];
        }

        $columns = self::loadTableColumns($table);
        $exists = !empty($columns);

        self::$tableExistsCache[$table] = $exists;
        if ($exists) {
            self::$tableColumnsCache[$table] = $columns;
        }

        return $exists;
    }

    public static function getTableColumns(string $table): array
    {
        if ($table === '') {
            return [];
        }

        if (isset(self::$tableColumnsCache[$table])) {
            return self::$tableColumnsCache[$table];
        }

        $columns = self::loadTableColumns($table);
        self::$tableColumnsCache[$table] = $columns;
        self::$tableExistsCache[$table] = !empty($columns);

        return $columns;
    }

    protected static function loadTableColumns(string $table): array
    {
        try {
            $fields = Db::connect()->getTableFields($table);
        } catch (\Throwable $e) {
            return [];
        }

        $columns = [];
        foreach ((array) $fields as $key => $value) {
            if (is_int($key) && is_string($value) && $value !== '') {
                $columns[$value] = true;
                continue;
            }

            if (is_string($key) && $key !== '') {
                $columns[$key] = true;
            }
        }

        return $columns;
    }
}
