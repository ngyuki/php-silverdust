<?php
namespace ngyuki\Silverdust\Test;

use ngyuki\Silverdust\FixtureLoaderBuilder;

class FixtureLoaderTest extends AbstractTestCase
{
    /**
     * @test
     */
    public function load_reset()
    {
        self::exec('
            DROP TABLE IF EXISTS ccc;
            DROP TABLE IF EXISTS bbb;
            DROP TABLE IF EXISTS aaa;

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
}
