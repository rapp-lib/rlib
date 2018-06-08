<?php
namespace R\Lib\Farm;
use R\Lib\Util\GitRepositry;

class FarmGitRepositry extends GitRepositry
{
    protected $dir;
    protected $options;
    public function __construct ($dir, $options=array())
    {
        $this->dir = $dir;
        $this->options = $options;
    }
    /**
     * Gitレポジトリ直下でコマンド発行
     */
    public function cmd ($cmd, $options=array())
    {
        $options = $options + $this->options;
        $dir = getcwd();
        chdir($this->dir);
        $cmd = \R\Lib\Util\Cli::escape($cmd);
        list($ret, $out, $err) = \R\Lib\Util\Cli::exec($cmd);
        chdir($dir);
        if ( ! $options["quiet"]) {
            file_put_contents("php://stderr", $options["prompt"]."$ ".$cmd."\n");
            if ($err) foreach (explode("\n", trim($err)) as $line) {
                file_put_contents("php://stderr", $options["prompt"]."> ".$line."\n");
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
    /**
     * 現在のブランチを取得
     */
    public function getCurrentBranch()
    {
        // git rev-parse --abbrev-ref HEAD
        return $this->cmd(array("git", "rev-parse", "--abbrev-ref", "HEAD"));
    }
}
