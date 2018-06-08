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
            "farm_mark_find" => array("--grep=<FARM>"),
            "root_mark" => array("-m", "<FARM><INIT>"),
            "root_find" => array("--grep=<FARM><INIT>"),
        );
        $this->gitApp = new FarmGitRepositry($this->getConfig("app_root_dir"), array(
            "prompt" => "APP  ",
        ));
        $this->gitWork = new FarmGitRepositry($this->getConfig("work_root_dir"), array(
            "prompt" => "WORK ",
        ));
    }
    public function getConfig($key)
    {
        return $this->config[$key];
    }
    public function cmdWork($cmd, $options=array())
    {
        return $this->gitWork->cmd($cmd, $options);
    }
    public function cmdApp($cmd, $options=array())
    {
        return $this->gitApp->cmd($cmd, $options);
    }
    public function getPipe()
    {
        return \R\Lib\Util\Cli::noEscape("|");
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
            // git rev-parse --abbrev-ref HEAD
            $this->config["develop_branch"] = $this->cmdApp(array(
                "git", "rev-parse", "--abbrev-ref", "HEAD"));
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
            "+".$this->getConfig("develop_branch").":".$this->getConfig("develop_branch")));
        //  事後確認、DEVELOPブランチが作成できていなければエラー
        if ( ! $this->cmdWork(array("git", "branch", "--list", $this->getConfig("develop_branch")))) {
            report_error("WORK環境への初期同期エラー");
        }

        // # JOINTコミットとROOTコミットを用意して、FARMブランチを作成
        // 事前準備、FARMブランチがあれば削除
        if ($this->cmdWork(array("git", "branch", "--list", $this->getConfig("farm_branch")))) {
            $this->cmdWork(array("git", "checkout", "--detach"));
            $this->cmdWork(array("git", "branch", "-D", $this->getConfig("farm_branch")));
        }
        // DEVELOPブランチからFARMマークされた直近のコミット（JOINTコミット）を探す
        // JOINT=` git rev-list --grep="<FARM>" develop | head -n1 `
        if (is_array($this->getConfig("farm_mark_find"))) {
            $joint_commit = $this->cmdWork(array("git", "rev-list",
                $this->getConfig("farm_mark_find"), $this->getConfig("develop_branch"),
                "--", $this->getPipe(), "head", "-n1"));
        } elseif (is_callable($this->getConfig("farm_mark_find"))) {
            $joint_commit = call_user_func($this->getConfig("farm_mark_find"), $this);
        }
        // JOINTコミットがあれば、そこからROOTコミットを探索する
        if ($joint_commit) {
            // ROOTコミットを探す
            if (is_array($this->getConfig("root_find"))) {
                // ROOT=` git rev-list --grep="<FARM>" $JOINT | tail -n1 `
                $root_commit = $this->cmdWork(array("git", "rev-list",
                    $this->getConfig("root_find"), $joint_commit,
                    "--", $this->getPipe(), "tail", "-n1"));
            } elseif (is_callable($this->getConfig("root_find"))) {
                $root_commit = call_user_func($this->getConfig("root_find"), $this, $joint_commit);
            // ROOTコミットを探索しない場合、JOINTコミットを代用する
            } else {
                $root_commit = $joint_commit;
            }
            // ROOTコミットが見つからなければエラー
            if ( ! $root_commit) {
                report("ROOTコミットが見つかりません");
            }
            // ファイルの状態をROOTコミットにして、JOINTコミットをCO
            // git checkout -b farm/build $ROOT
            $this->cmdWork(array("git", "checkout", "-b",
                $this->getConfig("farm_branch"), $root_commit));
            // git reset --soft $JOINT
            $this->cmdWork(array("git", "reset", "--soft", $joint_commit));
        // JOINTコミットがなければ、空白のコミットを作成する
        } else {
            // 作業コピーを空白にしてROOTコミットを作成する
            $this->cmdWork(array("git", "checkout", "--orphan", $this->getConfig("farm_branch")));
            // git reset --hard
            $this->cmdWork(array("git", "reset", "--hard"));
            // git reset commit --allow-empty -m "<FARM>"
            $this->cmdWork(array("git", "commit", "--allow-empty", $this->getConfig("root_mark")));
        }
        // 事後確認、FARMブランチの作成が出来ていない場合エラー
        if ( ! $this->cmdWork(array("git", "branch", "--list", $this->getConfig("farm_branch")))) {
            report_error("FARMブランチの作成エラー");
        }
    }
    /**
     * FARMコミットの作成
     */
    public function apply()
    {
        // 事前確認、WORK環境にFARMブランチがCOされていなければエラー
        // git rev-parse --abbrev-ref HEAD
        $current_branch = $this->cmdWork(array("git", "rev-parse", "--abbrev-ref", "HEAD"));
        if ($current_branch != $this->getConfig("farm_branch")) {
            report_error("WORK環境にFARMブランチがCOされていません");
        }
        // # FARMマークコミットを作成する
        // git add -A
        $this->cmdWork(array("git", "add", "-A"));
        // git commit -m "<FARM>"
        $this->cmdWork(array("git", "commit", $this->getConfig("farm_mark")));

        // # WORK環境のFARMブランチをFetchする
        $this->cmdApp(array("git", "fetch", "-f", $this->getConfig("work_root_dir"),
            "+".$this->getConfig("farm_branch").":".$this->getConfig("farm_branch")));
        //  事後確認、FARMブランチが作成できていなければエラー
        if ( ! $this->cmdApp(array("git", "branch", "--list", $this->getConfig("farm_branch")))) {
            report_error("作成済みFARMブランチの同期エラー");
        }
    }
    /**
     * APP環境へのMerge
     */
    public function merge()
    {
        // # DEVELOPブランチをCOして、FARMブランチをマージする
        // git rev-parse --abbrev-ref HEAD
        $current_branch = $this->cmdApp(array("git", "rev-parse", "--abbrev-ref", "HEAD"));
        if ($current_branch != $this->getConfig("develop_branch")) {
            // git checkout develop
            $this->cmdApp(array("git", "checkout", $this->getConfig("develop_branch")));
        }
        // git2.9以降では無関係のブランチのマージで追加オプションが必要
        $version_string = $this->cmdApp(array("git", "--version"));
        $version = preg_match('!version (\d+\.\d+)!', $version_string, $_) ? $_[1] : "2.14";
        if (version_compare($version, "2.9.0")>=0) {
            $append_option = array("--allow-unrelated-histories");
        }
        // git merge --no-commit --allow-unrelated-histories farm/build
        $this->cmdApp(array("git", "merge",
            "--no-commit", $append_option, $this->getConfig("farm_branch")));
    }
}
