<?php
namespace R\Lib\Builder\Element;

class EnumSetElement extends Element_Base
{
    public function getFullName ()
    {
        return $this->getParent()->getName().".".$this->getName();
    }
    public function getCol ()
    {
        return $this->getParent()->getParent()->getColByName($this->getAttr("col_name"));
    }
    public function hasOwnedEnumSet ($role)
    {
        if ($fkey_for_table = $this->getCol()->getFkeyForTable()) {
            foreach ($fkey_for_table->getOwnedByCols() as $owned_by_col) {
                if ($owned_role = $owned_by_col->getFkeyForTable()->getAuthRole()) {
                    return $role==$owned_role;
                }
            }
        }
        return false;
    }
}
