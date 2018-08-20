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

        foreach ($this->tables as $table => $_) {
            foreach ($this->tables[$table] as $index => $row) {
                $this->tables[$table][$index] = $row = Row::create($row);
                $row->entity = true;
                $this->generateRow($table, $row);
            }
        }

        $tables = $this->tables;
        $this->tables = null;

        return array_reverse($this->schema->krsort($tables), true);
    }

    private function generateRow(string $table, Row $row)
    {
        $foreignKeys = $this->schema->foreignKeys($table);
        foreach ($foreignKeys as list($foreignTable, $references)) {
            $this->generateByForeignKey($table, $row, $foreignTable, $references);
        }
    }

    private function generateByForeignKey(string $table, Row $row, string $foreignTable, array $references): Row
    {
        $nullable = false;
        $columns = $this->schema->columns($table);
        foreach ($references as $local => $foreign) {
            if ($row->has($local)) {
                if ($row[$local] === null) {
                    $nullable = true;
                }
            } else {
                if (!$columns[$local]->getNotnull()) {
                    $row[$local] = null;
                    $nullable = true;
                }
            }
        }
        if ($nullable) {
            return $row;
        }

        $foreignRow = [];
        foreach ($references as $local => $foreign) {
            if ($row->has($local) && is_scalar($row[$local])) {
                $foreignRow[$foreign] = $row[$local];
            }
        }

        $foreignRow = $this->ensureRow($foreignTable, $foreignRow);
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
            $row = Row::create($row);
            foreach ($values as $name => $value) {
                if ($row->has($name) && $value != $row[$name]) {
                    $ok = false;
                    break;
                }
            }
            if ($ok) {
                return $this->tables[$table][$index] = $row->assign($values);
            }
        }

        return $this->tables[$table][] = Row::create($values);
    }
}
