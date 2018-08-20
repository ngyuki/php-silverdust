<?php
namespace ngyuki\Silverdust;

class ForeignValue
{
    /**
     * @var Row
     */
    public $row;

    /**
     * @var string
     */
    private $column;

    public function __construct(Row $row, string $column)
    {
        $this->row = $row;
        $this->column = $column;
    }

    public function value()
    {
        return $this->row[$this->column];
    }
}
