<?php
namespace R\Lib\File;

class UserFileDriver
{
    private $storages;
    public function __construct()
    {
        foreach ((array)app()->config("file.storages") as $storage_name=>$config) {
            $this->storages[$storage_name] = new UserFileStorage($storage_name, $config);
        }
    }
    public function getStorage($storage_name)
    {
        return $this->storages[$storage_name];
    }
    public function getFileByUri($uri)
    {
        foreach ($this->storages as $storage) {
            if ($file = $storage->getFileByUri($uri)) {
                return $file;
            }
        }
        return null;
    }
}
