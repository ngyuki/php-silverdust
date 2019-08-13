<?php
namespace ngyuki\Silverdust;

use Doctrine\DBAL\Schema\Column;
use ngyuki\Silverdust\Value\ValueFactory;

class FixtureLoader
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
     * @var Generator
     */
    private $generator;

    public function __construct(Query $query, SchemaManager $schema, Generator $generator)
    {
        $this->query = $query;
        $this->schema = $schema;
        $this->generator = $generator;
    }

    public function reset(array $tables): self
    {
        foreach ($this->schema->rsort($tables) as $table) {
            $this->query->delete($table);
        }
        return $this;
    }

    public function load(array $tables): self
    {
        $tables = $this->generator->generate($tables);

        foreach ($tables as $table => $rows) {
            $columns = $this->schema->columns($table);
            foreach ($rows as $index => $row) {
                $row = Row::create($row);
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

                if (!$row->entity) {
                    $found = $this->query->fetch($table, $row->toArray());
                    if ($found) {
                        $row->assign($found);
                        continue;
                    }
                }

                foreach ($columns as $name => $column) {
                    if (!$row->has($name)) {
                        $row[$name] = $this->generateValue($column);
                    }
                }

                $this->query->overwrite($table, $row->toArray());
            }
        }

        return $this;
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
