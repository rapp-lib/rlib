<?php
namespace R\Lib\Table\Feature;

class StdFeatureProvider implements FeatureProvider
{
    /**
     * @inheritdoc
     */
    public function register($features)
    {
        $ns = '\R\Lib\Table\Feature\Provider';
        $features->registerProvider($ns."\HookPoint");
        $features->registerProvider($ns."\QueryAccess");
        $features->registerProvider($ns."\QueryModifier");
        $features->registerProvider($ns."\QueryExec");
        $features->registerProvider($ns."\ResultFeature");
        $features->registerProvider($ns."\SearchFeature");
        $features->registerProvider($ns."\AliasFeature");
        $features->registerProvider($ns."\AssocFeature");
        $features->registerProvider($ns."\AuthFeature");
    }
}
