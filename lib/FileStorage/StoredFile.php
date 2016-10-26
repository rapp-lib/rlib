<?php
namespace R\Lib\FileStorage;

/**
 * コードに対応して保存されているファイル
 */
final class StoredFile
{
    private $storage_manager;
    private $code;

    public function __construct ($storage_manager, $code)
    {
        $this->storage_manager = $storage_manager;
        $this->storage = $this->storage_manager->getStorage($code);
        $this->code = $code;
    }
    public function getCode ()
    {
        return $this->code;
    }
    public function getFile ()
    {
        return $this->storage->getFile($code);
    }
    public function getUrl ()
    {
        return $this->storage->getUrl($code);
    }
    public function getMeta ()
    {
        return $this->storage->getMeta($code);
    }
    public function remove ()
    {
        return $this->storage->remove($code);
    }
    public function updateMeta ($meta)
    {
        return $this->storage->updateMeta($code, $meta);
    }
    public function isAccessible ($use_case=null)
    {
        return $this->storage->isAccessible($code, $use_case);
    }
}
