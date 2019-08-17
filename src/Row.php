<?php
namespace ngyuki\Silverdust;

class Row extends \ArrayObject
{
    public $table;
    public $entry = false;
    public $exists = null;

    public function __construct($table, $input = array())
    {
        parent::__construct($input);
        $this->table = $table;
    }

    public function has($name)
    {
        return $this->offsetExists($name);
    }

    public function assign($arr)
    {
        foreach ($arr as $key => $val) {
            $this[$key] = $val;
        }
        return $this;
    }

    public function toArray()
    {
        return $this->getArrayCopy();
    }
}
