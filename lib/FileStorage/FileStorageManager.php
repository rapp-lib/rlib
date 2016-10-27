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
    public function create ($storage_name, $src_file=null, $meta=array())
    {
        $storage = $this->getStorage($storage_name.":");
        if ( ! $storage) {
            report_warning("Storageが取得できませんでした",array(
                "storage_name" => $storage_name,
            ));
            return null;
        }
        $code = $storage->create($src_file, $meta);
        // ファイルの作成が失敗した場合は中断
        if ( ! isset($code)) {
            report_warning("ファイルが作成できませんでした",array(
                "storage_name" => $storage_name,
                "src_file" => $src_file,
                "meta" => $meta
            ));
            return null;
        }
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
