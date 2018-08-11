<?php
namespace ngyuki\Silverdust;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Index;

class SchemaManager
{
    /**
     * @var Connection
     */
    private $conn;

    private $columns = [];

    private $indexes = [];

    private $foreignKeys = [];

    private $referenceTables;

    public function __construct(Connection $connection)
    {
        $this->conn = $connection;
    }

    /**
     * @param string $table
     * @return Column[]
     */
    public function columns(string $table): array
    {
        if (!isset($this->columns[$table])) {
            $this->columns[$table] = $this->conn->getSchemaManager()->listTableColumns($table);
        }
        return $this->columns[$table];
    }

    /**
     * @param string $table
     * @return Index[]
     */
    public function indexes(string $table)
    {
        return $this->conn->getSchemaManager()->listTableIndexes($table);
    }

    /**
     * @param string $table
     * @return array [$foreignTable => [$localColumn => $foreignColumn, ...], ...]
     */
    public function foreignKeys(string $table): array
    {
        if (!isset($this->foreignKeys[$table])) {
            $this->foreignKeys[$table] = [];
            $list = $this->conn->getSchemaManager()->listTableForeignKeys($table);
            foreach ($list as $fkey) {
                $this->foreignKeys[$table][] = [
                    $fkey->getForeignTableName(),
                    array_combine($fkey->getLocalColumns(), $fkey->getForeignColumns())
                ];
            }
        }
        return $this->foreignKeys[$table];
    }

    public function referenceTables(string $table)
    {
        if ($this->referenceTables === null) {
            $this->referenceTables = [];
            $sm = $this->conn->getSchemaManager();
            foreach ($sm->listTableNames() as $local) {
                foreach ($sm->listTableForeignKeys($local) as $fkey) {
                    $this->referenceTables[$fkey->getForeignTableName()][] = $local;
                }
            }
        }
        return $this->referenceTables[$table] ?? [];
    }

    public function rsort(array $tables)
    {
        $sorted = new \ArrayObject();
        $visit = new \ArrayObject();
        foreach ($tables as $table) {
            $this->visit($sorted, $visit, $table);
        }
        return $sorted->getArrayCopy();
    }

    public function krsort(array $tables)
    {
        $ret = [];
        foreach ($this->rsort(array_keys($tables)) as $table) {
            if (array_key_exists($table, $tables)) {
                $ret[$table] = $tables[$table];
            }
        }
        return $ret;
    }

    private function visit(\ArrayObject $sorted, \ArrayObject $visit, string $table)
    {
        if (isset($visit[$table])) {
            return;
        }
        $visit[$table] = true;
        foreach ($this->referenceTables($table) as $referenceTable) {
            $this->visit($sorted, $visit, $referenceTable);
        }
        $sorted->append($table);
    }
}
