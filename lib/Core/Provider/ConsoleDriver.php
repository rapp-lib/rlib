<?php
namespace R\Lib\Core\Provider;

use R\Lib\Core\Contract\Provider;
use ArrayObject;

class ConsoleDriver extends ArrayObject implements Provider
{
    public function __construct ()
    {
        $this->commands = (array)app()->config("console.command");
        $this->exchangeArray($this->getCliParams());
    }
    /**
     * Commandインスタンスを取得
     */
    public function getCommand ($command_name, $action_name)
    {
        $command_class = $this->commands[$command_name];
        if ( ! isset($command_class) || ! class_exists($command_class)) {
            report_error("Commandの設定が不正です",array(
                "command_name" => $command_name,
                "command_class" => $command_class,
            ));
        }
        $command = new $command_class($command_name, $action_name);
        return $command;
    }
    /**
     * アクセスされたパラメータによりCommandのactionを実行
     */
    public function execCurrentCommand ()
    {
        $callback = app()->middleware->apply(function () {
            $params = app()->console->getCliParams();
            $command = app()->console->getCommand($params[0], $params[1]);
            return $command->execAct($params);
        }, (array)app()->config("console.middleware"));
        return call_user_func($callback);
    }
    /**
     * CLI（コマンドライン）実行時パラメータの取得
     */
    public function getCliParams ()
    {
        $argv = $_SERVER["argv"];
        unset($argv[0]);
        $params = array();
        foreach ($argv as $a_argv) {
            // --XXX=AAA , --XXX
            if (preg_match('!^--([^=]+)(?:=(.+))?$!',$a_argv,$match)) {
                $params[$match[1]] = $match[2];
            // -X , -XAAA
            } elseif (preg_match('!^-(.)(.+)?$!',$a_argv,$match)) {
                $params[$match[1]] = $match[2];
            // XXX
            } else {
                $params[] = $a_argv;
            }
        }
        return $params;
    }
    /**
     * CLIに渡す文字列の構築
     */
    public function cliEscape ($value)
    {
        $escaped_value = null;
        // 引数配列
        if (is_array($value)) {
            $escaped_value = array();
            foreach ($value as $k => $v) {
                if (is_string($k)) {
                    $escaped_value[] = $this->cliEscape($k.$v);
                } else {
                    $escaped_value[] = $this->cliEscape($v);
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
     * 標準出力
     */
    public function output ($string)
    {
        print $string;
    }
    /**
     * エラー出力
     */
    public function outputError ($string)
    {
        fputs(STDERR, $string);
    }
}