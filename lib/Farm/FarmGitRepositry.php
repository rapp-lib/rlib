<?php
namespace R\Lib\Farm;
use R\Lib\Util\GitRepositry;

class FarmGitRepositry extends GitRepositry
{
    protected $dir;
    public function __construct ($dir)
    {
        $this->dir = $dir;
    }
    protected $cmd_log = array();
    public function getCommandLog ()
    {
        return $this->cmd_log;
    }
    /**
     * Gitレポジトリ直下でコマンド発行
     */
    public function cmd ($cmd, $options=array())
    {
        $dir = getcwd();
        chdir($this->dir);
        $cmd = \R\Lib\Util\Cli::escape($cmd);
        $this->cmd_log[] = "> ".$cmd;
        list($ret, $out, $err) = \R\Lib\Util\Cli::exec($cmd);
        chdir($dir);
        if ( ! $options["quiet"]) {
            file_put_contents("php://stderr", "$ ".$cmd."\n");
            if ($err) foreach (explode("\n", trim($err)) as $line) {
                file_put_contents("php://stderr", "> ".$line."\n");
            }
        }
        if ($options["return"] != "rawoutput") {
            $out = rtrim($out);
        }
        return $out;
    }
    public function noEscape ($cmd)
    {
        return \R\Lib\Util\Cli::noEscape($cmd);
    }
}
