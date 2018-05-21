<?php
namespace R\Lib\Util;

class GitRepositry
{
    protected $dir;
    protected $cmd_log = array();
    public function __construct ($dir)
    {
        $this->dir = $dir;
    }
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
        $cmd = app()->console->cliEscape($cmd);
        $this->cmd_log[] = "> ".$cmd;
        $result = shell_exec($cmd);
        chdir($dir);
        return rtrim($result);
    }

// -- Status確認

    /**
     * Commitされてない差分を取得
     */
    public function getChanges ()
    {
        $changes = $this->cmd(array("git","status","-s"));
        return strlen($changes)===0 ? array() : explode("\n",$changes);
    }
    /**
     * 差分を取得
     */
    public function getDiff ($ref="HEAD")
    {
        $changes = $this->cmd(array("git","diff","--name-only",$ref));
        return strlen($changes)===0 ? array() : explode("\n",$changes);
    }

// -- Log取得

    /**
     * CommitIDを新しい順で取得
     */
    public function getCommits ($branch)
    {
        $result = $this->cmd(array("git","log","--format=%h",$branch));
        return explode("\n",$result);
    }

// -- Branch操作

    /**
     * Chackout中のBranchを取得
     */
    public function getCurrentBranch ()
    {
        $branch = $this->cmd(array("git","rev-parse","--abbrev-ref","HEAD"));
        return $branch;
    }
    /**
     * Branchを全件取得
     */
    public function getBranches ()
    {
        $result = $this->cmd(array("git","for-each-ref","--format=%(refname)"));
        $branches = array();
        foreach (explode("\n",$result) as $branch) {
            if (preg_match('!^refs/heads/([^/]+?)$!',$branch,$match)) {
                $branches[] = $match[1];
            } elseif (preg_match('!^refs/remotes/(([^/]+?)/([^/]+?))$!',$branch,$match)) {
                $branches[] = $match[1];
            }
        }
        return $branches;
    }
    /**
     * Branchを新規作成
     */
    public function createBranch ($branch, $ref="HEAD")
    {
        $this->cmd(array("git","branch",$branch,$ref));
    }

// -- HEADに対する操作

    /**
     * HEADの差し先Branch変更
     */
    public function checkout ($branch)
    {
        $this->cmd(array("git","checkout",$branch));
    }
    /**
     * Branchの差し先を強制的に変更
     */
    public function resetBranch ($to, $options=array())
    {
        $this->cmd(array("git","reset",$to,$options));
    }
    /**
     * BranchのMerge
     */
    public function merge ($ref, $options=array())
    {
        $this->cmd(array("git","merge",$ref,$options));
    }
    /**
     * BranchのMerge
     */
    public function mergeNoCommit ($ref)
    {
        $this->cmd(array("git","merge","--no-commit",$ref));
    }

// -- HEADに対する操作

    /**
     * 全差分を反映
     */
    public function addCommitAll ($message)
    {
        $this->cmd(array("git","add","-A"));
        $this->cmd(array("git","commit","-m",$message));
    }
}
