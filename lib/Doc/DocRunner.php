<?php
namespace R\Lib\Doc;

class DocRunner
{
    private $config;
    public function __construct($config)
    {
        $this->config = $config;
    }
    public function getDocNames()
    {
        return array_keys((array)$this->config["docs"]);
    }
    public function runAll()
    {
        $doc_files = array();
        foreach ($this->getDocNames() as $doc_name) {
            $doc_files[$doc_name] = $this->run($doc_name);
        }
        return $doc_files;
    }
    public function run($doc_name)
    {
        $doc = $this->config["docs"][$doc_name];
        $format_class = $doc["format"];
        $format = new $format_class();
        $files = $format->writeAll($this->config["output_dir"]."/".$doc_name);
        return $files;
    }
}
