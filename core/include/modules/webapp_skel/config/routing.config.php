<!?php
	
	registry(array(
		
		// ルーティング
		"Routing.page_to_path" =>array(
<? foreach ($s["controller"] as $c): ?>

			// <?=$c['label']?> 
<? if ($c["type"] == "index"): ?>
			"<?=$c['name']?>.index" =>"/<?=$c['name']?>/index.html",
<? elseif ($c["type"] == "login"): ?>
			"<?=$c['name']?>.index" =>"/<?=$c['name']?>/index.html",
			"<?=$c['name']?>.entry_form" =>"/<?=$c['name']?>/<?=$c['name']?>.entry_form.html",
			"<?=$c['name']?>.entry_confirm" =>"/<?=$c['name']?>/<?=$c['name']?>.entry_confirm.html",
			"<?=$c['name']?>.logout" =>"/<?=$c['name']?>/<?=$c['name']?>.logout.html",
<? elseif ($c["type"] == "master"): ?>
			"<?=$c['name']?>.index" =>"/<?=$c['name']?>/index.html",
<? if ($c["usage"] != "form"): ?>
			"<?=$c['name']?>.view_list" =>"/<?=$c['name']?>/<?=$c['name']?>.view_list.html",
			"<?=$c['name']?>.view_detail" =>"/<?=$c['name']?>/<?=$c['name']?>.view_detail.html",
<? endif; /* $c["usage"] != "form" */ ?>
<? if ($c["usage"] != "view"): ?>
			"<?=$c['name']?>.entry_form" =>"/<?=$c['name']?>/<?=$c['name']?>.entry_form.html",
			"<?=$c['name']?>.entry_confirm" =>"/<?=$c['name']?>/<?=$c['name']?>.entry_confirm.html",
			"<?=$c['name']?>.entry_exec" =>"/<?=$c['name']?>/<?=$c['name']?>.entry_exec.html",
<? endif; /* $c["usage"] != "view" */ ?>
<? if($c["usage"] == ""): ?>
			"<?=$c['name']?>.delete_confirm" =>"/<?=$c['name']?>/<?=$c['name']?>.delete_confirm.html",
			"<?=$c['name']?>.delete_exec" =>"/<?=$c['name']?>/<?=$c['name']?>.delete_exec.html",
<? endif; /* $c["usage"] == "" */ ?>
<? endif; /* by $c["type"] */ ?>
<? endforeach; /* by $s["controller"] */ ?>
		),
	));
	