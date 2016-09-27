<?php

namespace R\Lib\Core;

/**
 *
 */
class ClassLoader
{
    /**
     * [$map description]
     * @var array
     */
    protected static $map =array();

    /**
     * NSと読み込み先DIRを設定
     */
    public static function add($ns, $includePath)
    {
        if ( ! is_array(self::$map[$ns])) {

            self::$map[$ns] =array();
        }

        self::$map[$ns][] = $includePath;
    }

    /**
     * SPL autoloader stackに登録
     */
    public static function install()
    {
        spl_autoload_register(array(get_class(), 'loadClass'));
    }

    /**
     * SPL autoloader stackから登録解除
     */
    public static function uninstall()
    {
        spl_autoload_unregister(array(get_class(), 'loadClass'));
    }

    /**
     * SPL autoloaderから呼び出されるIF
     */
    public static function loadClass($className)
    {
        if (false !== ($lastNsPos = strripos($className, '\\'))) {

            // クラス名からNSを分離
            $ns = substr($className, 0, $lastNsPos);
            $className = substr($className, $lastNsPos + 1);
            $fileName = str_replace('_', '/', $className).'.php';

            if ($found =self::findAsset($ns,$fileName)) {

                require_once($found);
            }
        }
    }

    /**
     * NS上のファイルを探索する
     * @param  [type] $ns [description]
     * @param  [type] $path [description]
     * @return [type]     [description]
     */
    public static function findAsset($ns, $path)
    {
        foreach (self::$map as $_ns => $_includePaths) {

            // 基底NSが適合しない場合はスキップ
            if ($_ns.'\\' !== substr($ns, 0, strlen($_ns.'\\'))) {

                continue;
            }

            // 基底NS部分を切り捨てる（PSR-0不適合の仕様）
            $ns =substr($ns, strlen($_ns.'\\'));

            $fileName = str_replace('\\', '/', $ns) . '/' . $path;

            foreach ($_includePaths as $dir) {

                if (file_exists($found = $dir . '/' .$fileName)) {

                    // 対象のファイルが発見された場合は探索終了
                    return $found;
                }
            }
        }

        return null;
    }
}