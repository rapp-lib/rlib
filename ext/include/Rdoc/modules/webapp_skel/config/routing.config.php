<!?php

    registry(array(
        "Routing.page_to_path" =>array(
<? foreach ($s["controller"] as $c): ?>

            // <?=$c['label']?>

<? if ($c["type"] == "index"): ?>
            "<?=$c['name']?>.index" =>"/<?=$c['name']?>/index.html",
<? elseif ($c["type"] == "login"): ?>
            "<?=$c['name']?>.index" =>"/<?=$c['name']?>/index.html",
            "<?=$c['name']?>.login" =>"/<?=$c['name']?>/login.html",
            "<?=$c['name']?>.logout" =>"/<?=$c['name']?>/logout.html",
<? elseif ($c["type"] == "master"): ?>
            "<?=$c['name']?>.index" =>"/<?=$c['name']?>/index.html",
<? if ($c["usage"] != "form"): ?>
            "<?=$c['name']?>.view_list" =>"/<?=$c['name']?>/view_list.html",
<? endif; /* $c["usage"] != "form" */ ?>
<? if ($c["usage"] != "view"): ?>
            "<?=$c['name']?>.entry_form" =>"/<?=$c['name']?>/entry_form.html",
            "<?=$c['name']?>.entry_confirm" =>"/<?=$c['name']?>/entry_confirm.html",
            "<?=$c['name']?>.entry_exec" =>"/<?=$c['name']?>/entry_exec.html",
<? endif; /* $c["usage"] != "view" */ ?>
<? if($c["usage"] != "view" && $c["usage"] != "form"): ?>
            "<?=$c['name']?>.delete" =>"/<?=$c['name']?>/delete.html",
<? endif; /* $c["usage"] != "view" && $c["usage"] != "form" */ ?>
<? if($c["usage"] != "form" && $c["use_csv"]): ?>
            "<?=$c['name']?>.view_csv" =>"/<?=$c['name']?>/view_csv.html",
<? endif; /* $c["usage"] != "form" && $c["usage"] != "form" */ ?>
<? if($c["usage"] != "view" && $c["use_csv"]): ?>
            "<?=$c['name']?>.entry_csv_form" =>"/<?=$c['name']?>/entry_csv_form.html",
            "<?=$c['name']?>.entry_csv_confirm" =>"/<?=$c['name']?>/entry_csv_confirm.html",
            "<?=$c['name']?>.entry_csv_exec" =>"/<?=$c['name']?>/entry_csv_exec.html",
<? endif; /* $c["usage"] != "view" && $c["usage"] != "form" */ ?>
<? endif; /* by $c["type"] */ ?>
<? endforeach; /* by $s["controller"] */ ?>
        ),
    ));
