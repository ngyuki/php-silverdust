<?php
namespace ngyuki\Silverdust\Value;

use Doctrine\DBAL\Schema\Column;

class FloatValue implements ValueInterface
{
    public function value(Column $column)
    {
        $precision = $column->getPrecision();
        $scale = $column->getScale();
        $val = (string)random_int(0, (int)pow(10, $precision) - 1);
        $decimal = substr($val, 0, $scale);
        $integer = substr($val, $scale);
        $sign = '';
        if (!$column->getUnsigned() & random_int(0, 1)) {
            $sign = '-';
        }
        $val = sprintf("%s%d.%{$scale}d", $sign, $integer, $decimal);
        return $val;
    }
}
