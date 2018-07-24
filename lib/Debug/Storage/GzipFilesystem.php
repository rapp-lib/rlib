<?php
namespace R\Lib\Debug\Storage;

use Illuminate\Filesystem\Filesystem as LaravelFilesystem;

class GzipFilesystem extends LaravelFilesystem
{
	public function get($path)
	{
		return gzinflate(parent::get($path));
	}
	public function put($path, $contents, $lock = false)
	{
		return parent::put($path, gzdeflate($contents, 1), $lock);
	}
	public function append($path, $data)
	{
		return parent::append($path, gzdeflate($contents, 1));
	}
}
