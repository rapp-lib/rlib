<#?php
namespace R\App\Controller;

/**
 * @controller
 */
class <?=$role->getRoleControllerClassName()?> extends Controller_App
{
    protected static $access_as = "<?=$role->getName()?>";
}
