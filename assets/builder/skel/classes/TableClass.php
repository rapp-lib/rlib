<#?php
namespace R\App\Table;

/**
 * @table
 */
class <?=$table->getClassName()?> extends Table_App
{
    protected static $table_name = ".$table->getName().";
    protected static $cols = array(
<?php foreach ($table->getCol() as $col): ?>
        <?=$col->getColDef()?><?="\n"?>
<?php endforeach; ?>
    );
    protected static $def = array(
        "indexes" => array(),
    );
}
