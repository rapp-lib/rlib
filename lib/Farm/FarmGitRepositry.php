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
    public function cmd ($cmd)
    {
        $dir = getcwd();
        chdir($this->dir);
        $cmd = \R\Lib\Util\Cli::escape($cmd);
        $this->cmd_log[] = "> ".$cmd;
        list($ret, $out, $err) = \R\Lib\Util\Cli::exec($cmd);
        chdir($dir);
        return rtrim($out);
    }
    public function noEscape ($cmd)
    {
        return \R\Lib\Util\Cli::noEscape($cmd);
    }
}
