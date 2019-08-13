<?php
namespace ngyuki\Silverdust;

class Row extends \ArrayObject
{
    public $generated = false;
    public $exists = null;
    public $through = [];

    public static function create($row)
    {
        if ($row instanceof Row) {
            return $row;
        }
        return (new Row())->assign($row);
    }

    public function has($name)
    {
        return $this->offsetExists($name);
    }

    public function assign($arr)
    {
        foreach ($arr as $key => $val) {
            $arr = explode('.', $key, 2);
            if (count($arr) !== 2) {
                $this[$key] = $val;
            } else {
                list($table, $column) = $arr;
                $this->through[$table][$column] = $val;
            }
        }
        return $this;
    }

    public function map($callback)
    {
        foreach ($this as $key => $val) {
            $this[$key] = $callback($val, $key);
        }
        return $this;
    }

    public function toArray()
    {
        return $this->getArrayCopy();
    }
}
