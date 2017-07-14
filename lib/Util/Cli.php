<?php
namespace R\Lib\Util;

class Cli
{
    /**
     * CLI（コマンドライン）実行時パラメータの取得
     */
    public static function getCliParams ()
    {
        $argv = $_SERVER["argv"];
        unset($argv[0]);
        $params = array();
        foreach ($argv as $a_argv) {
            // --XXX=AAA , --XXX
            if (preg_match('!^--([^=]+)(?:=(.+))?$!',$a_argv,$match)) {
                $params[$match[1]] = strlen($match[2]) ? $match[2] : true;
            // -X , -XAAA
            } elseif (preg_match('!^-(.)(.+)?$!',$a_argv,$match)) {
                $params[$match[1]] = strlen($match[2]) ? $match[2] : true;
            // XXX
            } else {
                $params[] = $a_argv;
            }
        }
        return $params;
    }
    /**
     * execに渡す文字列の構築
     * ex) exec(Cli::execEscape("test", array("-u"=>$user), ">" => array($file)), $out, $ret);
     */
    public static function escape ($value)
    {
        $escaped_value = null;
        // 引数配列
        if (is_array($value)) {
            $escaped_value = array();
            foreach ($value as $k => $v) {
                if (is_string($k)) {
                    if (is_array($v)) {
                        $escaped_value[] = $k;
                        $escaped_value[] = self::escape($v);
                    } else {
                        $escaped_value[] = self::escape($k.$v);
                    }
                } else {
                    $escaped_value[] = self::escape($v);
                }
            }
            $escaped_value =implode(" ", $escaped_value);
        // 文字列
        } elseif (is_string($value)) {
            $escaped_value = escapeshellarg($value);
        }
        return $escaped_value;
    }
    /**
     * execに渡す文字列の構築
     * ex) list($ret, $out, $err) = Cli::exec("test -v", "Hello");
     */
    public static function exec ($cmd, $input=null)
    {
        $desc = array();
        if ($input) $desc[0] = array("pipe", "r");
        $desc[1] = array("pipe", "w");
        $desc[2] = array("pipe", "w");
        $proc = proc_open($cmd, $desc, $pipes, $cwd=null, $env=array());
        if (is_resource($proc)) {
            if ($input) {
                fwrite($pipes[0], $input);
                fclose($pipes[0]);
            }
            $out = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            $err = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            $ret = proc_close($proc);
            return array($ret, $out, $err);
        }
        return array();
    }
}
