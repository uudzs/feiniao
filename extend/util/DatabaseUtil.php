<?php

declare(strict_types=1);

namespace util;

use think\facade\Db;
use think\db\exception\DbException;

class DatabaseUtil
{
    /**
     * 检测并转换字段为 LONGBLOB 类型
     *
     * @param string $table 表名
     * @param string $column 字段名
     * @return bool 是否执行了转换操作
     */
    public static function ensureLongBlobColumn($table, $column)
    {
        $prefix = Db::connect('mysql')->getConfig('prefix');
        $table = $prefix . $table;
        // 获取字段信息
        $columnInfo = self::getColumnInfo($table, $column);
        if (!$columnInfo) {
            throw new \RuntimeException("字段 {$column} 在表 {$table} 中不存在");
        }
        // 检查是否为 LONGBLOB
        if (self::isLongBlob($columnInfo)) {
            return false; // 已经是 LONGBLOB，无需转换
        }
        // 执行转换
        self::convertToLongBlob($table, $column, $columnInfo);
        return true;
    }

    /**
     * 获取字段信息
     */
    protected static function getColumnInfo($table, $column)
    {
        try {
            // 获取表结构
            $schema = Db::query("SHOW FULL COLUMNS FROM `{$table}`");
            foreach ($schema as $field) {
                if ($field['Field'] === $column) {
                    return $field;
                }
            }
            return null;
        } catch (DbException $e) {
            throw new \RuntimeException("获取表结构失败: " . $e->getMessage());
        }
    }

    /**
     * 检查是否为 LONGBLOB 类型
     */
    protected static function isLongBlob($columnInfo)
    {
        $type = strtoupper($columnInfo['Type']);
        return preg_match('/^LONGBLOB($|\()/i', $type);
    }

    /**
     * 转换为 LONGBLOB 类型
     */
    protected static function convertToLongBlob($table, $column, $columnInfo)
    {
        try {
            $sql = sprintf(
                "ALTER TABLE `%s` MODIFY COLUMN `%s` LONGBLOB %s %s COMMENT '%s'",
                $table,
                $column,
                ($columnInfo['Null'] === 'YES') ? 'NULL' : 'NOT NULL',
                $columnInfo['Default'] ? "DEFAULT '{$columnInfo['Default']}'" : '',
                addslashes($columnInfo['Comment'])
            );
            // 执行转换
            Db::execute($sql);
        } catch (DbException $e) {
            throw new \RuntimeException("字段转换失败: " . $e->getMessage());
        }
    }
}
