<?php
namespace R\Lib\Http;
use Illuminate\Routing\UrlGenerator as IlluminateUrlGenerator;

class UrlGenerator extends IlluminateUrlGenerator
{
	public function to ($path, $extra = array(), $secure = null)
	{
        return app("request.fallback")->getUri()->getWebroot()->uri($path, $extra);
    }
}