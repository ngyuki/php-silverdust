<?php
namespace ngyuki\Silverdust;

class Row extends \ArrayObject
{
    public $table;
    public $generated = false;
    public $exists = null;
    public $through = [];

    public static function create($table, $row)
    {
        if ($row instanceof Row) {
            return $row;
        }
        $row = (new Row())->assign($row);
        $row->table = $table;
        return $row;
    }

    public function has($name)
    {
        return $this->offsetExists($name);
    }

    public function assign($arr)
    {
        foreach ($arr as $key => $val) {
            $arr = explode('.', $key, 2);
            if (count($arr) === 2) {
                $key = $arr[0];
                $val = [$arr[1] => $val];
            }
            if (is_array($val)) {
                $this->through[$key] = array_merge($this->through[$key] ?? [], $val);
            } else {
                $this[$key] = $val;
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
