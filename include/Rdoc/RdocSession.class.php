<?php

//-------------------------------------
//
class RdocSession {

    protected $tmp_dir ="";
    protected $history ="";
    protected $options =array();

    //-------------------------------------
    // セッションの開始
    public function __construct ($options=array()) {

        $this->options =$options;
        $this->tmp_dir =registry("Path.tmp_dir")."/rdoc/";
        $this->history ="U".date("Ymd_His_").sprintf("%03d",rand(001,999));
    }

    //-------------------------------------
    // 空のファイルの作成
    public function touch_kindly (
            // 作成するファイル名
            $filename) {

        if ( ! file_exists(dirname($filename))) {

            $old_umask =umask(0);
            mkdir(dirname($filename),0775,true);
            umask($old_umask);
        }

        return touch($filename) && chmod($filename,0664);
    }

    //-------------------------------------
    // テンプレートファイルの参照
    public function arch_template (
            // テンプレートPHPファイル名
            $src_file,
            // 書き出し先ファイル名
            $dest_file,
            // テンプレート変数
            $assign_vars=array()) {

        if ( ! is_readable($src_file)) {

            return false;
        }

        extract($assign_vars,EXTR_REFS);
        ob_start();
        include($src_file);
        $src =ob_get_clean();

        $src =str_replace('<!?','<?',$src);

        return $this->deploy_src($dest_file,$src);
    }

    //-------------------------------------
    // 履歴ファイルへの追記
    public function append_history (
            // 操作モード（"memo"以外はRollback時の動作に関わる）
            $mode,
            // 操作元ファイル
            $src="",
            // 操作先ファイル
            $dest="") {

        // 履歴ファイルへの追記
        $history_file =$this->tmp_dir."/history/".$this->history;

        if ($this->touch_kindly($history_file)) {

            $msg =$mode."\n".$src."\n".$dest."\n";
            file_put_contents($history_file,$msg,FILE_APPEND);
        }
    }

    //-------------------------------------
    // ファイルの待避（バックアップを取って削除）
    public function backup_file (
            // 待避させたいファイル名
            $dest_file) {

        $webapp_dir =registry("Path.webapp_dir");
        $backup_file =preg_replace(
                '!^'.preg_quote($webapp_dir).'!',$this->tmp_dir."/backup/",$dest_file)
                .'-'.date("ymd_His");

        if ($this->touch_kindly($backup_file)) {

            rename($dest_file,$backup_file);
        }

        if (file_exists($backup_file)) {

            // 履歴ファイルへの追記
            $this->append_history("backup",$dest_file,$backup_file);

        } else {

            report_warning("Backup failur.",array(
                "dest_file" =>$dest_file,
                "tmp_dir" =>$this->tmp_dir,
                "backup_file" =>$backup_file,
                "tmp_dir_is_writable" =>is_writable($this->tmp_dir),
                "backup_dir_is_writable" =>is_writable(dirname($backup_file)),
            ));

            return false;
        }

        return true;
    }

    //-------------------------------------
    // ファイルへのテキストの書き込み
    public function deploy_src (
            // 書き込み先ファイル名
            $dest_file,
            // ファイルに書き込むテキスト/またはcallback
            $src) {

        // 自動展開機能がOFFであれば設定によらず勝手にファイルの上書きを行わない
        if ( ! registry("Config.auto_deploy")) {

            $replace_pattern ='!^'.preg_quote(registry("Path.webapp_dir"),'!').'!';

            if ( ! preg_match($replace_pattern,$dest_file)) {

                return false;
            }

            $dest_file =preg_replace(
                    $replace_pattern,
                    $this->tmp_dir."/deploy/".$this->history."/",
                    $dest_file);
        }

        // 既存ファイルのバックアップ
        if (file_exists($dest_file)) {

            // 同一性チェック
            if ($src !== null) {

                $src_dest =file_get_contents($dest_file);

                if ( ! is_callable($src) && crc32($src_dest) == crc32($src)) {

                    report("File not-changed.",array(
                        "dest_file" =>$dest_file,
                    ));

                    return true;
                }
            }

            // バックアップ
            if ( ! $this->backup_file($dest_file)) {

                return false;
            }
        }

        // 既存ファイルのチェック
        if (file_exists($dest_file)) {

            report_warning("Dest File exists",array(
                "dst_file" =>$dest_file,
                "same" =>is_string($src) ? md5(file_get_contents($dest_file)) == md5($src) : "unknown",
            ));

            return false;
        }

        // ファイルの書き込み
        if (($r_touch =$this->touch_kindly($dest_file))
                && ($r_writable =is_writable($dest_file))) {

            if (is_callable($src)) {

                call_user_func($src,$dest_file);

                report("Write-in file successfuly.",array(
                    "dest_file" =>$dest_file,
                    "src_callback" =>$src,
                ));

            } else {

                file_put_contents($dest_file,$src);

                report("Write-in file successfuly.",array(
                    "dest_file" =>$dest_file,
                ));
                print "<pre>".htmlspecialchars($src)."</pre>";
            }

            // 履歴ファイルへの追記
            $this->append_history("create","",$dest_file);

        } else {

            report_warning("Fail to write-into file.",array(
                "dest_file" =>$dest_file,
                "r_touch" =>$r_touch,
                "r_writable" =>$r_writable,
                "r_write" =>$r_write,
            ));
            return false;
        }

        return true;
    }
}