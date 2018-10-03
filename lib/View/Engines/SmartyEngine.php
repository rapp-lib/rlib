<?php
namespace R\Lib\View\Engines;

use Illuminate\View\Engines\EngineInterface;

class SmartyEngine implements EngineInterface
{
	public function get($path, array $data = array())
	{
        return app("view.smarty")->fetch($path, $data);
	}
}
