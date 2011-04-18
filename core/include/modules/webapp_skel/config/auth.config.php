<!?php
	
	registry(array(
		
<? foreach ($s["controller"] as $c): ?>
<? if ($c["type"] == "login"): ?>

		// <?=$c['account']?>認証
		"Auth.access_only.<?=$c['account']?>" =>array(
		),
<? endif; ?>
<? endforeach; ?>
	));
	