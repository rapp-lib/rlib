<?php
namespace R\Lib\Util;

/**
 * 探索可能なClassを収集する
 */
class ClassFinder
{
    private static $composer_loader = null;

    /**
     * ComposerのClassLoaderオブジェクトを取得
     */
    public static function getComposerLoader ()
    {
        if (self::$composer_loader) {
            return self::$composer_loader;
        }

        // グローバル変数経由でオブジェクトを探す
        if ($GLOBALS["_composer_loader"]) {
            return self::$composer_loader = $GLOBALS["_composer_loader"];
        }

        // 定義されている全クラスから自動生成クラスを探す
        foreach (get_declared_classes() as $class) {
            if (preg_match('!^ComposerAutoloaderInit!',$class)) {
                return self::$composer_loader = $class::getLoader();
            }
        }

        return null;
    }

    /**
     * NS以下に定義されているクラスを全て取得
     */
    public static function findClassInNamespace ($ns)
    {
        $classes = array();
        $ns = preg_replace('!^\\\\!','',$ns);

        // Composer経由で読み込まれるClassを検索する
        if ($composer_loader = self::getComposerLoader()) {
            // PSR-4の指定を探索
            $prefix_dirs = $composer_loader->getPrefixesPsr4();
            foreach ($prefix_dirs as $prefix => $dirs) {
                // NSに該当するディレクトリ以下のファイルを探索
                if (strpos($ns,$prefix)===0) {
                    foreach ($dirs as $dir) {
                        $ns_dir = preg_replace('!^'.preg_quote($prefix,'!').'!','',$ns);
                        $ns_dir = $dir.'/'.str_replace('\\','/',$ns_dir);
                        $files = array_merge((array)$files, self::findFileInDir($ns_dir));
                        foreach ($files as $file) {
                            // ファイル名からNS付きクラス名に変換する
                            if (preg_match('!^'.preg_quote($dir,'!').'/(.*?)\.[^/]+$!',$file,$match)) {
                                $class = $prefix.str_replace('/','\\',$match[1]);
                                // クラスが存在していれば発見
                                if (class_exists($class)) {
                                    $classes[] = $class;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $classes;
    }

    /**
     * ディレクトリ以下のファイルを全て取得
     */
    public static function findFileInDir ($dir)
    {
        $files = array();
        foreach (scandir($dir) as $f) {
            if ($f=="." || $f=="..") {
                continue;
            }
            $f = $dir.$f;
            if (is_dir($f)) {
                $files[] = self::findFileInDir($f);
            } else {
                $files[] = $f;
            }
        }
        return $files;
    }
}