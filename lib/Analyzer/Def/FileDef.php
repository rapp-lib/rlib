<?php
namespace R\Lib\Analyzer\Def;

class FileDef extends Def_Base
{
    public function getFullName()
    {
        return constant("R_APP_ROOT_DIR")."/".$this->name;
    }
}
