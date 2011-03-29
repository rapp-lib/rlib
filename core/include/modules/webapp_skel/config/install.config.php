<!?php

	ob_start();
	
?>	
	
<? foreach ($td as $t_name => $table_def): ?>

-- <?=$ts[$t_name]["label"]?> 
<?=DBI::load()->st_init_schema(array("table_def"=>$table_def));?>

<? endforeach; ?>


<!?

	$install =ob_get_clean();
	
	registry("Install.sql.",$install);
	