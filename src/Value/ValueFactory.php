<?php
namespace ngyuki\Silverdust\Value;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\Type;

class ValueFactory
{
    const GENERATOR_MAPPING = [
        //Type::TARRAY => 'array',
        //Type::SIMPLE_ARRAY => 'simple_array',
        //Type::JSON_ARRAY => 'json_array',
        Type::BIGINT => [IntValue::class, 64],
        Type::BOOLEAN => [BoolValue::class],
        Type::DATETIME => [DateTimeValue::class, 'Y-m-d H:i:s'],
        Type::DATETIMETZ => [DateTimeValue::class, 'Y-m-d H:i:s'],
        Type::DATE => [DateTimeValue::class, 'Y-m-d'],
        Type::TIME => [DateTimeValue::class, 'H:i:s'],
        Type::DECIMAL => [FloatValue::class],
        Type::INTEGER => [IntValue::class, 32],
        //Type::OBJECT => 'object',
        Type::SMALLINT => [IntValue::class, 16],
        Type::STRING => [StringValue::class],
        Type::TEXT => [StringValue::class],
        Type::BINARY => [StringValue::class],
        Type::BLOB => [StringValue::class],
        Type::FLOAT => [FloatValue::class],
        //Type::GUID => 'guid',
    ];

    public function value(Column $column)
    {
        $name = $column->getType()->getName();

        if (!isset(self::GENERATOR_MAPPING[$name])) {
            throw new \DomainException("Unsupported type: $name");
        }

        $args = self::GENERATOR_MAPPING[$name];
        $class = array_shift($args);
        assert(is_subclass_of($class, ValueInterface::class));

        $obj = new $class(...$args);
        assert($obj instanceof ValueInterface);
        return $obj->value($column);
    }
}
