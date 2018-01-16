<?php
namespace R\Lib\Analyzer;

/**
 * ファイル構成と文字列操作のみで可能な範囲の名前解決機能を提供
 */
class NameResolver
{

// -- controller

    public static function getControllerNames()
    {
        return self::scanAppDir("/app/Controller", function($file){
            if (preg_match('!/(\w+)Controller\.php$!', $file, $_)) {
                return str_underscore($_[1]);
            }
        });
    }
    public static function getControllerNameByClass($class)
    {
        if (preg_match('!\\\R\\\App\\\Controller\\\(\w+)Controller$!', $class, $_)) {
            return str_underscore($_[1]);
        }
        return null;
    }
    public static function getControllerClassByName($name)
    {
        return '\R\App\Controller\\'.str_camelize($name)."Controller";
    }

// -- table

    public static function getTableNames()
    {
        return self::scanAppDir("/app/Table", function($file){
            if (preg_match('!/(\w+)Table\.php$!', $file, $_)) {
                return str_underscore($_[1]);
            }
        });
    }

// -- enum

    public static function getEnumRepoNames()
    {
        return self::scanAppDir("/app/Enum", function($file){
            if (preg_match('!/(\w+)Enum\.php$!', $file, $_)) {
                return str_underscore($_[1]);
            }
        });
    }

// -- class

    public static function getAppClasses()
    {
        return self::scanAppDir("/app", function($file){
            if (preg_match('!^/app/(.*)\.php$!', $file, $_)) {
                return '\R\App\\'.str_replace('/','\\',$_[1]);
            }
        });
    }

// -- file

    public static function getAppFileName($file)
    {
        $ptn = '!^'.preg_quote(constant("R_APP_ROOT_DIR"),'!').'(.*)$!';
        return preg_match($ptn, $file, $_) ? $_[1] : null;
    }
    public static function scanAppDir($dir, $map_filter=null)
    {
        return self::scanDir(constant("R_APP_ROOT_DIR").$dir, function($file)use($map_filter){
            $file = NameResolver::getAppFileName($file);
            if ($map_filter) $file = call_user_func($map_filter, $file);
            return $file;
        });
    }
    public static function scanDir($dir, $map_filter=null)
    {
        $files = array();
        foreach (new \DirectoryIterator($dir) as $file) {
            if ($file->isDot()) continue;
            if ($file->isFile()) $files[] = $file->getPathname();
            if ($file->isDir()) $files = array_merge($files, self::scanDir($file->getPathname()));
        }
        if ($map_filter) {
            $files = array_map($map_filter, $files);
            $files = array_filter($files, function($file){ return strlen($file)!==0; });
        }
        return $files;
    }
}
