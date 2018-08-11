<?php
namespace ngyuki\Silverdust;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class Query
{
    /**
     * @var Connection
     */
    private $conn;

    /**
     * @var SchemaManager
     */
    private $schema;

    public function __construct(Connection $conn, SchemaManager $schema)
    {
        $this->conn = $conn;
        $this->schema = $schema;
    }

    /**
     * @param string $table
     * @param array $values
     * @return int
     */
    public function overwrite(string $table, array $values)
    {
        try {
            return $this->insert($table, $values);
        } catch (UniqueConstraintViolationException $ex) {
            return $this->updateByUnique($table, $values);
        }
    }

    /**
     * @param string $table
     * @param array $values
     * @return int
     */
    private function insert(string $table, array $values)
    {
        $data = [];
        foreach ($values as $column => $value) {
            $data[$this->conn->quoteIdentifier($column)] = $value;
        }
        return $this->conn->insert($this->conn->quoteIdentifier($table), $data);
    }

    private function updateByUnique(string $table, array $values)
    {
        $indexes = $this->schema->indexes($table);

        foreach ($indexes as $index) {
            if (!$index->isPrimary() && !$index->isUnique()) {
                continue;
            }

            $ok = true;
            $columns = $index->getColumns();

            foreach ($columns as $name) {
                if (!isset($values[$name])) {
                    $ok = false;
                    break;
                }
            }

            if (!$ok) {
                continue;
            }

            $columns = array_flip($columns);
            $identifier = [];
            $data = [];
            foreach ($values as $column => $value) {
                if (isset($columns[$column])) {
                    $identifier[$this->conn->quoteIdentifier($column)] = $value;
                }
                $data[$this->conn->quoteIdentifier($column)] = $value;
            }

            return $this->conn->update($table, $data, $identifier);
        }

        throw new \RuntimeException("Unable overwrite \"$table\"");
    }

    /**
     * @param string $table
     */
    public function delete(string $table)
    {
        $this->conn->createQueryBuilder()->delete($this->conn->quoteIdentifier($table))->execute();
    }

    /**
     * @param string $table
     * @param array $values
     *
     * @return array|null
     */
    public function fetch(string $table, array $values)
    {
        $q = $this->conn->createQueryBuilder()->select('*')->from($this->conn->quoteIdentifier($table));
        foreach ($values as $name => $value) {
            $q->where($this->conn->quoteIdentifier($name), $this->conn->quote($value));
        }
        return $q->setMaxResults(1)->execute()->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
}
