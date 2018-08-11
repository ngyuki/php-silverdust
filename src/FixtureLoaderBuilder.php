<?php
namespace ngyuki\Silverdust;

use Doctrine\DBAL\Connection;

class FixtureLoaderBuilder
{
    /**
     * @var Connection
     */
    private $conn;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    public function create(): FixtureLoader
    {
        $s = new SchemaManager($this->conn);
        $q = new Query($this->conn, $s);
        $g = new Generator($q, $s);
        $f = new FixtureLoader($q, $s, $g);
        return $f;
    }
}
