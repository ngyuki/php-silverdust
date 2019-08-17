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
    public $column;

    /**
     * @param Row $row
     * @param string $column
     */
    public function __construct(Row $row, string $column)
    {
        $this->row = $row;
        $this->column = $column;
    }
}
