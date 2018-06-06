<?php
namespace R\Lib\Exception;
use Whoops\Handler\PrettyPageHandler as WhoopsPrettyPageHandler;

class PrettyPageHandler extends WhoopsPrettyPageHandler
{
    public function getInspector()
    {
        return parent::getInspector();
    }
}
