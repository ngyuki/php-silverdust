<?php
namespace ngyuki\Silverdust\Value;

use Doctrine\DBAL\Schema\Column;

class StringValue implements ValueInterface
{
    public function value(Column $column)
    {
        $len = min($column->getLength(), 100);
        $str = random_bytes($len);
        return substr(base64_encode($str), 0, $len);
    }
}
