<?php
	$modelCall =$r->mod("app_root")->getModelCallOnController($c);
    //$model_obj ='$this->model("'.$c["table"].'"'.($c["accessor"] ? ',"'.$c["accessor"].'"': '').')';
	$filedsSearch =$r->mod("app_root")->getFields($c,"search");
	$filedsCsv =$r->mod("app_root")->getFields($c,"csv");
	
?><@?php

/**
* <?=$c["label"]?> 
*/
class <?=str_camelize($c["_id"])?>Controller extends Controller_App 
{
<? if ($c["has"]["list_setting"]): ?>
	/**
    * 検索フォーム設定
    */
	protected $list_setting =array(
		"search" =>array(
<? foreach ($fieldsSearch as $f): ?>
			"<?=$f["_id"]?>" =>array("type" =>'eq', "target" =>"<?=$f["_id"]?>"),
<? endforeach; ?>
		),
		"sort" =>array(
			"sort_param_name" =>"sort",
			"default" =>"<?=$t['pkey']?>@ASC",
		),
		"paging" =>array(
			"offset_param_name" =>"offset",
			"limit" =>20,
			"slider" =>10,
		),
	);
	
<? endif /* $c["has"]["list_setting"] */ ?>
<? if ($c["has"]["csv_setting"]): ?>
	/**
    * CSV設定
    */
	protected $csv_setting = array(
		"rows" =>array(
			"<?=$t['pkey']?>" =>"#ID",
<? foreach ($r->mod("app_root")->getFields($c,"csv") as $f): ?>
			"<?=$f['_id']?>" =>"<?=$f['label']?>",
<? endforeach; ?>
		),
		"filters" =>array(
			array("filter" =>"sanitize"),
<? foreach ($r->get_fields($c["_id"],"csv") as $f): ?>
<? if ($f['list']): ?>
			array("target" =>"<?=$f['name']?>",
					"filter" =>"list_select", 
<? if ($f['type'] == "checklist"): ?>
					"delim" =>"/", 
<? endif; /* $f['type'] == "checklist" */ ?>
					"list" =>"<?=$f['list']?>"),
<? endif; /* $f['list'] */ ?>
<? if ($f['type'] == "date"): ?>
			array("target" =>"<?=$f['name']?>",
					"filter" =>"date"),
<? endif; /* $f['type'] == "date" */ ?>
<? endforeach; ?>
			array("filter" =>"validate",
					"required" =>array(),
					"rules" =>array()),
		),
		"ignore_empty_line" =>true,
	);
	
<? endif /* $c["has"]["csv_setting"] */ ?>

<? foreach ($r->get_controller_method($c["_id"]) as $src): ?>
<?=$src?>
<? endforeach; ?>
}