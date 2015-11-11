<? foreach ($td as $t_name => $table_def): ?>

-- <?=$ts[$t_name]["label"]?> 
DROP TABLE IF EXISTS <?=$t_name?>;
<?=dbi()->st_init_schema(array("table_def"=>$table_def));?>

<? endforeach; ?>