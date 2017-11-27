<?php
namespace R\Lib\Console\Command;

use R\Lib\Console\Command;
use R\Lib\Util\GitRepositry;

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
        register_shutdown_function(array($this, "onShutdown"));
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
        $current_branch = $this->git->getCurrentBranch();
        if ($current_branch != $this->config["branch_d"]) {
            report_error($this->config["branch_d"]."ブランチがCheckoutされていません",array(
                "current_branch" => $current_branch,
                "branch_d" => $this->config["branch_d"],
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
        // dブランチからCSVをコピーする
        $work_dir = constant("R_APP_ROOT_DIR")."/tmp/builder/log/".$this->config["build_log_id"];
        $schema_csv_file = $work_dir."/schema.config.csv";
        $csv_data = $this->git->cmd(array("git","show",$this->config["branch_d"].":config/schema.config.csv"));
        \R\Lib\Util\File::write($schema_csv_file,$csv_data);
        // CSVを読み込む
        $schema_loader = new \R\Lib\Builder\SchemaCsvLoader;
        $schema_data = $schema_loader->load($schema_csv_file);
        // SchemaElementを作成
        $schema = new \R\Lib\Builder\Element\SchemaElement();
        $schema->addSkel(constant("R_LIB_ROOT_DIR")."/assets/builder/skel");
        $schema->loadSchemaData($schema_data);
        $schema->registerDeployCallback(function($deploy_name, $source){
            $deploy_file = constant("R_APP_ROOT_DIR")."/".$deploy_name;
            $status = "create";
            if (file_exists($deploy_file)) {
                $current_source = file_get_contents($deploy_file);
                $status = crc32($current_source)==crc32($source) ? "nochange" : "modify";
            }
            \R\Lib\Util\File::write($deploy_file, $source);
            if ($status != "nochange") {
                print "Deploy ".$status." ".$deploy_name."\n";
            }
        });

        // apply
        //     checkout b2
        $this->git->checkout($this->config["branch_b2"]);
        //     deploy
        $schema->deploy(true);
        //     git add -A; git commit -m $build_log_id
        $this->git->addCommitAll("build ".$this->config["build_log_id"]);
        //     git checkout d
        $this->git->checkout($this->config["branch_d"]);
        //     git merge b2 --no-commit --no-ff # コミットしない指定
        $this->git->cmd(array("git","merge","--no-ff","--no-commit",$this->config["branch_b2"]));
        //     git status
        $this->git->cmd(array("git","status"));
    }

    /**
     * エラー停止時の処理を実行する
     */
    public function onShutdown ()
    {
        if (\R\Lib\Report\ReportDriver::isFatalPhpErrorCode(error_get_last())) {
            $current_branch = $this->git->getCurrentBranch();
            // ブランチがb1/b2でエラー停止した場合
            if ($current_branch == $this->config["branch_b1"]
                || $current_branch == $this->config["branch_d2"]) {
                //     git add -A
                $this->git->cmd(array("git","add","-A"));
                //     git reset --hard
                $this->git->cmd(array("git","reset","--hard"));
                //     git checkout d
                $this->git->checkout($this->config["branch_d"]);
            }
        }
    }
}
