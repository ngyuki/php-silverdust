<?php
namespace ngyuki\Silverdust\Value;

use Doctrine\DBAL\Schema\Column;

class IntValue implements ValueInterface
{
    private $size;

    public function __construct($size)
    {
        $this->size = $size;
    }

    public function value(Column $column)
    {
        if ($this->size >= 64) {
            if ($column->getUnsigned()) {
                $min = 0;
                $max = PHP_INT_MAX;
            } else {
                $min = PHP_INT_MIN;
                $max = PHP_INT_MAX;
            }
        } else {
            if ($column->getUnsigned()) {
                $size = $this->size;
                $min = 0;
                $max = (1 << $size) - 1;
            } else {
                $size = $this->size - 1;
                $min = -1 << $size;
                $max = (1 << $size) - 1;
            }
        }
        $val = 0;
        while ($val === 0) {
            $val = random_int($min, $max);
        }
        return $val;
    }
}
