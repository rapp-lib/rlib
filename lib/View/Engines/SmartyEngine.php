<?php
namespace R\Lib\View\Engines;

// >= 5.5
if (interface_exists('Illuminate\Contracts\View\Engine')) {
abstract class BaseSmartyEngine implements \Illuminate\Contracts\View\Engine
{
}
} else {
// < 5.5
abstract class BaseSmartyEngine implements \Illuminate\View\Engines\EngineInterface
{
}
}

class SmartyEngine extends BaseSmartyEngine
{
    public function get($path, array $data = array())
    {
        return app("view.smarty")->fetch($path, $data);
    }
}
