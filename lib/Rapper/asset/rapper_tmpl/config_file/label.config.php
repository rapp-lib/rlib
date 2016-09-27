<!?php

    registry(array(

        // ラベル
<? foreach ((array)$ts as $t): ?>

        // <?=$t['name']?>
<? foreach ((array)$t["fields"] as $tc): ?>
        "Label.cols.<?=$tc['name']?>" =>"<?=$tc['label']?>",
<? endforeach; ?>
<? endforeach; ?>
<? foreach ($s["controller"] as $c): ?>
<? if ($c["type"] == "login"): ?>

        // <?=$c['account']?>認証エラー
        "Label.errmsg.user.<?=$c['account']?>_login_failed" =>"IDまたはPassが誤っています",
<? endif; ?>
<? endforeach; ?>
    ));
