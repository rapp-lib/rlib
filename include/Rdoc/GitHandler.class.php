<?php

/**
 *
 */
class GitHandler {

    protected $git_dir;
    protected $git_bin;

    /**
     *
     */
    public function __construct ($git_dir, $config=array()) {

        $this->git_dir =$git_dir;

        $this->git_bin =$config["git_bin"]
                ? $config["git_bin"]
                : "git";
    }

    /**
     * gitコマンドの発行
     */
    public function git_cmd ($git_cmd) {

        $cmd =cli_escape(array($this->git_bin,$git_cmd,'2>&1'));

        // git_dir上でコマンド実行
        $chdir =chdir();
        chdir($this->git_dir);
        exec($cmd,$output,$result);
        chdir($chdir);

        return $output;
    }

    /**
     * checkout中のブランチの取得
     */
    public function get_current_branch () {

        $output =$this->git_cmd(array('branch'));
    }

    /**
     * レポジトリの状態がcleanであるか確認
     */
    public function check_clean () {

        $output =$this->git_cmd(array('status'));
    }
}