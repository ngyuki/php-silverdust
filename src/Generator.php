<?php
namespace ngyuki\Silverdust;

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
                $this->tables[$table][$index] = $row = Row::create($row);
                $row->entity = true;
            }
        }

        foreach ($this->tables as $table => $rows) {
            foreach ($rows as $index => $row) {
                $this->generateRow($table, $row);
            }
        }

        $tables = $this->tables;
        $this->tables = null;

        return array_reverse($this->schema->krsort($tables), true);
    }

    private function generateRow(string $table, Row $row): Row
    {
        if ($row->generated) {
            return $row;
        }
        $row->generated = true;

        $foreignKeys = $this->schema->foreignKeys($table);

        $foreignTables = [];
        foreach ($foreignKeys as list($foreignTable)) {
            $foreignTables[$foreignTable] = $foreignTable;
        }

        $through = [];
        $row->filter(function ($val, $key) use (&$through) {
            $arr = explode('.', $key, 2);
            if (count($arr) !== 2) {
                return true;
            }
            list($throughTable, $throughColumn) = $arr;
            $through[$throughTable][$throughColumn] = $val;
            return false;
        });

        if ($foreignKeys && !$through && !$row->entity) {
            $found = $this->query->fetch($table, $row->toArray());
            if ($found) {
                $row->exists = $found;
                return $row;
            }
        }
        foreach ($foreignKeys as list($foreignTable, $references)) {
            $this->generateByForeignKey($table, $row, $foreignTable, $references, $through[$foreignTable] ?? []);
        }
        return $row;
    }

    private function generateByForeignKey(string $table, Row $row, string $foreignTable, array $references, array $through): Row
    {
        if (!$through) {
            $nullable = false;
            $columns = $this->schema->columns($table);
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

        $foreignRow = [];
        foreach ($references as $local => $foreign) {
            if ($row->has($local) && is_scalar($row[$local])) {
                $foreignRow[$foreign] = $row[$local];
            }
        }
        $foreignRow += $through;

        if ($through) {
            $this->tables[$foreignTable][] = $foreignRow = $this->generateRow($foreignTable, Row::create($foreignRow));
        } else {
            $foreignRow = $this->ensureRow($foreignTable, $foreignRow);
        }

        foreach ($references as $local => $foreign) {
            if (!$row->has($local)) {
                $row[$local] = new ForeignValue($foreignRow, $foreign);
            }
        }
        return $row;
    }

    /**
     * @param string $table
     * @param array $values
     *
     * @return Row
     */
    private function ensureRow(string $table, array $values): Row
    {
        foreach ($this->tables[$table] ?? [] as $index => $row) {
            $ok = true;
            assert($row instanceof Row);
            foreach ($values as $name => $value) {
                if ($row->has($name) && $value != $row[$name]) {
                    $ok = false;
                    break;
                }
            }
            if ($ok) {
                return $row->assign($values);
            }
        }

        return $this->tables[$table][] = $this->generateRow($table, Row::create($values));
    }
}
