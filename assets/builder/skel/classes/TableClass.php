<#?php
namespace R\App\Table;

/**
 * @table
 */
class <?=$table->getClassName()?> extends Table_App
{
    protected static $table_name = "<?=$table->getDefName()?>";
    protected static $cols = array(
<?php foreach ($table->getCols() as $col): ?>
<?=$col->getColDefSource()?>
<?php endforeach; ?>
    );
    protected static $def = array(
        "indexes" => array(
<?php foreach ($table->getIndexes() as $index): ?>
            array("name"=>"<?=$index["name"]?>", "cols"=>array("<?=implode($index["cols"],'", "')?>")),
<?php endforeach; ?>
        ),
    );
}
