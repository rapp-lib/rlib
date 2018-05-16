<?php
namespace R\Lib\Doc;

class DocDriver
{
    /**
     * DebugDriverからのIntercept起動を行う
     */
    public function runIntercept()
    {
        $result = $this->run($_REQUEST);
        print '<div style="background-color:#000; color:white; padding:20px; margin:0px;">';
        print nl2br(str_replace(' ','&nbsp;',htmlspecialchars($result["output"])));
        print "</div>";
        foreach ((array)$result["preview"] as $file=>$html) {
            print '<h2>'.$file.'</h2><div>'.$html."</div>";
        }
        exit;
    }
    public function run($params)
    {
        // rootの決定
        $root_dir = constant("R_DEV_ROOT_DIR")."/docs";
        // doc_config.phpの読み込み
        $config = include($root_dir."/doc_config.php");
        // インスタンス生成
        $runner = new DocRunner($config);
        // 設定値の上書き
        $runner->overwriteConfig();
        // help表示
        if ( ! $params["doc"]) {
            $out .= " * available params:\n";
            $out .= "   - doc = specific_doc_name | all ... spec doc by name\n";
            $out .= "\n";
            $out .= " * available docs:\n";
            foreach ($runner->getDocNames() as $doc_name) {
                $out .= "   - ".$doc_name."\n";
            }
            $out .= "\n";
        } else {
            $result = $runner->run($params);
            $out .= " * write docs"."\n";
            foreach ($result as $doc_name=>$contents) {
                if ( ! $contents) continue;
                $out .= "   - ".$doc_name."\n";
                foreach ($contents as $content_name=>$content) {
                    $out .= "     - ".$content_name."\n";
                    $preview[$doc_name."/".$content_name] = $content["preview"];
                }
            }
        }
        return array("output"=>$out, "preview"=>$preview);
    }
}
