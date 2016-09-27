<?php

namespace R\Lib\Core;
use R\Lib\Core\Vars;
use \ReflectionFunction;

/**
 *
 */
class Profiler
{
    /**
     * 値を解析する
     */
    public static function profileValue ($value)
    {
        $info =array();

        if ( ! is_string($value) && $info =self::profile_function($value)) {
        } else if ($info =self::profileClass($value)) {
        } else if (is_array($value)) {
            $info["type"] ="array";
            $info["values"] =array();
            foreach ($value as $k => $v) {
                $info["values"][$k] =self::profileValue($v);
            }
        } else if (is_bool($value)) {
            $info["type"] ="bool";
            $info["value"] =$value ? "true" : "false";
        } else if (is_null($value)) {
            $info["type"] ="null";
        } else if (is_string($value)) {
            $info["type"] ="string";
            $info["value"] ='"'.$value.'"';
        } else {
            $info["type"] =gettype($value);
            $info["value"] =(string)$value;
        }
        return $info;
    }

    /**
     * 関数/メソッドの情報を解析する
     */
    public static function profileFunction ($func)
    {
        $info =array();
        $ref =null;

        if ( ! is_callable($func)) {
            return null;
        }

        if (is_array($func) && method_exists($func[0], $func[1])) {
            $ref =new ReflectionMethod($func[0], $func[1]);
            $info["type"] ="method";
            $info["class"] =$ref->getDeclaringClass()->getName();
        } else {
            $info["type"] =is_string($func) ? "function" : "closure";
            $ref =new ReflectionFunction($func);
        }

        $info["ref"] =$ref;
        $info["name"] =$ref->getName();
        $info["file"] =$ref->getFileName();
        $info["line"] =$ref->getStartLine();
        $info["end_line"] =$ref->getEndLine();
        $info["file_short"] =self::getShortFilename($info["file"]);
        $param_list =array();

        foreach ($ref->getParameters() as $ref_param) {
            $param_list[] ='$'.$ref_param->getName();
        }

        $info["param_list"].=implode(',',$param_list);

        return $info;
    }

    /**
     * クラス/オブジェクトの情報を解析する
     */
    public static function profileClass ($class)
    {
        $info =array();
        $ref =null;

        if (is_object($class)) {
            $ref =new ReflectionObject($class);
        } else if (class_exists($class)) {
            $ref =new ReflectionClass($class);
        } else {
            return null;
        }

        $info["ref"] =$ref;
        $info["name"] =$ref->getName();
        $info["file"] =$ref->getFileName();
        $info["line"] =$ref->getStartLine();
        $info["file_short"] =self::getShortFilename($info["file"]);

        return $info;
    }

    /**
     * ファイル名を省略名に変換する
     * @param  [type] $filename [description]
     * @return [type]           [description]
     */
    public static function getShortFilename ($filename)
    {
        return basename($filename);
    }

    /**
     * [getFunctionId description]
     * @param  [type] $func [description]
     * @return [type]       [description]
     */
    public static function getFunctionId ($func)
    {
        $functionId ="";
        $info =self::profileFunction($func);
        if ($info["type"]=="function") {
            $functionId =$info["name"];
        } else if ($info["type"]=="method") {
            $functionId =$inf["ref"]->isStatic()
                ? $info["class"].'::'.$info["name"]
                : $info["class"].'->'.$info["name"];
        } else if ($info["type"]=="closure") {
            $functionId =$info["name"]."@".$info["file_short"]."[L".$info["line"]."]";
        }
        return $functionId;
    }
}