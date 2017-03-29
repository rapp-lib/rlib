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
        if ( ! $this->storage) {
            report_error("Storageが解決できませんでした",array(
                "code" => $this->code,
                "storage" => $this->storage,
            ));
        }
    }
    public function isValid ()
    {
        return isset($this->storage);
    }
    public function getCode ()
    {
        if ( ! $this->isValid()) {
            return null;
        }
        return $this->code;
    }
    public function getFile ()
    {
        if ( ! $this->isValid()) {
            return null;
        }
        return $this->storage->getFile($this->code);
    }
    public function download ()
    {
        if ( ! $this->isValid()) {
            return null;
        }
        return $this->storage->download($this->code);
    }
    public function getMeta ()
    {
        if ( ! $this->isValid()) {
            return null;
        }
        return $this->storage->getMeta($this->code);
    }
    public function remove ()
    {
        if ( ! $this->isValid()) {
            return null;
        }
        return $this->storage->remove($this->code);
    }
    public function updateMeta ($meta)
    {
        if ( ! $this->isValid()) {
            return null;
        }
        return $this->storage->updateMeta($this->code, $meta);
    }
    public function isAccessible ($use_case=null)
    {
        if ( ! $this->isValid()) {
            return null;
        }
        return $this->storage->isAccessible($this->code, $use_case);
    }
}
