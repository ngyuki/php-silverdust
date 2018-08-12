<?php
namespace ngyuki\Silverdust\Test;

use ngyuki\Silverdust\Query;
use ngyuki\Silverdust\SchemaManager;

class QueryTest extends AbstractTestCase
{
    public static function tearDownAfterClass()
    {
        self::exec('DROP TABLE IF EXISTS qqq');
    }

    /**
     * @test
     */
    public function override()
    {
        $conn = self::conn();
        $query = new Query(self::conn(), new SchemaManager($conn));

        self::exec('
            DROP TABLE IF EXISTS qqq;
            CREATE TABLE qqq (
                id INT NOT NULL,
                no INT NOT NULL,
                name VARCHAR (255) NOT NULL,
                PRIMARY KEY (id, no)
            );
        ');

        $query->overwrite('qqq', [ 'id' => 1, 'no' => 2, 'name' => 'aaa' ]);
        $query->overwrite('qqq', [ 'id' => 1, 'no' => 3, 'name' => 'bbb' ]);
        $query->overwrite('qqq', [ 'id' => 1, 'no' => 2, 'name' => 'ccc' ]);
        $query->overwrite('qqq', [ 'id' => 1, 'no' => 4, 'name' => 'ddd' ]);

        $rows = $this->all('qqq');
        assertThat($rows, equalTo([
            [ 'id' => 1, 'no' => 2, 'name' => 'ccc' ],
            [ 'id' => 1, 'no' => 3, 'name' => 'bbb' ],
            [ 'id' => 1, 'no' => 4, 'name' => 'ddd' ],
        ]));
    }
}
