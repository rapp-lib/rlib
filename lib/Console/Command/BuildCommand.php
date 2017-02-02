<?php
namespace R\Lib\Console\Command;

use R\Lib\Console\Command;
use R\Lib\Core\Exception\ResponseException;

/*
rexe build.init (-b master)
    createConfigFile()
    createBuildBranch($args["branch"])
rexe build.make (config/schema.config.csv)
    $tmp_branch = createTmpBranch(); // "build-tmp-001"
    >   $current_branch = getCurrentBranch();
    git checkout -b $tmp_branch build-master
    $error = deployAll();
    if $error then disableTmpBranch(); // getLatestTmpBranch()不可にする
    git add -A ; git commit -m "build.make $tmp_branch"
    git checkout $current_branch
    git merge --no-commit $tmp_branch
rexe build.apply
    $tmp_branch = getLatestTmpBranch();
    $current_branch = getCurrentBranch();
    git add -A ; git commit -m "build.apply $tmp_branch"
    git checkout build-master
    git merge --ff-only $tmp_branch
rexe build.clean
    cleanTmpBranches()
*/
class BuildCommand extends Command
{
    protected function init ()
    {
        $this->git = new GitRepositry(constant("R_APP_ROOT_DIR"));
        $this->config = array();
    }
    public function act_make ()
    {
        // レポジトリの状態の確認
        if ($changes = $this->git->getChanges()) {
            report_error("Gitレポジトリがcleanではありません",array(
                "changes" => $changes,
            ));
        }
        // buildブランチがなければ作成
        if ( ! in_array("build",$this->git->getBranches())) {
            $this->git->createBranch("build");
        }
        $this->config["develop_branch"] = $this->git->getCurrentBranch();
        try {
            app()->builder->start();
        } catch (ResponseException $e) {
            report_warning("Builderの実行中にエラーがありました",array(
                "exceptions"
            ));
        }
        $patch_file = "./tmp/builder/patches/".date("YmdHis").".patch";
        $this->git->createPatch($patch_file);
        // git checkout $current_branch
        $this->git->checkout($this->config["develop_branch"]);
        // git merge --no-commit $tmp_branch
        $this->git->merge($this->config["develop_branch"],array("--no-commit"));
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
    public function createBranch ($branch)
    {
        $branch = $this->cmd(array("git","branch",$branch));
        return $branch;
    }
}
