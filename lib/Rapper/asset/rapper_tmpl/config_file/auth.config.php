<!?php

    registry(array(
<? foreach ($s["controller"] as $c): ?>
<?  if ($c["type"] == "login"): ?>

        // <?=$c['account']?>認証
        "Auth.<?=$c['account']?>" =>array(
            "context_name" =>"<?=$c['account']?>_auth",
            "force_login.redirect_to" =>"page:<?=$c["name"]?>.entry_form",
            "force_login.zone" =>array(
<?      foreach ($s["controller"] as $c2): ?>
<?          if ($c2["auth"] == $c['account']): ?>
                "page:<?=$c2["name"]?>.*",
<?          endif; ?>
<?      endforeach; ?>
            ),
        ),
<?  endif; ?>
<? endforeach; ?>
    ));
