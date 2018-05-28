<?php
namespace R\Lib\Debug;
use R\Lib\Http\HttpController;

class DebugbarController extends HttpController
{
    public function act_openhandler()
    {
        return app()->http->response("html", "ready");
    }
}
