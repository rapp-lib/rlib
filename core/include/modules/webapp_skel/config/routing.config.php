<!?php
	
	registry(array(
		
		// ルーティング
		"Routing.page_to_path" =>array(
<? foreach ($s["controller"] as $c): ?>

			// <?=$c['label']?> 
<? if ($c["type"] == "login"): ?>
			"<?=$c['name']?>.index" =>"/<?=$c['name']?>/<?=$c['name']?>.index.html",
			"<?=$c['name']?>.entry_form" =>"/<?=$c['name']?>/<?=$c['name']?>.entry_form.html",
			"<?=$c['name']?>.entry_confirm" =>"/<?=$c['name']?>/<?=$c['name']?>.entry_confirm.html",
			"<?=$c['name']?>.logout" =>"/<?=$c['name']?>/<?=$c['name']?>.logout.html",
<? elseif ($c["type"] == "master"): ?>
			"<?=$c['name']?>.index" =>"/<?=$c['name']?>/<?=$c['name']?>.index.html",
			"<?=$c['name']?>.view_list" =>"/<?=$c['name']?>/<?=$c['name']?>.view_list.html",
			"<?=$c['name']?>.view_detail" =>"/<?=$c['name']?>/<?=$c['name']?>.view_detail.html",
			"<?=$c['name']?>.entry_form" =>"/<?=$c['name']?>/<?=$c['name']?>.entry_form.html",
			"<?=$c['name']?>.entry_confirm" =>"/<?=$c['name']?>/<?=$c['name']?>.entry_confirm.html",
			"<?=$c['name']?>.entry_exec" =>"/<?=$c['name']?>/<?=$c['name']?>.entry_exec.html",
			"<?=$c['name']?>.delete_confirm" =>"/<?=$c['name']?>/<?=$c['name']?>.delete_confirm.html",
			"<?=$c['name']?>.delete_exec" =>"/<?=$c['name']?>/<?=$c['name']?>.delete_exec.html",
<? elseif ($c["type"] == "form"): ?>
			"<?=$c['name']?>.index" =>"/<?=$c['name']?>/<?=$c['name']?>.index.html",
			"<?=$c['name']?>.entry_form" =>"/<?=$c['name']?>/<?=$c['name']?>.entry_form.html",
			"<?=$c['name']?>.entry_confirm" =>"/<?=$c['name']?>/<?=$c['name']?>.entry_confirm.html",
			"<?=$c['name']?>.entry_exec" =>"/<?=$c['name']?>/<?=$c['name']?>.entry_exec.html",
<? endif; ?>
<? endforeach; ?>
		),
	));
	