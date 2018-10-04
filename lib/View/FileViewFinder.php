<?php
namespace R\Lib\View;

use Illuminate\View\FileViewFinder as IlluminateFileViewFinder;

class FileViewFinder extends IlluminateFileViewFinder
{
	public function find($name)
	{
        if (strpos($name, "mail:")===0) {
            $name = preg_replace('!^mail:/*!', constant("R_APP_ROOT_DIR")."/resources/mail/", $name);
        }
        if (strpos($name, "/")===0 && $this->files->exists($name)) $this->views[$name] = $name;
        return parent::find($name);
    }
}
