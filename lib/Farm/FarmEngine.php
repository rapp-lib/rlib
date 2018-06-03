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
            "work_root_dir" => constant("R_APP_ROOT_DIR")."/tmp/farm/work",
            "farm_dirname" => "devel/farm",
            "develop_branch" => false,
            "farm_branch" => "farm/build",
            "farm_mark" => array("-m", "<FARM>"),
            "farm_mark_find" => array("--grep=", "<FARM>"),
        );
        $this->gitApp = new FarmGitRepositry($this->getConfig("app_root_dir"));
        $this->gitWork = new FarmGitRepositry($this->getConfig("work_root_dir"));
    }
    public function getConfig($key)
    {
        return $this->config[$key];
    }
    public function cmdWork($cmd, $options=array())
    {
        $options["prompt"] = "WORK ";
        return $this->gitWork->cmd($cmd, $options);
    }
    public function cmdApp($cmd, $options=array())
    {
        $options["prompt"] = "APP  ";
        return $this->gitApp->cmd($cmd, $options);
    }
    public function getPipe()
    {
        return $this->gitWork->noEscape("|");
    }
    /**
     * 事前処理
     */
    public function prepare()
    {
        // # 事前処理、条件の確認
        // APP環境がCleanでなければエラー停止
        if ($changes = $this->cmdApp(array("git", "status", "-s"))) {
            report_error("作業コピーがcleanではありません",array(
                "changes" => $changes,
            ));
        }
        // DEVELOPブランチが指定されていない場合、APP環境のHEADをDEVELOPブランチとする
        if ( ! $this->getConfig("develop_branch")) {
            $this->config["develop_branch"] = $this->cmdApp(array(
                "git", "rev-parse", "--abbrev-ref", "HEAD"), array("quiet"=>true));
        }
        // 事後確認、DEVELOPブランチが無効である場合にエラー停止
        if ( ! $this->getConfig("develop_branch")) {
            report_error("DEVELOPブランチが指定されていません",array(
                "develop_branch" => $this->getConfig("develop_branch"),
            ));
        }

        // # WORK環境がなければ作成、初期化
        \R\Lib\Util\File::createDir($this->getConfig("work_root_dir"));
        if ( ! is_dir($this->getConfig("work_root_dir")."/.git")) {
            $this->cmdWork(array("git", "init"));
        }
        // WORK環境がCleanでなければリセットして続行
        if ($changes = $this->cmdWork(array("git","status","-s"))) {
            $this->cmdWork(array("git", "reset", "--hard"));
        }

        // # WORK環境をAPP環境から同期
        // WORK環境でAPP環境からDEVELOPブランチを取り込む
        $this->cmdWork(array("git", "fetch", "-f", $this->getConfig("app_root_dir"),
            "+".$this->getConfig("develop_branch").":".$this->getConfig("develop_branch"),
            $this->getConfig("app_dir")));
        //  事後確認、DEVELOPブランチが作成できていなければエラー
        if ( ! $this->cmdWork(array("git", "branch", "--list", $this->getConfig("develop_branch")))) {
            report_error("WORK環境の同期エラー");
        }

        // # JOINTコミットとROOTコミットを用意して、FARMブランチを作成
        // 事前準備、FARMブランチがあれば削除
        if ($this->cmdWork(array("git", "branch", "--list", $this->getConfig("farm_branch")))) {
            $this->cmdWork(array("git", "branch", "-D", $this->getConfig("farm_branch")));
        }
        // DEVELOPブランチからFARMマークされた直近のコミット（JOINTコミット）を探す
        // JOINT=` git rev-list --grep="<FARM>" develop | head -n1 `
        $joint_commit = $this->cmdWork(array("git", "rev-list",
            $this->getConfig("farm_mark_find"), $this->getConfig("develop_branch"),
            "--", $this->getPipe(), "head", "-n1"));
        // JOINTコミットがあれば、そこからROOTコミットを探索する
        if ($joint_commit) {
            // 最も古いFARMマークされたコミット（ROOTコミット）を探す
            // ROOT=` git rev-list --grep="<FARM>" $JOINT | tail -n1 `
            $root_commit = $this->cmdWork(array("git", "rev-list",
                $this->getConfig("farm_mark_find"), $joint_commit,
                "--", $this->getPipe(), "tail", "-n1"));
            // ファイルの状態をROOTコミットにして、JOINTコミットをCO
            // git checkout -b farm/build $ROOT
            $this->cmdWork(array("git", "checkout", "-b", $this->getConfig("farm_branch")));
            // git reset --soft $JOINT
            $this->cmdWork(array("git", "reset", "--hard", $root_commit));
        // JOINTコミットがなければ、空白のコミットを作成する
        } else {
            // 作業コピーを空白にしてROOTコミットを作成する
            $this->cmdWork(array("git", "checkout", "--orphan", $this->getConfig("farm_branch")));
            // git reset --hard
            $this->cmdWork(array("git", "reset", "--hard"));
            // git reset commit --allow-empty -m "<FARM>"
            $this->cmdWork(array("git", "commit", "--allow-empty", $this->getConfig("farm_mark")));
        }
        // 事後確認、FARMブランチの作成が出来ていない場合エラー
        if ( ! $this->cmdWork(array("git", "branch", "--list", $this->getConfig("farm_branch")))) {
            report_error("FARMブランチの作成エラー");
        }

        // // # DEVELOPブランチ上のFARM_DIRを展開
        // // git checkout develop -- devel/farm
        // $this->cmd(array("git", "checkout",
        //     $this->getConfig("develop_branch"), "--", $this->getConfig("farm_dirname")));
        // // 事後確認、FARM_DIRの作成が出来ていない場合エラー
        // if ( ! is_dir($this->getConfig("work_root_dir")."/".$this->getConfig("farm_dirname"))) {
        //     report_error("FARM_DIRの作成エラー");
        // }
    }
    /**
     * 事後処理
     */
    public function apply()
    {
        // # FARM_DIR以外のファイルの状態からFARMマークコミットを作成する
        // git add -A
        $this->cmdWork(array("git", "add", "-A"));
        // // git reset -- devel/farm
        // $this->cmdWork(array("git", "reset", "--", $this->getConfig("farm_dirname")));
        // git commit -m "<FARM>"
        $this->cmdWork(array("git", "commit", $this->getConfig("farm_mark")));

        // # DEVELOPブランチをCOして、FARMブランチをマージする
        // git checkout develop
        $this->cmdApp(array("git", "checkout", $this->getConfig("develop_branch")));
        // git pull tmp/farm/work farm/build
        $this->cmdApp(array("git", "pull", "--no-commit", "--allow-unrelated-histories",
            $this->getConfig("work_root_dir"), $this->getConfig("farm_branch")));
    }
}
