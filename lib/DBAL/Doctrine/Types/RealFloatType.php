<?php
namespace R\Lib\DBAL\Doctrine\Types;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Platforms\AbstractPlatform;

class RealFloatType extends Type
{
    public function getName()
    {
        return 'real_float';
    }
    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return 'FLOAT';
    }
    public function getMappedDatabaseTypes(AbstractPlatform $platform)
    {
        return array('FLOAT');
    }
}
