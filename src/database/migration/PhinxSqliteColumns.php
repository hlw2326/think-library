<?php

declare(strict_types=1);
/**
 * +----------------------------------------------------------------------
 * | HlwAdmin Plugin for HlwAdmin
 * +----------------------------------------------------------------------
 * | 版权所有 2014~2026 HlwAdmin [ hlwadmin.top ]
 * +----------------------------------------------------------------------
 * | 官方网站: https://hlwadmin.top
 * +----------------------------------------------------------------------
 */

namespace think\admin\database\migration;

use Phinx\Db\Adapter\SQLiteAdapter;
use Phinx\Db\Table\Table;

/**
 * Phinx 3.0 的 SQLite 列定义兼容层。
 * @internal
 */
class PhinxSqliteColumns extends SQLiteAdapter
{
    use PhinxLegacyColumns;

    public function createTable(Table $table, array $columns = [], array $indexes = []): void
    {
        $options = $table->getOptions();
        foreach ($columns as $column) {
            if ($column->isIdentity() && (array)($options['primary_key'] ?? []) === [$column->getName()]) {
                unset($options['primary_key']);
            }
        }
        $copy = new Table($table->getName(), $options);
        parent::createTable($copy, $columns, $indexes);
    }

    /**
     * 旧版 changeColumn 使用正则拆 SQL，会截断小数类型和默认值，并丢弃索引及触发器。
     * 从完整元数据一次重建，先复制成功再替换原表，失败时回滚。
     */
    public function replaceFields(string $name, array $fields): void
    {
        $source = new class($this) {
            private $adapter;

            public function __construct($adapter)
            {
                $this->adapter = $adapter;
            }

            public function getConfig($key)
            {
                return $key === 'type' ? 'sqlite' : '';
            }

            public function query($sql)
            {
                return $this->adapter->fetchAll($sql);
            }
        };
        [$options, $current, $indexes] = PhinxSchema::read($source, $name);
        $merged = array_column($current, null, 0);
        foreach ($fields as $field) {
            $merged[$field[0]] = $field;
        }
        $adapter = new self($this, array_values($merged));
        $temporary = 'phinx_rebuild_' . bin2hex(random_bytes(8));
        $table = new Table($temporary, $options, $adapter);
        foreach (PhinxSchema::prepareFields($adapter, array_values($merged)) as $field) {
            $table->addColumn($field[0], $field[1], $field[2]);
        }
        $quotedName = $this->connection->quote($name);
        $triggers = $this->fetchAll("SELECT sql FROM sqlite_master WHERE type = 'trigger' AND tbl_name = " . $quotedName);
        $columns = array_column($current, 0);
        // 保留没有显式 INTEGER 主键时的 rowid。
        foreach (['_rowid_', 'rowid', 'oid'] as $rowid) {
            if (!in_array($rowid, array_map('strtolower', array_keys($merged)), true)) {
                $columns[] = $rowid;
                break;
            }
        }
        $columns = implode(', ', array_map([PhinxSchema::class, 'quote'], $columns));
        $this->execute('SAVEPOINT phinx_legacy_rebuild');
        try {
            $table->create();
            $this->execute('INSERT INTO ' . PhinxSchema::quote($temporary) . ' (' . $columns . ') SELECT ' . $columns . ' FROM ' . PhinxSchema::quote($name));
            $this->execute('DROP TABLE ' . PhinxSchema::quote($name));
            $this->execute('ALTER TABLE ' . PhinxSchema::quote($temporary) . ' RENAME TO ' . PhinxSchema::quote($name));
            PhinxExtend::upgrade(new Table($name, [], $adapter), [], $indexes, true);
            foreach ($triggers as $trigger) {
                $this->execute($trigger['sql']);
            }
            $this->execute('RELEASE SAVEPOINT phinx_legacy_rebuild');
        } catch (\Throwable $exception) {
            $this->execute('ROLLBACK TO SAVEPOINT phinx_legacy_rebuild');
            $this->execute('RELEASE SAVEPOINT phinx_legacy_rebuild');
            throw $exception;
        }
    }
}
