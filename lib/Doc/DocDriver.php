<?php
namespace R\Lib\Doc;

class DocDriver
{
    /**
     * DebugDriverからのIntercept起動を行う
     */
    public function runIntercept()
    {
        $out = $this->run($_REQUEST);
        print '<div style="background-color:#000; color:white; padding:20px; margin:0px;">';
        print nl2br(str_replace(' ','&nbsp;',htmlspecialchars($out)));
        print "</div>";
        exit;
    }
    public function run($params)
    {
        // rootの決定
        $root_dir = constant("R_APP_ROOT_DIR")."/docs";
        // doc_config.phpの読み込み
        $config = include($root_dir."/doc_config.php");
        // インスタンス生成
        $runner = new DocRunner($config);
        // help表示
        if ($params["all"]) {
            $doc_files = $runner->runAll();
            $out .= " * write docs: ALL"."\n";
            foreach ($doc_files as $doc_name=>$files) {
                $out .= "   - ".$doc_name."\n";
                foreach ($files as $file) {
                    $out .= "     - ".str_replace($config["output_dir"], '', $file)."\n";
                }
            }
        } elseif($doc_name = $params["doc"]) {
            $files = $runner->run($doc_name);
            $out .= " * write docs: ".$doc_name."\n";
            foreach ($files as $file) {
                $out .= "   - ".str_replace($config["output_dir"], '', $file)."\n";
            }
        } else {
            $out .= " * available params:\n";
            $out .= "   - doc = specific_doc_name ... spec doc by name\n";
            $out .= "   - all ... without spec\n";
            $out .= "   - help ... show this message\n";
            $out .= "\n";
            $out .= " * available docs:\n";
            foreach ($runner->getDocNames() as $doc_name) {
                $out .= "   - ".$doc_name."\n";
            }
            $out .= "\n";
        }
        return $out;
    }
}
