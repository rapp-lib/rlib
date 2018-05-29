<?php
namespace R\Lib\Farm;

class FarmEngine
{
    private $config = array();
    private $git;
    public function __construct($config)
    {
        $this->config = $config + array(
            "app_root_dir" => constant("R_APP_ROOT_DIR"),
            "farm_dirname" => "devel/farm",
            "develop_branch" => null,
            "fallback_branch" => "develop",
            "farm_branch" => "farm/build",
            "farm_mark" => array("-m", "<FARM>"),
            "farm_mark_find" => array("--grep=", "<FARM>"),
        );
        $this->git = new FarmGitRepositry($this->getConfig("app_root_dir"));
    }
    public function getConfig($key)
    {
        return $this->config[$key];
    }
    public function cmd($cmd, $options=array())
    {
        return $this->git->cmd($cmd, $options);
    }
    /**
     * 現在のブランチを取得
     */
    private function getCurrentBranch()
    {
        return $this->cmd(array("git", "rev-parse", "--abbrev-ref", "HEAD"));
    }
    /**
     * FARMブランチがあれば削除
     */
    private function deleteFarmBranch()
    {
        // FARMブランチがあれば削除
        if ($this->cmd(array("git", "branch", "--list", $this->getConfig("farm_branch")))) {
            // FARMブランチをCOしている場合、DEVELOPブランチをCO
            if ($this->getCurrentBranch() == $this->getConfig("farm_branch")) {
                // git checkout develop
                $this->cmd(array("git", "checkout", $this->getConfig("develop_branch")));
            }
            // git branch -D farm/build
            $this->cmd(array("git", "branch", "-D", $this->getConfig("farm_branch")));
        }
    }
    /**
     * # 事前状態の確認
     * 前提: なし
     * 結果: 処理実行可能な状態
     */
    public function checkState()
    {
        // DEVELOPブランチが指定されていない場合、HEADをDEVELOPブランチとする
        if ( ! $this->getConfig("develop_branch")) {
            $this->config["develop_branch"] = $this->getCurrentBranch();
        }
        // Detach HEADをCOしていた場合にエラー停止
        if ( ! $this->getConfig("develop_branch")) {
            report_error("展開先ブランチが指定されていません",array(
                "develop_branch" => $this->getConfig("develop_branch"),
            ));
        }
        // DEVELOPとFARMブランチが同じであればFALLBACKブランチに切り替えてエラー停止
        if ($this->getConfig("develop_branch") == $this->getConfig("farm_branch")) {
            $this->cleanup();
            report_error("FARMブランチからFALLBACKブランチに切り替えます",array(
                "fallback_branch" => $this->getConfig("fallback_branch"),
            ));
        }
        // 作業コピーがCleanでなければエラー停止
        if ($changes = $this->cmd(array("git","status","-s"))) {
            report_error("作業コピーがcleanではありません",array(
                "changes" => $changes,
            ));
        }
    }
    /**
     * # 生成ブランチの用意
     * 前提: 処理開始可能な状態である
     * 結果: FARMブランチがチェックアウトされた状態になる
     */
    public function prepareFarmBranch()
    {
        // FARMブランチが残っていれば削除
        $this->deleteFarmBranch();

        // FARMマークされた直近のコミット（JOINTコミット）を探す
        // JOINT=` git rev-list --grep="<FARM>" develop | head -n1 `
        $joint_commit = $this->cmd(array("git", "rev-list",
            $this->getConfig("farm_mark_find"), $this->getConfig("develop_branch"),
            "--", $this->git->noEscape("|"), "head", "-n1"));

        // JOINTコミットがあれば、FARMブランチとしてCO
        if ($joint_commit) {
            // 	git checkout -b farm/build $JOINT
            $this->cmd(array("git", "checkout", "-b", $this->getConfig("farm_branch"), $joint_commit));
        // JOINTコミットがなければ、FARMブランチとして作成
        } else {
            // git checkout --orphan farm/build
            $this->cmd(array("git", "checkout", "--orphan", $this->getConfig("farm_branch")));
            // git reset --hard
            $this->cmd(array("git", "reset", "--hard"));
            // git commit --allow-empty -m "<FARM>"
            $this->cmd(array("git", "commit", "--allow-empty", $this->getConfig("farm_mark")));
        }
    }
    /**
     * # 生成ブランチ上のファイル状態を準備
     * 前提: なし
     * 結果: COPYがROOTコミットの状態になる
     */
    public function prepareFarmBranchCopy()
    {
        // 最も古いFARMマークされたコミット（ROOTコミット）を探す
        // ROOT=` git rev-list --grep="<FARM>" farm/build | tail -n1 `
        $root_commit = $this->cmd(array("git", "rev-list",
            $this->getConfig("farm_mark_find"), $this->getConfig("farm_branch"),
            "--", $this->git->noEscape("|"), "tail", "-n1"));

        // 作業コピーをROOTコミットの状態にする
        // git reset $ROOT
        $this->cmd(array("git", "reset", $root_commit));
        // git clean -f -- devel/farm
        $this->cmd(array("git", "clean", "-f"));
    }
    /**
     * DEVELOPブランチ上のFARM_DIRを展開
     * 前提: なし
     * 結果: DEVELOPブランチ上のFARM_DIRが展開された状態になる
     */
    public function prepareFarmDir()
    {
        // # DEVELOPブランチ上のFARM_DIRを展開
        // git checkout develop -- devel/farm
        $this->cmd(array("git", "checkout",
            $this->getConfig("develop_branch"), "--", $this->getConfig("farm_dirname")));
    }
    /**
     * FARM_DIRを展開前の状態に戻す
     * 前提: なし
     * 結果: FARM_DIRがHEADの状態に戻る
     */
    public function cleanFarmDir()
    {
        // # FARM_DIRを展開前の状態に戻す
        // git reset -- devel/farm
        $this->cmd(array("git", "reset", "--", $this->getConfig("farm_dirname")));
        // git clean -f -- devel/farm
        $this->cmd(array("git", "clean", "-f", $this->getConfig("farm_dirname")));
    }
    /**
     * # FARMマークコミットを作成してDEVELOPブランチにマージ
     * 前提: FARMブランチがCOされている
     * 結果: FARMコミットをマージ中のDEVELOPブランチがCOされた状態になる
     */
    public function mergeFarmBranch()
    {
        // # FARMマークコミットを作成する
        // git add -A
        $this->cmd(array("git", "add", "-A"));
        // git commit -m"<FARM>"
        $this->cmd(array("git", "commit", $this->getConfig("farm_mark")));

        // # DEVELOPブランチをCOして、FARMブランチをマージする
        // git checkout develop
        $this->cmd(array("git", "checkout", $this->getConfig("develop_branch")));

        // PHPバージョン2.9以降では無関係なブランチのマージを通すオプションを追加
        $version_string = $this->cmd(array("git", "--version"));
        $commit_options = array();
        if (preg_match('!^git version ([\d\.]+)!', $version_string, $_)) {
            if (version_compare($_[1], 2.9, ">=")) $commit_options[] = "--allow-unrelated-histories";
        }

        // git merge --no-commit --allow-unrelated-histories farm/build
        $this->cmd(array("git", "merge", "--no-commit", $commit_options, $this->getConfig("farm_branch")));
    }
    /**
     * # 処理を終了する前の処理
     * 前提: なし
     * 結果: 処理開始前の状態
     */
    public function cleanup()
    {
        // トラブルによりまだFARMブランチを参照している場合、DEVELOPブランチに切り替える
        if ($this->getCurrentBranch() == $this->getConfig("farm_branch")) {
            // git add -A; git reset --hard; git checkout develop
            $this->git->cmd(array("git", "add", "-A"));
            $this->git->cmd(array("git", "reset", "--hard"));
            if ($this->getConfig("develop_branch") == $this->getConfig("farm_branch")) {
                $this->git->cmd(array("git", "checkout", $this->getConfig("fallback_branch")));
            } else {
                $this->git->cmd(array("git", "checkout", $this->getConfig("develop_branch")));
            }
        }
        // FARMブランチを削除
        $this->deleteFarmBranch();
    }
}
