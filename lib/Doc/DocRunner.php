<?php
namespace R\Lib\Doc;

class DocRunner
{
    private $config;
    public function __construct($config)
    {
        $this->config = $config;
    }
    public function overwriteConfig()
    {
        app()->config((array)$this->config["overwrite_config"]);
    }
    public function getDocNames()
    {
        return array_keys((array)$this->config["docs"]);
    }
    public function run($params)
    {
        $result = array();
        foreach ($this->getDocNames() as $doc_name) {
            if ($params["doc"] && $params["doc"]!="all" && $params["doc"]!=$doc_name) continue;
            // 設定の読み込み
            $doc = $this->config["docs"][$doc_name];
            $writer_class = $doc["writer"];
            // Formatからコンテンツを作成
            $format_class = $doc["format"];
            $format = new $format_class($doc["format_config"]);
            $contents = $format->getContents();
            foreach ($contents as $content_name=>$content) {
                // Writerを作成してファイルに書き込む
                $file = $this->config["output_dir"]."/".$doc_name."/".$content_name;
                $writer = new $writer_class($content, $doc["writer_config"]);
                $writer->write($file);
                $result[$doc_name][$content_name] = array(
                    "file"=>$file,
                    "preview"=>$writer->preview(),
                );
            }
        }
        return $result;
    }
}
