<?php
namespace ngyuki\Silverdust\Value;

use Doctrine\DBAL\Schema\Column;

interface ValueInterface
{
    public function value(Column $column);
}
