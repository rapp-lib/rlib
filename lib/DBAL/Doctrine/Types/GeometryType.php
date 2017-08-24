<?php
namespace R\Lib\DBAL\Doctrine\Types;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class GeometryType extends Type
{
    public function getName()
    {
        return 'geometry';
    }
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return 'GEOMETRY';
    }
    public function getMappedDatabaseTypes(AbstractPlatform $platform)
    {
        return array('GEOMETRY');
    }
}
