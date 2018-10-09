<?php
namespace ngyuki\Silverdust\Value;

use Doctrine\DBAL\Schema\Column;

class StringValue implements ValueInterface
{
    public function value(Column $column)
    {
        $len = min($column->getLength(), 100);
        if ($len === null) {
            // Specify 100 because it is null in LONGBLOB or LONGTEXT
            // see https://github.com/doctrine/dbal/blob/master/lib/Doctrine/DBAL/Schema/MySqlSchemaManager.php#L145-L153
            $len = 100;
        }
        $str = random_bytes($len);
        return substr(base64_encode($str), 0, $len);
    }
}
