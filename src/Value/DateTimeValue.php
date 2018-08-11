<?php
namespace ngyuki\Silverdust\Value;

use Doctrine\DBAL\Schema\Column;

class DateTimeValue implements ValueInterface
{
    /**
     * @var string
     */
    private $fmt;

    public function __construct($fmt)
    {
        $this->fmt = $fmt;
    }

    public function value(Column $column)
    {
        $time = random_int(time() - 60*60*24*365*10, time() + 60*60*24*365*10);
        $date = (new \DateTime())->setTimestamp($time);
        return $date->format($this->fmt);
    }
}
