<?php

    /**
     *
     */
    function rapper_mod_basic_deploy ($r) {
    
    }

class Rapper_Deploy_Basic {
    
    /**
     * ファイルの書き込み
     */
	public function fetch_template ($deployee) {
        
        $tmpl_file =$deployee["tmpl_file"];
        $dest_file =$deployee["dest_file"];
        
        // テンプレートファイルの読み込み
        $src =null;
        
        if ( ! is_readable($tmpl_file)) {
            
            report_error("テンプレートファイル(tmpl_file)読み込みエラー",array(
                "tmpl_file" =>$tmpl_file,
                "deployee" =>$deployee,
            ));
        }
        
        $r =$this;
        extract($vars,EXTR_REFS);
        
        ob_start();
        include($tmpl_file);
        $src =ob_get_clean();
        $src =str_replace('<!?','<?',$src);
    }
    
    /**
     * ファイルの書き込み
     */
	public function deploy_file ($deployee) {
		
        $webapp_file =$this->mod->config("webapp_dir")."/".$dest_path;
        $dest_file =$this->mod->config("deploy_dir")."/".$dest_path;
        
		// 同一性チェック
		if (file_exists($webapp_file)
			    && crc32(file_get_contents($webapp_file)) == crc32($src)) {
			
			report("Deploy中止:差分なし",array(
				"file" =>$webapp_file,
			));
			
			return true;
		}
		
        // 親dirの作成
		if ( ! file_exists(dirname($filename))) {
			
			$old_umask =umask(0);
			mkdir(dirname($filename),0775,true);
			umask($old_umask);
		}
        
		// ファイルの書き込み
		if (touch($dest_file) && chmod($dest_file,0664)
				&& is_writable($dest_file)
				&& file_put_contents($dest_file,$src)) {
			
			report("Deploy完了",array(
				"file" =>$dest_file,
			));
			print "<code>".sanitize($src)."</code>";
		
		} else {
			
			report_warning("Deploy失敗",array(
				"file" =>$dest_file,
			));
            
			return false;
		}
		
		return true;
	}
}