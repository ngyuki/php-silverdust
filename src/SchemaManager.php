<?php
namespace ngyuki\Silverdust;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Index;
use Psr\SimpleCache\CacheInterface;

class SchemaManager
{
    /**
     * @var Connection
     */
    private $conn;

    /**
     * @var array
     */
    private $cache1st = [];

    /**
     * @var CacheInterface
     */
    private $cache2nd;

    public function __construct(Connection $connection, CacheInterface $cache = null)
    {
        $this->conn = $connection;
        $this->cache2nd = $cache;
    }

    private function cache($name, $args, $func)
    {
        $name = $name . '.' . implode('.', $args);
        if (!array_key_exists($name, $this->cache1st)) {
            if ($this->cache2nd && $this->cache2nd->has($name)) {
                $val = $this->cache2nd->get($name);
            } else {
                /* @phan-suppress-next-line PhanParamTooManyUnpack */
                $val = $func(...$args);
                if ($this->cache2nd) {
                    $this->cache2nd->set($name, $val);
                }
            }
            $this->cache1st[$name] = $val;
        }
        return $this->cache1st[$name];
    }

    /**
     * @param string $table
     * @return Column[]
     */
    public function columns(string $table): array
    {
        return $this->cache(__FUNCTION__, func_get_args(), function ($table) {
            return $this->conn->getSchemaManager()->listTableColumns($table);
        });
    }

    /**
     * @param string $table
     * @return Index[]
     */
    public function indexes(string $table)
    {
        return $this->cache(__FUNCTION__, func_get_args(), function ($table) {
            return $this->conn->getSchemaManager()->listTableIndexes($table);
        });
    }

    /**
     * @param string $table
     * @return array [$foreignTable, [$localColumn => $foreignColumn, ...], ...]
     */
    public function foreignKeys(string $table): array
    {
        return $this->cache(__FUNCTION__, func_get_args(), function ($table) {
            $ret = [];
            $list = $this->conn->getSchemaManager()->listTableForeignKeys($table);
            foreach ($list as $fkey) {
                $referenceColumns = array_combine($fkey->getLocalColumns(), $fkey->getForeignColumns());
                $ret[] = [$fkey->getForeignTableName(), $referenceColumns];
            }
            return $ret;
        });
    }

    public function referenceTables(string $table)
    {
        return $this->referenceAllTables()[$table] ?? [];
    }

    private function referenceAllTables()
    {
        return $this->cache(__FUNCTION__, func_get_args(), function () {
            $ret = [];
            $sm = $this->conn->getSchemaManager();
            foreach ($sm->listTableNames() as $local) {
                foreach ($sm->listTableForeignKeys($local) as $fkey) {
                    $ret[$fkey->getForeignTableName()][] = $local;
                }
            }
            return $ret;
        });
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

    public function ksort(array $tables)
    {
        return array_reverse($this->krsort($tables), true);
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
