<?php
namespace ngyuki\Silverdust;

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
        $this->generator->generate($tables);
        return $this;
    }
}
