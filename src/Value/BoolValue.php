<?php
namespace ngyuki\Silverdust\Value;

use Doctrine\DBAL\Schema\Column;

class BoolValue implements ValueInterface
{
    public function value(Column $column)
    {
        return random_int(0, 1);
    }
}
