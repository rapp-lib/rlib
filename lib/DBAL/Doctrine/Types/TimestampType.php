<?php
namespace R\Lib\DBAL\Doctrine\Types;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class TimestampType extends Type
{
    public function getName()
    {
        return 'timestamp';
    }
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return 'TIMESTAMP';
    }
    public function getMappedDatabaseTypes(AbstractPlatform $platform)
    {
        return array('TIMESTAMP');
    }
}
