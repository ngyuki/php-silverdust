<?php
namespace ngyuki\Silverdust\Test;

use ngyuki\Silverdust\FixtureLoaderBuilder;

class FixtureLoaderTest extends AbstractTestCase
{
    protected function setUp()
    {
        $conn = self::conn();
        $conn->exec('SET FOREIGN_KEY_CHECKS=0');
        foreach ($conn->getSchemaManager()->listTableNames() as $table) {
            $conn->getSchemaManager()->dropTable($table);
        }
        $conn->exec('SET FOREIGN_KEY_CHECKS=1');
    }

    /**
     * @test
     */
    public function load_reset()
    {
        self::exec('
            CREATE TABLE aaa (
                a_id INT NOT NULL,
                name VARCHAR (255) NOT NULL,
                PRIMARY KEY (a_id)
            );

            CREATE TABLE bbb (
                a_id INT NOT NULL,
                b_id INT NOT NULL,
                name VARCHAR (255) NOT NULL,
                PRIMARY KEY (a_id, b_id)
            );

            CREATE TABLE ccc (
                a_id INT NOT NULL,
                b_id INT NOT NULL,
                c_id INT NOT NULL,
                name VARCHAR (255) NOT NULL,
                PRIMARY KEY (a_id, b_id, c_id)
            );

            ALTER TABLE bbb ADD FOREIGN KEY (a_id) REFERENCES aaa (a_id);
            ALTER TABLE ccc ADD FOREIGN KEY (a_id, b_id) REFERENCES bbb (a_id, b_id);
        ');

        $loader = (new FixtureLoaderBuilder($this->conn(), new Cache()))->create();
        $loader->load([
            'aaa' => [
                [ 'name' => 'ore' ],
                [ 'a_id' => 100 ],
            ],
            'bbb'  => [
                [ 'a_id' => 200 ],
                [],
                [ 'a_id' => 300, 'name' => 'are' ],
            ],
            'ccc'  => [
                [ 'a_id' => 500 ],
                [ 'b_id' => 600 ],
                [ 'name' => 'sore' ],
            ],
        ]);

        $rows = $this->all('aaa');
        assertNotEmpty($rows);
        $this->printWhenSingle($rows);

        $rows = $this->all('bbb');
        assertNotEmpty($rows);
        $this->printWhenSingle($rows);

        $rows = $this->all('ccc');
        assertNotEmpty($rows);
        $this->printWhenSingle($rows);

        ///

        $loader->reset([
            'aaa',
        ]);

        $rows = $this->all('aaa');
        assertEmpty($rows);
        $rows = $this->all('bbb');
        assertEmpty($rows);
        $rows = $this->all('ccc');
        assertEmpty($rows);
    }

    /**
     * @test
     */
    public function nullable_fkey()
    {
        self::exec('
          DROP TABLE IF EXISTS t_user;
          DROP TABLE IF EXISTS t_group;
          CREATE TABLE t_group (
            aa INT NOT NULL,
            bb INT NOT NULL,
            PRIMARY KEY (aa, bb)
          );
          CREATE TABLE t_user (
            id INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
            aa INT NOT NULL,
            bb INT NULL
          );
          ALTER TABLE t_user ADD FOREIGN KEY (aa, bb) REFERENCES t_group (aa, bb);
        ');

        $loader = (new FixtureLoaderBuilder($this->conn(), new Cache()))->create();
        $loader->load([
            't_user'  => [
                [],
            ],
        ]);

        $rows = $this->all('t_group');
        assertEmpty($rows);

        $rows = $this->all('t_user');
        assertNotEmpty($rows);
        $this->printWhenSingle($rows);
    }

    /**
     * @test
     */
    public function types_()
    {
        self::exec('
            DROP TABLE IF EXISTS t_user;
            CREATE TABLE t_user (
                v_boolean BOOLEAN NOT NULL,

                s_tinyint TINYINT NOT NULL,
                u_tinyint TINYINT UNSIGNED NOT NULL,
                s_smallint SMALLINT NOT NULL,
                u_smallint SMALLINT UNSIGNED NOT NULL,
                s_integer INTEGER NOT NULL,
                u_integer INTEGER UNSIGNED NOT NULL,
                s_bigint BIGINT NOT NULL,
                u_bigint BIGINT UNSIGNED NOT NULL,

                v_datetime DATETIME NOT NULL,
                v_timestamp TIMESTAMP NOT NULL,
                v_date DATE NOT NULL,
                v_time TIME NOT NULL,

                c_char VARCHAR(1) NOT NULL,
                v_char VARCHAR(255) NOT NULL,

                c_varchar VARCHAR(1) NOT NULL,
                v_varchar VARCHAR(255) NOT NULL,

                c_binary BINARY(1) NOT NULL,
                v_binary BINARY(255) NOT NULL,

                c_varbinary VARBINARY(1) NOT NULL,
                v_varbinary VARBINARY(255) NOT NULL,

                v_text TEXT NOT NULL,
                v_blob BLOB NOT NULL,
                
                x_decimal DECIMAL(1, 0) NOT NULL,
                v_decimal DECIMAL(6, 4) NOT NULL,
                u_decimal DECIMAL(6, 4) UNSIGNED NOT NULL,
                v_float  FLOAT NOT NULL,
                u_float  FLOAT UNSIGNED NOT NULL,

                PRIMARY KEY (u_bigint)
            )
        ');

        $loader = (new FixtureLoaderBuilder($this->conn(), new Cache()))->create();
        $loader->load([
            't_user'  => [
                [],
                [],
                [],
            ],
        ]);

        $rows = $this->all('t_user');
        assertNotEmpty($rows);
        $this->printWhenSingle($rows);
    }

    /**
     * @test
     */
    public function existing_row()
    {
        self::exec('
            DROP TABLE IF EXISTS t_user;
            DROP TABLE IF EXISTS t_group;
            CREATE TABLE t_group (
                gid INT NOT NULL PRIMARY KEY
            );
            INSERT INTO t_group VALUES (100);
            CREATE TABLE t_user (
                id INT NOT NULL PRIMARY KEY,
                gid INT NOT NULL
            );
            ALTER TABLE t_user ADD FOREIGN KEY (gid) REFERENCES t_group (gid);
        ');

        $loader = (new FixtureLoaderBuilder($this->conn(), new Cache()))->create();
        $loader->load([
            't_user'  => [
                [ 'gid' => 200 ],
            ],
        ]);

        $rows = $this->all('t_group');
        assertThat(array_column($rows, 'gid'), equalTo([100, 200]));

        $rows = $this->all('t_user');
        assertThat(array_column($rows, 'gid'), equalTo([200]));
    }

    /**
     * @test
     */
    public function multi_row()
    {
        self::exec('
            DROP TABLE IF EXISTS t_user;
            CREATE TABLE t_user (
                id INT NOT NULL PRIMARY KEY
            );
        ');

        $loader = (new FixtureLoaderBuilder($this->conn(), new Cache()))->create();
        $loader->load([
            't_user'  => [
                [],[],[]
            ],
        ]);

        $rows = $this->all('t_user');
        assertCount(3, $rows);
    }


    /**
     * @test
     */
    public function through_row()
    {
        self::exec('
            CREATE TABLE aaa (
                a_id INT NOT NULL,
                name VARCHAR (255) NOT NULL,
                PRIMARY KEY (a_id)
            );

            CREATE TABLE bbb (
                a_id INT NOT NULL,
                b_id INT NOT NULL,
                name VARCHAR (255) NOT NULL,
                PRIMARY KEY (a_id, b_id)
            );

            CREATE TABLE ccc (
                a_id INT NOT NULL,
                b_id INT NOT NULL,
                c_id INT NOT NULL,
                name VARCHAR (255) NOT NULL,
                PRIMARY KEY (a_id, b_id, c_id)
            );

            ALTER TABLE bbb ADD FOREIGN KEY (a_id) REFERENCES aaa (a_id);
            ALTER TABLE ccc ADD FOREIGN KEY (a_id, b_id) REFERENCES bbb (a_id, b_id);
        ');

        $loader = (new FixtureLoaderBuilder($this->conn(), new Cache()))->create();
        $loader->load([
            'ccc'  => [
                [
                    'bbb.a_id' => 111,
                    'bbb.aaa.name' => 'A1',
                ],
                [
                    'bbb.a_id' => 222,
                    'bbb.aaa.name' => 'A2',
                ],
                [
                    'bbb.a_id' => 111,
                    'bbb.aaa.name' => 'A1',
                ],
            ],
        ]);

        $rows = $this->all('aaa');
        assertNotEmpty($rows);
        $this->printWhenSingle($rows);

        $rows = $this->all('bbb');
        assertNotEmpty($rows);
        $this->printWhenSingle($rows);

        $rows = $this->all('ccc');
        assertNotEmpty($rows);
        $this->printWhenSingle($rows);
    }

    /**
     * @test
     */
    public function longblob()
    {
        self::exec('
            CREATE TABLE aaa (
                id INT NOT NULL,
                data LONGBLOB NOT NULL,
                PRIMARY KEY (id)
            );
        ');

        $loader = (new FixtureLoaderBuilder($this->conn(), new Cache()))->create();
        $loader->load([
            'aaa'  => [
                [],
                [
                    'data' => 'xxx',
                ],
            ],
        ]);

        $rows = $this->all('aaa');
        assertCount(2, $rows);
        $this->printWhenSingle($rows);
    }

    /**
     * @test
     */
    public function foreign_foreign_key_bug()
    {
        self::exec("
            CREATE TABLE aaa (
                a_id INT NOT NULL,
                str VARCHAR(10) NOT NULL,
                PRIMARY KEY (a_id)
            );
            CREATE TABLE bbb (
                b_id INT NOT NULL,
                a_id INT,
                str VARCHAR(10) NOT NULL,
                PRIMARY KEY (b_id),
                FOREIGN KEY (a_id) REFERENCES aaa (a_id)
            );
            CREATE TABLE ccc (
                c_id INT NOT NULL,
                a_id INT,
                b_id INT,
                PRIMARY KEY (c_id),
                FOREIGN KEY (a_id) REFERENCES aaa (a_id),
                FOREIGN KEY (b_id) REFERENCES bbb (b_id)
            );
            INSERT aaa VALUES (10, 'X');
            INSERT bbb VALUES (100, 10, 'A');
        ");

        $loader = (new FixtureLoaderBuilder($this->conn(), new Cache()))->create();
        $loader->load([
            'ccc'  => [
                [
                    'a_id' => 10,
                    'b_id' => 100,
                ],
                [
                    'a_id' => 20,
                    'b_id' => 200,
                ],
            ],
        ]);

        $rows = $this->all('aaa', ['a_id']);
        assertThat($rows, equalTo(array_replace_recursive($rows, [
            [ 'a_id' => 10, 'str' => 'X' ],
            [ 'a_id' => 20 ],
        ])));

        $rows = $this->all('bbb', ['b_id']);
        assertThat($rows, equalTo(array_replace_recursive($rows, [
            [ 'b_id' => 100, 'a_id' => 10, 'str' => 'A' ],
            [ 'b_id' => 200, 'a_id' => null ],
        ])));

        $rows = $this->all('ccc', ['a_id', 'b_id']);
        assertThat($rows, equalTo(array_replace_recursive($rows, [
            [ 'a_id' => 10, 'b_id' => 100 ],
            [ 'a_id' => 20, 'b_id' => 200 ],
        ])));
    }
}
