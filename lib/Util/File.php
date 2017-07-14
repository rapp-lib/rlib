<?php
namespace R\Lib\Core\Util;

/**
 * ファイル操作
 */
class File
{
    /**
     * ディレクトリの作成
     */
    public static function createDir ($dir)
    {
        if ( ! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
    }
    /**
     * ファイルの作成
     */
    public static function create ($file)
    {
        self::createDir(dirname($file));
        if ( ! file_exists($file)) {
            touch($file);
            chmod($file, 0664);
        }
    }
    /**
     * ファイルの書き込み
     */
    public static function write ($file, $content)
    {
        self::create($file);
        file_put_contents($file, $content);
    }
    /**
     * ファイルの読み込み
     */
    public static function read ($file)
    {
        if ( ! file_exists($file)) {
            return null;
        }
        return file_get_contents($file);
    }
    /**
     * ファイルのコピー
     */
    public static function copy ($file, $dest)
    {
        self::createDir(dirname($dest));
        copy($file,$dest);
    }
    /**
     * ファイルの移動
     */
    public static function move ($file, $dest)
    {
        self::createDir(dirname($dest));
        rename($file,$dest);
    }
    /**
     * ファイルの削除
     */
    public static function delete ($file, $dustbox_dir=null)
    {
    }
}