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
     * @var Row[][]|null
     */
    private $tables = [];

    public function __construct(Query $query, SchemaManager $schema)
    {
        $this->query = $query;
        $this->schema = $schema;
    }

    public function generate(array $tables)
    {
        $this->tables = [];

        $tables = $this->schema->ksort($tables);
        foreach ($tables as $table => $rows) {
            foreach ($rows as $row) {
                $row = $this->prepareRow($table, $row);
                $row->entry = true;
                $this->tables[$table][] = $row;
            }
        }

        $this->tables = $this->schema->krsort($this->tables);
        foreach ($this->tables as $rows) {
            foreach ($rows as $row) {
                $this->applyForeign($row);
            }
        }

        $this->tables = $this->schema->ksort($this->tables);
        foreach ($this->tables as $rows) {
            foreach ($rows as $row) {
                $this->generateRow($row);
            }
        }
    }

    public function prepareRow(string $table, array $arr): Row
    {
        $row = new Row($table);
        $throughTables = [];

        foreach ($arr as $key => $val) {
            $arr = explode('.', $key, 2);
            if (count($arr) === 2) {
                $key = $arr[0];
                $val = [$arr[1] => $val];
            }
            if (is_array($val)) {
                $throughTables[$key] = array_merge($throughTables[$key] ?? [], $val);
            } else {
                $row[$key] = $val;
            }
        }

        $foreignTables = [];
        foreach ($this->schema->foreignKeys($table) as list($foreignTable, $references)) {
            $foreignTables[$foreignTable][] = $references;
        }

        foreach ($throughTables as $throughTable => $throughValues) {

            if (!array_key_exists($throughTable, $foreignTables)) {
                throw new \RuntimeException("missing foreign reference $table -> $throughTable");
            }

            foreach ($foreignTables[$throughTable] as $references) {
                foreach ($references as $local => $foreign) {
                    if (array_key_exists($foreign, $throughValues) && !$row->has($local)) {
                        $row[$local] = $throughValues[$foreign];
                    } elseif (!array_key_exists($foreign, $throughValues) && $row->has($local)) {
                        $throughValues[$foreign] = $row[$local];
                    }
                }
            }

            $throughRow = $this->findOnMemory($throughTable, $throughValues);
            if (!$throughRow) {
                $throughRow = $this->prepareRow($throughTable, $throughValues);
                $this->tables[$throughTable][] = $throughRow;
            }

            foreach ($foreignTables[$throughTable] as $references) {
                foreach ($references as $local => $foreign) {
                    if (!$row->has($local)) {
                        $row[$local] = new ForeignValue($throughRow, $foreign);
                    }
                }
            }
        }
        return $row;
    }

    public function applyForeign(Row $row)
    {
        $columns = $this->schema->columns($row->table);
        $foreignKeys = $this->schema->foreignKeys($row->table);
        foreach ($foreignKeys as list($foreignTable, $references)) {

            $nullable = false;
            foreach ($references as $local => $foreign) {
                if ($row->has($local) && ($row[$local] === null)) {
                    $nullable = true;
                } elseif (!$row->has($local) && !$columns[$local]->getNotnull()) {
                    $nullable = true;
                }
            }
            if ($nullable) {
                continue;
            }

            $foreignValues = [];
            foreach ($references as $local => $foreign) {
                if ($row->has($local)) {
                    $foreignValues[$foreign] = $row[$local];
                }
            }

            $foreignRow = $this->findOnMemory($foreignTable, $foreignValues);
            if ($foreignRow) {
                foreach ($references as $local => $foreign) {
                    if ($row->has($local) && !$foreignRow->has($foreign)) {
                        $value = $row[$local];
                        if (is_scalar($value)) {
                            $foreignRow[$foreign] = $value;
                        }
                    } elseif (!$row->has($local) && $foreignRow->has($foreign)) {
                        $value = $foreignRow[$foreign];
                        if (is_scalar($value)) {
                            $row[$local] = $value;
                        } else {
                            $row[$local] = new ForeignValue($foreignRow, $foreign);
                        }
                    }
                }
            }
        }
    }

    private function generateRow(Row $row)
    {
        foreach ($row as $column => $value) {
            if ($value instanceof ForeignValue) {
                $this->generateRow($value->row);
                $row[$column] = $value->row[$value->column];
            }
        }

        if (!$row->entry) {
            $found = $this->query->fetch($row->table, $row->toArray());
            if ($found) {
                $row->assign($found);
                $row->exists = true;
                return;
            }
        }

        $foreignKeys = $this->schema->foreignKeys($row->table);
        foreach ($foreignKeys as list($foreignTable, $references)) {
            $this->generateByForeignKey($row, $foreignTable, $references);
        }

        $columns = $this->schema->columns($row->table);
        foreach ($columns as $name => $column) {
            if (!$row->has($name)) {
                $row[$name] = $this->generateValue($column);
            }
        }
        $this->query->overwrite($row->table, $row->toArray());
    }

    private function generateByForeignKey(Row $row, $foreignTable, $references)
    {
        $nullable = false;
        $columns = $this->schema->columns($row->table);
        foreach ($references as $local => $foreign) {
            if ($row->has($local) && ($row[$local] === null)) {
                $nullable = true;
            } elseif (!$row->has($local) && !$columns[$local]->getNotnull()) {
                $nullable = true;
                $row[$local] = null;
            }
        }
        if ($nullable) {
            return;
        }

        $foreignValues = [];
        foreach ($references as $local => $foreign) {
            if ($row->has($local)) {
                $foreignValues[$foreign] = $row[$local];
            }
        }

        $foreignRow = $this->findOnMemory($foreignTable, $foreignValues);
        if ($foreignRow === null) {
            $foreignRow = new Row($foreignTable, $foreignValues);
            $this->tables[$foreignTable][] = $foreignRow;
        }
        if (!$foreignRow->exists) {
            $foreignRow->assign($foreignValues);
            $this->generateRow($foreignRow);;
        }

        foreach ($references as $local => $foreign) {
            if (!$row->has($local)) {
                $row[$local] = $foreignRow[$foreign];
            }
        }
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
            $ok = true;
            foreach ($values as $name => $value) {
                if (!$row->has($name)) {
                    continue;
                }
                if (is_scalar($row[$name]) && is_scalar($value) && $value == $row[$name]) {
                    continue;
                }
                $ok = false;
                break;
            }
            if ($ok) {
                return $row;
            }
        }
        return null;
    }
}
