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
        $names = array();
        $glob = constant("R_APP_ROOT_DIR")."/app/Controller/*Controller.php";
        foreach (glob($glob) as $file) if (preg_match('!/(\w+)Controller\.php$!', $file, $_)) {
            $names[] = str_underscore($_[1]);
        }
        return $names;
    }
    public static function getControllerNameByClass($class)
    {
    }
    public static function getControllerClassByName($name)
    {
    }

// -- form

    public static function getFormPropByName($name)
    {
        if (preg_match('!^([\w_]+)\.([\w_]+)$!', $name, $_)) {
            return array(self::getControllerClassByName($_[1]), $_[2]);
        }
        return null;
    }

// -- table

    public static function getTableNames()
    {
        $names = array();
        $glob = constant("R_APP_ROOT_DIR")."/app/Table/*Table.php";
        foreach (glob($glob) as $file) if (preg_match('!/(\w+)Table\.php$!', $file, $_)) {
            $names[] = str_underscore($_[1]);
        }
        return $names;
    }

// -- enum

    public static function getEnumRepoNames()
    {
        $names = array();
        $glob = constant("R_APP_ROOT_DIR")."/app/Enum/*Enum.php";
        foreach (glob($glob) as $file) if (preg_match('!/(\w+)Enum\.php$!', $file, $_)) {
            $names[] = str_underscore($_[1]);
        }
        return $names;
    }

// -- class

    public static function getAppClasses()
    {
        $classes = array();
        for ($i=1; $i<10; $i++) {
            $glob = constant("R_APP_ROOT_DIR")."/app".str_repeat("/*", $i).".php";
            $ptn = '!^'.preg_quote(constant("R_APP_ROOT_DIR")."/app/",'!').'(.*)\.php$!';
            foreach (glob($glob) as $file) if (preg_match($ptn, $file, $_)) {
                $classes[] = '\R\App\\'.str_replace('/','\\',$_[1]);
            }
        }
        return $classes;
    }

// -- file

    public static function getAppFiles()
    {
        $files = array();
        for ($i=1; $i<20; $i++) {
            $glob = constant("R_APP_ROOT_DIR").str_repeat("/*", $i);
            foreach (glob($glob) as $file) {
                $files[] = self::getAppFileName($file);
            }
        }
        return $files;
    }
    public static function getAppFileName($file)
    {
        $ptn = '!^'.preg_quote(constant("R_APP_ROOT_DIR"),'!').'(.*)$!';
        return preg_match($ptn, $file, $_) ? $_[1] : null;
    }
}
