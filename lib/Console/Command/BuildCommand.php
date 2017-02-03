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
            "build_log_id" => "build-".date("Ymd-His"),
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
        $b2_commits = $this->git->getCommits($this->config["branch_b2"]);
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
            $schema_csv_file = constant("R_APP_ROOT_DIR")."/config/schema.config.".$this->config["build_log_id"].".csv";
            $skel_dir = constant("R_LIB_ROOT_DIR")."/assets/builder/skel";
            $deploy_dir = $current_dir = constant("R_APP_ROOT_DIR");
            $work_dir = constant("R_APP_ROOT_DIR")."/tmp/builder/work-".date("Ymd-his");
            // dブランチからCSVをコピーする
            $csv_data = $this->git->cmd(array("git","show",$this->config["branch_d"].":config/schema.config.csv"));
            util("File")->write($schema_csv_file,$csv_data);
            // Builderを作成
            $schema = app()->builder(array(
                "current_dir" => $current_dir,
                "deploy_dir" => $deploy_dir,
                "work_dir" => $work_dir,
                "show_source" => true,
            ));
            $schema->addSkel($skel_dir);
            $schema->initFromSchemaCsv($schema_csv_file);
            $schema->deploy(true);

        } catch (ResponseException $e) {
            report_warning("Builderの実行中にエラーがありました",array(
                "exceptions" => $e,
            ));
        }
        //     addCommitAll msg
        $this->git->addCommitAll("build ".$this->config["build_log_id"]);
        //     checkout d
        $this->git->checkout($this->config["branch_d"]);
        //     merge b2 --no-commit
        $this->git->cmd(array("git","merge","--no-ff","--no-commit",$this->config["branch_b2"]));
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
        app()->console->output("> ".$cmd."\n");
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
