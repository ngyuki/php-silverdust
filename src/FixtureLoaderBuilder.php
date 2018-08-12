<?php
namespace ngyuki\Silverdust;

use Doctrine\DBAL\Connection;
use Psr\SimpleCache\CacheInterface;

class FixtureLoaderBuilder
{
    /**
     * @var Connection
     */
    private $conn;

    /**
     * @var CacheInterface|null
     */
    private $cache;

    public function __construct(Connection $conn, CacheInterface $cache = null)
    {
        $this->conn = $conn;
        $this->cache = $cache;
    }

    public function create(): FixtureLoader
    {
        $s = new SchemaManager($this->conn, $this->cache);
        $q = new Query($this->conn, $s);
        $g = new Generator($q, $s);
        $f = new FixtureLoader($q, $s, $g);
        return $f;
    }
}
