<?php
namespace ngyuki\Silverdust;

class Row extends \ArrayObject
{
    public $entity = false;

    public static function create($row)
    {
        if ($row instanceof Row) {
            return $row;
        }
        return new Row($row);
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
