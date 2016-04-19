<!?php

//-------------------------------------
// Controller: <?=$c["name"]?> 
class <?=str_camelize($c["name"])?>Controller extends Controller_App {
    
<? foreach ($r->get_controller_method($c["_id"]) as $src): ?>
<?=$src?>
<? endforeach; ?>
}