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

        foreach ($this->tables as $table => $_) {
            foreach ($this->tables[$table] as $index => $row) {
                $this->tables[$table][$index] = $this->generateRow($table, $row);
            }
        }

        $tables = $this->tables;
        $this->tables = null;

        return array_reverse($this->schema->krsort($tables), true);
    }

    private function generateRow(string $table, array $row)
    {
        $foreignKeys = $this->schema->foreignKeys($table);
        foreach ($foreignKeys as list($foreignTable, $referenceColumns)) {
            $row = $this->generateByForeignKey($table, $row, $foreignTable, $referenceColumns);
        }
        $columns = $this->schema->columns($table);
        foreach ($columns as $name => $column) {
            if (!array_key_exists($name, $row)) {
                $row[$name] = $this->generateValue($column);
            }
        }
        return $row;
    }

    private function generateByForeignKey(string $table, array $row, string $foreignTable, array $referenceColumns)
    {
        $foreignRow = [];
        foreach ($referenceColumns as $local => $foreign) {
            if (array_key_exists($local, $row)) {
                if ($row[$local] === null) {
                    return $row;
                }
                $foreignRow[$foreign] = $row[$local];
            }
        }
        $columns = $this->schema->columns($table);
        foreach ($referenceColumns as $local => $foreign) {
            if (!array_key_exists($local, $row)) {
                if (!$columns[$local]->getNotnull()) {
                    $row[$local] = null;
                    return $row;
                }
            }
        }
        $foreignRow = $this->ensureRow($foreignTable, $foreignRow);
        if ($foreignRow) {
            foreach ($referenceColumns as $local => $foreign) {
                $row[$local] = $foreignRow[$foreign];
            }
        }
        return $row;
    }

    /**
     * @param string $table
     * @param array $values
     *
     * @return array
     */
    private function ensureRow(string $table, array $values): array
    {
        $rows = [];
        foreach ($this->tables[$table] ?? [] as $index => $row) {
            $ok = true;
            foreach ($values as $name => $value) {
                if (array_key_exists($name, $row) && $value != $row[$name]) {
                    $ok = false;
                    break;
                }
            }
            if ($ok) {
                $rows[$index] = $row;
                break;
            }
        }

        if ($rows) {
            $index = key($rows);
        } elseif ($row = $this->query->fetch($table, $values)) {
            return $row;
        } else {
            $this->tables[$table][] = [];
            end($this->tables[$table]);
            $index = key($this->tables[$table]);
        }

        $this->tables[$table][$index] += $values;
        if (array_diff_key($this->schema->columns($table), $this->tables[$table][$index])) {
            $this->tables[$table][$index] = $this->generateRow($table, $this->tables[$table][$index]);
        }

        return $this->tables[$table][$index];
    }

    private function generateValue(Column $column)
    {
        if (!$column->getNotnull()) {
            return null;
        }
        $v = new ValueFactory();
        return $v->value($column);
    }
}
