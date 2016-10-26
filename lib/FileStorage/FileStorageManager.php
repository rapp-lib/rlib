<?php
namespace R\Lib\FileStorage;

final class FileStorageManager
{
    private static $instance = null;

    private $storages = array();

    /**
     * インスタンスの取得
     */
    public static function getInstance ()
    {
        if ( ! self::$instance) {
            self::$instance = new FileStorageManager();
        }
        return self::$instance;
    }

    /**
     * コードからStorageインスタンスを取得する
     */
    public function getStorage ($code)
    {
        if (preg_match('!^(\w+):!',$code,$match)) {
            $storage_name = $match[1];
            $storage_class = extention("FileStorage",$storage_name);
            if ( ! $this->storages[$storage_name]) {
                $this->storages[$storage_name] = new $storage_class;
            }
            return $this->storages[$storage_name];
        }
        return null;
    }

    /**
     * StoredFileの作成
     */
    public function create ($storage_name, $file=null, $meta=array())
    {
        if ( ! isset($src_file)) {
            $src_file = date("Ymd-His")."-".rand(1000,9999);
        }
        $storage = self::getStorage($storage_name.":");
        $code = $storage->create($file, $meta);
        return new StoredFile($this, $code);
    }

    /**
     * StoredFileの取得
     */
    public function get ($code)
    {
        return new StoredFile($this, $code);
    }
}
