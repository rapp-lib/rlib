<?php
namespace R\Lib\Builder;

class CodeRenderer
{
    public static function elementLines($height, $lines)
    {
        $code = "";
        foreach ($lines as $k=>$v) $code .= self::elementLine($height, $k, $v);
        return $code;
    }
    public static function elementLine($height, $k, $v)
    {
        if ( ! isset($v)) return;
        return self::indent($height).self::key($height, $k).self::value($height, $v).','."\n";
    }
    public static function indent($height)
    {
        return str_repeat('    ', $height);
    }
    public static function key($height, $k)
    {
        return ($k && ! is_numeric($k) ? self::value($height, $k).'=>': "");
    }
    public static function value($height, $v)
    {
        if ($v instanceof CodeFragment){
            return (string)$v;
        } elseif (is_array($v)) {
            if (count($v) < 8) {
                foreach ($v as $k2=>$v2) {
                    if (isset($v2)) {
                        $v[$k2] = self::key($height, $k2).self::value($height, $v2);
                    } else {
                        unset($v[$k2]);
                    }
                }
                $v = 'array('.implode(', ',$v).')';
            } else {
                foreach ($v as $k2=>$v2) {
                    $v[$k2] = self::elementLine($height+1, $k2, $v2);
                }
                $v = 'array('."\n".implode('',$v).str_repeat('    ', $height).')';
            }
        } elseif (is_null($v)) {
            $v = 'null';
        } elseif (is_bool($v)) {
            $v = $v ? 'true' : 'false';
        } elseif (is_numeric($v)) {
        } else {
            $v = '"'.str_replace(array('\\', '"'), array('\\\\', '\\"'), (string)$v).'"';
        }
        return $v;
    }
    public static function call($height, $callback, $args=array())
    {
        foreach ($args as $i=>$arg) $args[$i] = self::value($height, $arg);
        return $callback."(".implode(", ", $args).")";
    }
    public static function code($code)
    {
        return new CodeFragment($code);
    }
    public static function smartyValue($value)
    {
        if (is_array($value)) {
            $elements = array();
            foreach ($value as $k=>$v) $elements[] = '"'.$k.'"=>'.self::smartyValue($v);
            return '['.implode(', ', $elements).']';
        }
        else if (is_numeric($value)) return $value;
        else if (is_bool($value)) return $value ? "true" : "false";
        else if (is_null($value)) return "null";
        else return (string)$value;
    }
}
class CodeFragment
{
    private $code;
    public function __construct($code)
    {
        $this->code = $code;
    }
    public function __toString()
    {
        return (string)$this->code;
    }
}
