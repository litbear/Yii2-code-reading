<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\db\mysql;

use yii\db\Expression;
use yii\db\TableSchema;
use yii\db\ColumnSchema;

/**
 * Schema is the class for retrieving metadata from a MySQL database (version 4.1.x and 5.x).
 * Schema 是负责从MySQL数据库中检索元数据的类
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Schema extends \yii\db\Schema
{
    /**
     * @var array mapping from physical column types (keys) to abstract column types (values)
     * 将指定数据库(在这里就是MySQL)中的数据类型 转换为 列的抽象数据类型
     */
    public $typeMap = [
        'tinyint' => self::TYPE_SMALLINT,
        'bit' => self::TYPE_INTEGER,
        'smallint' => self::TYPE_SMALLINT,
        'mediumint' => self::TYPE_INTEGER,
        'int' => self::TYPE_INTEGER,
        'integer' => self::TYPE_INTEGER,
        'bigint' => self::TYPE_BIGINT,
        'float' => self::TYPE_FLOAT,
        'double' => self::TYPE_DOUBLE,
        'real' => self::TYPE_FLOAT,
        'decimal' => self::TYPE_DECIMAL,
        'numeric' => self::TYPE_DECIMAL,
        'tinytext' => self::TYPE_TEXT,
        'mediumtext' => self::TYPE_TEXT,
        'longtext' => self::TYPE_TEXT,
        'longblob' => self::TYPE_BINARY,
        'blob' => self::TYPE_BINARY,
        'text' => self::TYPE_TEXT,
        'varchar' => self::TYPE_STRING,
        'string' => self::TYPE_STRING,
        'char' => self::TYPE_STRING,
        'datetime' => self::TYPE_DATETIME,
        'year' => self::TYPE_DATE,
        'date' => self::TYPE_DATE,
        'time' => self::TYPE_TIME,
        'timestamp' => self::TYPE_TIMESTAMP,
        'enum' => self::TYPE_STRING,
    ];


    /**
     * Quotes a table name for use in a query.
     * A simple table name has no schema prefix.
     * @param string $name table name
     * @return string the properly quoted table name
     */
    public function quoteSimpleTableName($name)
    {
        return strpos($name, "`") !== false ? $name : "`" . $name . "`";
    }

    /**
     * Quotes a column name for use in a query.
     * A simple column name has no prefix.
     * @param string $name column name
     * @return string the properly quoted column name
     */
    public function quoteSimpleColumnName($name)
    {
        return strpos($name, '`') !== false || $name === '*' ? $name : '`' . $name . '`';
    }

    /**
     * Creates a query builder for the MySQL database.
     * @return QueryBuilder query builder instance
     */
    public function createQueryBuilder()
    {
        return new QueryBuilder($this->db);
    }

    /**
     * Loads the metadata for the specified table.
     * @param string $name table name
     * @return TableSchema driver dependent table metadata. Null if the table does not exist.
     */
    protected function loadTableSchema($name)
    {
        $table = new TableSchema;
        $this->resolveTableNames($table, $name);

        if ($this->findColumns($table)) {
            $this->findConstraints($table);

            return $table;
        } else {
            return null;
        }
    }

    /**
     * Resolves the table name and schema name (if any).
     * @param TableSchema $table the table metadata object
     * @param string $name the table name
     */
    protected function resolveTableNames($table, $name)
    {
        $parts = explode('.', str_replace('`', '', $name));
        if (isset($parts[1])) {
            $table->schemaName = $parts[0];
            $table->name = $parts[1];
            $table->fullName = $table->schemaName . '.' . $table->name;
        } else {
            $table->fullName = $table->name = $parts[0];
        }
    }

    /**
     * Loads the column information into a [[ColumnSchema]] object.
     * 加载列信息 并把每个列的信息封装到[[ColumnSchema]]对象中
     * 随便拿个建表语句做例子：
     * 
     * CREATE TABLE `wb_follow` (
     * `follow` int(10) unsigned NOT NULL COMMENT '关注用户ID',
     * `fans` int(10) unsigned NOT NULL COMMENT '粉丝用户ID',
     * `gid` int(11) NOT NULL COMMENT '所属关注分组id',
     * KEY `follow` (`follow`),
     * KEY `fans` (`fans`),
     * KEY `gid` (`gid`)
     * ENGINE=MyISAM DEFAULT CHARSET=utf8 COMMENT='关注与粉丝表';
     * 
     * @param array $info column information 接收数组
     * @return ColumnSchema the column schema object
     */
    protected function loadColumnSchema($info)
    {
        //为每个列分别建一个ColumnSchema 对象
        $column = $this->createColumnSchema();

        // 字段名
        $column->name = $info['field'];
        // 是否允许为空
        $column->allowNull = $info['null'] === 'YES';
        // 是否是主键
        $column->isPrimaryKey = strpos($info['key'], 'PRI') !== false;
        // 是否自增
        $column->autoIncrement = stripos($info['extra'], 'auto_increment') !== false;
        // 注释
        $column->comment = $info['comment'];

        // 字段类型 如int(11)
        $column->dbType = $info['type'];
        // 是否unsigned
        $column->unsigned = stripos($column->dbType, 'unsigned') !== false;

        // 准备把数据库类型，转换成对应的抽象类型，默认为 TYPE_STRING
        $column->type = self::TYPE_STRING;
        // 例：将'int(10) not null'匹配为 $matches = ['int','10']
        if (preg_match('/^(\w+)(?:\(([^\)]+)\))?/', $column->dbType, $matches)) {
            $type = strtolower($matches[1]);
            // 如果映射表里有 则直接取出实际类型
            if (isset($this->typeMap[$type])) {
                $column->type = $this->typeMap[$type];
            }
            // 假如括号里有值 则开始讨论
            if (!empty($matches[2])) {
                // 如果是枚举类型  则转换为如下: enum('jj','hh','jj')
                if ($type === 'enum') {
                    $values = explode(',', $matches[2]);
                    foreach ($values as $i => $value) {
                        // 删除每个元素左右的半角单引号
                        $values[$i] = trim($value, "'");
                    }
                    //以数组形式存入
                    $column->enumValues = $values;
                } else {
                    // 如果不是枚举类型 还剩下 int(10) 和 decimal(19,4) 两种形式了
                    $values = explode(',', $matches[2]);
                    $column->size = $column->precision = (int) $values[0];
                    if (isset($values[1])) {
                        // 处理精度
                        $column->scale = (int) $values[1];
                    }
                    // 如果是bit(1) 类型的 则转换为boolean
                    if ($column->size === 1 && $type === 'bit') {
                        $column->type = 'boolean';
                    } elseif ($type === 'bit') {
                        // 由于bit最多64位，如果超过 32 位，那么用一个 bigint 足以。
                        if ($column->size > 32) {
                            $column->type = 'bigint';
                            // 如果正好32位，那么用一个 interger 来表示。
                        } elseif ($column->size === 32) {
                            $column->type = 'integer';
                        }
                    }
                }
            }
        }

        $column->phpType = $this->getColumnPhpType($column);

        if (!$column->isPrimaryKey) {
            if ($column->type === 'timestamp' && $info['default'] === 'CURRENT_TIMESTAMP') {
                $column->defaultValue = new Expression('CURRENT_TIMESTAMP');
            } elseif (isset($type) && $type === 'bit') {
                $column->defaultValue = bindec(trim($info['default'],'b\''));
            } else {
                $column->defaultValue = $column->phpTypecast($info['default']);
            }
        }

        return $column;
    }

    /**
     * Collects the metadata of table columns.
     * @param TableSchema $table the table metadata
     * @return boolean whether the table exists in the database
     * @throws \Exception if DB query fails
     */
    protected function findColumns($table)
    {
        $sql = 'SHOW FULL COLUMNS FROM ' . $this->quoteTableName($table->fullName);
        try {
            $columns = $this->db->createCommand($sql)->queryAll();
        } catch (\Exception $e) {
            $previous = $e->getPrevious();
            if ($previous instanceof \PDOException && strpos($previous->getMessage(), 'SQLSTATE[42S02') !== false) {
                // table does not exist
                // https://dev.mysql.com/doc/refman/5.5/en/error-messages-server.html#error_er_bad_table_error
                return false;
            }
            throw $e;
        }
        foreach ($columns as $info) {
            if ($this->db->slavePdo->getAttribute(\PDO::ATTR_CASE) !== \PDO::CASE_LOWER) {
                $info = array_change_key_case($info, CASE_LOWER);
            }
            $column = $this->loadColumnSchema($info);
            $table->columns[$column->name] = $column;
            if ($column->isPrimaryKey) {
                $table->primaryKey[] = $column->name;
                if ($column->autoIncrement) {
                    $table->sequenceName = '';
                }
            }
        }

        return true;
    }

    /**
     * Gets the CREATE TABLE sql string.
     * @param TableSchema $table the table metadata
     * @return string $sql the result of 'SHOW CREATE TABLE'
     */
    protected function getCreateTableSql($table)
    {
        $row = $this->db->createCommand('SHOW CREATE TABLE ' . $this->quoteTableName($table->fullName))->queryOne();
        if (isset($row['Create Table'])) {
            $sql = $row['Create Table'];
        } else {
            $row = array_values($row);
            $sql = $row[1];
        }

        return $sql;
    }

    /**
     * Collects the foreign key column details for the given table.
     * @param TableSchema $table the table metadata
     */
    protected function findConstraints($table)
    {
        $sql = $this->getCreateTableSql($table);

        $regexp = '/FOREIGN KEY\s+\(([^\)]+)\)\s+REFERENCES\s+([^\(^\s]+)\s*\(([^\)]+)\)/mi';
        if (preg_match_all($regexp, $sql, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $fks = array_map('trim', explode(',', str_replace('`', '', $match[1])));
                $pks = array_map('trim', explode(',', str_replace('`', '', $match[3])));
                $constraint = [str_replace('`', '', $match[2])];
                foreach ($fks as $k => $name) {
                    $constraint[$name] = $pks[$k];
                }
                $table->foreignKeys[] = $constraint;
            }
        }
    }

    /**
     * Returns all unique indexes for the given table.
     * Each array element is of the following structure:
     *
     * ~~~
     * [
     *  'IndexName1' => ['col1' [, ...]],
     *  'IndexName2' => ['col2' [, ...]],
     * ]
     * ~~~
     *
     * @param TableSchema $table the table metadata
     * @return array all unique indexes for the given table.
     */
    public function findUniqueIndexes($table)
    {
        $sql = $this->getCreateTableSql($table);
        $uniqueIndexes = [];

        $regexp = '/UNIQUE KEY\s+([^\(\s]+)\s*\(([^\(\)]+)\)/mi';
        if (preg_match_all($regexp, $sql, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $indexName = str_replace('`', '', $match[1]);
                $indexColumns = array_map('trim', explode(',', str_replace('`', '', $match[2])));
                $uniqueIndexes[$indexName] = $indexColumns;
            }
        }

        return $uniqueIndexes;
    }

    /**
     * Returns all table names in the database.
     * @param string $schema the schema of the tables. Defaults to empty string, meaning the current or default schema.
     * @return array all table names in the database. The names have NO schema name prefix.
     */
    protected function findTableNames($schema = '')
    {
        $sql = 'SHOW TABLES';
        if ($schema !== '') {
            $sql .= ' FROM ' . $this->quoteSimpleTableName($schema);
        }

        return $this->db->createCommand($sql)->queryColumn();
    }
}
