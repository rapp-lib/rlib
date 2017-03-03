<?php
namespace R\Lib\Core\Middleware;

use R\Lib\Core\Contract\Middleware;

class StoredFileService implements Middleware
{
    public function handler ($next)
    {
        $path = app()->router->getCurrentRoute()->getPath();
        $code = preg_replace('!^/file:/!','',$path);
        return app()->response->downloadStoredFile(file_storage()->get($code));
    }
}
