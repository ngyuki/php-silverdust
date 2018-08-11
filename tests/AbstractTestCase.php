<?php
namespace ngyuki\Silverdust\Test;

use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;

abstract class AbstractTestCase extends TestCase
{
    protected static $connection;

    private static function env($key, $default)
    {
        $val = getenv($key);
        if ($val === false) {
            return $default;
        }
        return $val;
    }

    protected function single()
    {
        return $this->getTestResultObject()->topTestSuite()->count(true) === 1;
    }

    protected function printWhenSingle($vars)
    {
        if ($this->single()) {
            print_r($vars);
        }
    }

    protected static function conn()
    {
        if (!self::$connection) {
            self::$connection = DriverManager::getConnection([
                'driver'   => 'pdo_mysql',
                'host'     => self::env('MYSQL_HOST', '127.0.0.1'),
                'port'     => self::env('MYSQL_PORT', '3306'),
                'dbname'   => self::env('MYSQL_DATABASE', 'test'),
                'user'     => self::env('MYSQL_USER', 'root'),
                'password' => self::env('MYSQL_PASSWORD', ''),
            ]);
        }
        return self::$connection;
    }

    protected static function exec($sql)
    {
        $arr = explode(';', $sql);
        foreach ($arr as $sql) {
            $sql = trim($sql);
            if (strlen($sql)) {
                self::conn()->exec($sql);
            }
        }
    }

    protected static function all($table)
    {
        return ($conn = self::conn())->createQueryBuilder()
            ->select('*')
            ->from($conn->quoteIdentifier($table))
            ->execute()
            ->fetchAll(\PDO::FETCH_ASSOC);
    }
}
