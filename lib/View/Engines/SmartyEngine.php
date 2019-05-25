<?php
namespace R\Lib\View\Engines;

if ( ! interface_exists('\Illuminate\Contracts\View\Engine')) {
    interface EngineInterface extends \Illuminate\Contracts\View\Engine {}
} else {
    interface EngineInterface extends \Illuminate\Contracts\View\Engine {}
}

class SmartyEngine implements EngineInterface
{
	public function get($path, array $data = array())
	{
        return app("view.smarty")->fetch($path, $data);
	}
}
