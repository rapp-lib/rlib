<?php
namespace R\Lib\Console\Command;

use R\Lib\Console\Command;
use R\Lib\Core\Exception\ResponseException;

class BuildCommand extends Command
{
    protected function init ()
    {
        $this->git = new GitRepositry(constant("R_APP_ROOT_DIR"));
        $this->config = array(
            "branch_d" => "develop",
            "branch_b1" => "build-latest",
            "branch_b2" => "build-working",
        );
    }
    public function act_make ()
    {
        $this->makeSetup();
        $this->makeCheck();
        $this->makeApply();
    }
    /**
     * 自動生成の事前処理
     */
    private function makeSetup ()
    {
        // setup
        //     if ! isClean
        //         error
        if ($changes = $this->git->getChanges()) {
            report_error("作業コピーがcleanではありません",array(
                "changes" => $changes,
            ));
        }
        //     if ! exists b1
        //         createBranch b1 from d
        $branches = $this->git->getBranches();
        if ( ! in_array($this->config["branch_b1"],$branches)) {
            $this->git->createBranch($this->config["branch_b1"], $this->config["branch_d"]);
        }
        //     if ! exists b2
        //         createBranch b2 from b1
        if ( ! in_array($this->config["branch_b2"],$branches)) {
            $this->git->createBranch($this->config["branch_b2"], $this->config["branch_b1"]);
        }
        //     if ! hasSameParent d b1
        //        ! hasSameParent d b2
        //         error
        $d_commits = $this->git->getCommits($this->config["branch_d"]);
        $b1_commits = $this->git->getCommits($this->config["branch_b1"]);
        if (array_intersect($d_commits, $b1_commits)) {
            report_error("b1ブランチがdと無関係です");
        }
        $b2_commits = $this->git->getCommits($this->config["branch_b2"]);
        if (array_intersect($d_commits, $b2_commits)) {
            report_error("b2ブランチがdと無関係です");
        }
    }
    /**
     * 前回の自動生成結果の反映、または削除
     */
    private function makeCheck ()
    {
        // check
        //     if isIncluded b2 d
        //         checkout b1
        //         merge b2
        $b2_commits = $$this->git->getCommits($this->config["branch_b2"]);
        $b2_latest_commit = array_shift($b2_commits);
        $d_commits = $this->git->getCommits($this->config["branch_d"]);
        if (in_array($b2_latest_commit, $d_commits)) {
            $this->git->checkout($this->config["branch_b1"]);
            $this->git->merge($this->config["branch_b2"]);
        //     if ! isIncluded b2 d
        //         checkout b2
        //         resetTo b1
        } else {
            $this->git->checkout($this->config["branch_b2"]);
            $this->git->resetBranch($this->config["branch_b1"]);
        }
    }
    /**
     * 自動生成実行の差分抽出
     */
    private function makeApply ()
    {
        // apply
        //     checkout b2
        $this->git->checkout($this->config["branch_b2"]);
        //     make
        try {
            app()->builder->start();
        } catch (ResponseException $e) {
            report_warning("Builderの実行中にエラーがありました",array(
                "exceptions" => $e,
            ));
        }
        //     addCommitAll msg
        $this->addCommitAll("make");
        //     checkout d
        $this->git->checkout($this->config["branch_d"]);
        //     merge b2 --no-commit
        $this->git->merge($this->config["branch_b2"],array("--no-commit"));
    }
}
class GitRepositry
{
    protected $dir;
    public function __construct ($dir)
    {
        $this->dir = $dir;
    }
    /**
     * Gitレポジトリ直下でコマンド発行
     */
    public function cmd ($cmd)
    {
        $dir = getcwd();
        chdir($this->dir);
        $cmd = app()->console->cliEscape($cmd);
        $result = shell_exec($cmd);
        chdir($dir);
        return $result;
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

// -- Log取得

    /**
     * CommitIDを新しい順で取得
     */
    public function getCommits ()
    {
        $result = $this->cmd(array("git","log","--format=%h"));
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
            if (preg_match('!^refs/heads/([^/]+?)$!',$match)) {
                $branches[] = $match[1];
            } elseif (preg_match('!^refs/remotes/(([^/]+?)/([^/]+?))$!',$match)) {
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

// -- 現在のBranchに対する操作

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
}
