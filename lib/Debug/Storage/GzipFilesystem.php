<?php
namespace R\Lib\Debug\Storage;

use Illuminate\Filesystem\Filesystem as LaravelFilesystem;

class GzipFilesystem extends LaravelFilesystem
{
	public function get($path)
	{
		$contents = parent::get($path);
		if (function_exists("gzdecode")) $contents = gzdecode($contents);
		return $contents;
	}
	public function put($path, $contents, $lock = false)
	{
		if (function_exists("gzencode")) $contents = gzencode($contents, 1);
		return parent::put($path, $contents, $lock);
	}
}
