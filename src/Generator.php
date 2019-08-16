<?php
namespace ngyuki\Silverdust;

use Doctrine\DBAL\Schema\Column;
use ngyuki\Silverdust\Value\ValueFactory;

class Generator
{
    /**
     * @var Query
     */
    private $query;

    /**
     * @var SchemaManager
     */
    private $schema;

    /**
     * @var array|null
     */
    private $tables;

    public function __construct(Query $query, SchemaManager $schema)
    {
        $this->query = $query;
        $this->schema = $schema;
    }

    public function generate(array $tables)
    {
        $this->tables = $this->schema->krsort($tables);

        foreach ($this->tables as $table => $rows) {
            foreach ($rows as $index => $row) {
                $this->tables[$table][$index] = $row = Row::create($table, $row);
            }
        }

        foreach ($this->tables as $rows) {
            foreach ($rows as $index => $row) {
                $this->generateRow($row, true);
            }
        }

        $this->tables = array_reverse($this->schema->krsort($this->tables), true);

        foreach ($this->tables as $table => $rows) {
            $columns = $this->schema->columns($table);
            foreach ($rows as $index => $row) {
                assert($row instanceof Row);

                if ($row->exists) {
                    continue;
                }

                $row->map(function ($v, /** @noinspection PhpUnusedParameterInspection */ $k) {
                    if ($v instanceof ForeignValue) {
                        return $v->value();
                    }
                    return $v;
                });

                foreach ($columns as $name => $column) {
                    if (!$row->has($name)) {
                        $row[$name] = $this->generateValue($column);
                    }
                }

                $this->query->overwrite($table, $row->toArray());
            }
        }
    }

    private function generateRow(Row $row, bool $entry = false): Row
    {
        if ($row->generated) {
            return $row;
        }
        $row->generated = true;

        if (!$row->through && !$entry) {
            $found = $this->query->fetch($row->table, $row->toArray());
            if ($found) {
                $row->exists = $found;
                $row->assign($found);
                return $row;
            }
        }

        $foreignKeys = $this->schema->foreignKeys($row->table);
        foreach ($foreignKeys as list($foreignTable, $references)) {
            $this->generateByForeignKey($row, $foreignTable, $references);
        }

        return $row;
    }

    private function generateByForeignKey(Row $row, string $foreignTable, array $references): Row
    {
        $through = $row->through[$foreignTable] ?? [];
        if (!$through) {
            $nullable = false;
            $columns = $this->schema->columns($row->table);
            foreach ($references as $local => $foreign) {
                if ($row->has($local)) {
                    // ローカル側の外部キー参照元の値に NULL が指定されているなら参照先の行は生成不要
                    if ($row[$local] === null) {
                        $nullable = true;
                    }
                } else {
                    // ローカル側の外部キー参照元の列が NULL 許可なら参照先の行は生成不要
                    if (!$columns[$local]->getNotnull()) {
                        $nullable = true;
                    }
                }
            }
            if ($nullable) {
                return $row;
            }
        }

        $foreignValues = [];
        foreach ($references as $local => $foreign) {
            if ($row->has($local) && is_scalar($row[$local])) {
                $foreignValues[$foreign] = $row[$local];
            }
        }
        $foreignValues += $through;

        $foreignRow = null;
        if (!$through) {
            $foreignRow = $this->findOnMemory($foreignTable, $foreignValues);
            if ($foreignRow) {
                $foreignRow = $foreignRow->assign($foreignValues);
            }
        }
        if ($foreignRow === null) {
            $foreignRow = Row::create($foreignTable, $foreignValues);
            $foreignRow = $this->generateRow($foreignRow);
            $this->tables[$foreignTable][] = $foreignRow;
        }

        foreach ($references as $local => $foreign) {
            if (!$row->has($local)) {
                $row[$local] = new ForeignValue($foreignRow, $foreign);
            }
        }
        return $row;
    }

    private function generateValue(Column $column)
    {
        if (!$column->getNotnull()) {
            return null;
        }
        $v = new ValueFactory();
        return $v->value($column);
    }

    /**
     * @param string $table
     * @param array $values
     *
     * @return Row|null
     */
    private function findOnMemory(string $table, array $values)
    {
        foreach ($this->tables[$table] ?? [] as $index => $row) {
            assert($row instanceof Row);
            if (!$row->generated) {
                continue;
            }
            $ok = true;
            foreach ($values as $name => $value) {
                if ($row->has($name) && $value != $row[$name]) {
                    $ok = false;
                    break;
                }
            }
            if ($ok) {
                return $row;
            }
        }
        return null;
    }
}
