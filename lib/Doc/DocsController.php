<?php
namespace R\Lib\Doc;
use R\Lib\Http\HttpController;

class DocsController extends HttpController
{
    public function act_index()
    {
        // doc_config.phpの読み込み
        $config_file = constant("R_APP_ROOT_DIR")."/devel/docs/doc_config.php";
        $config = include($config_file);
        foreach ((array)$config["overwrite_config"] as $k=>$v) app()->config[$k] = $v;
        $docs = $config["docs"];
        foreach ($docs as $doc_name=> & $doc) {
            $format_class = $doc["format"];
            $format = new $format_class((array)$doc["format_config"]);
            $doc["format"] = $format;
            try {
                foreach ($format->getContents() as $content_name=>$content) {
                    $doc["contents"][$content_name]["content"] = $content;
                }
            } catch (\Exception $e) {
                $doc["error"] = $e->getMessage();
                if ($e instanceof \R\Lib\Report\HandlableError) {
                    report_warning("Doc Contentsエラー", array(
                        "doc_name"=>$doc_name,
                        "message"=>$e->getMessage(),
                        "params"=>$e->getparams(),
                    ));
                }
            }
        }
        $this->vars["docs"] = $docs;
        // Download/Preview処理
        if ($this->input["content"] && $this->input["action"]) {
            list($doc_name, $content_name) = explode('::', $this->input["content"]);
            $content = $docs[$doc_name]["contents"][$content_name]["content"];
            if ( ! $content) return app()->http->response("notfound");
            // Writer作成
            $writer_class = $doc["writer"];
            $writer = new $writer_class($content, $doc["writer_config"]);
            // Download
            if ($this->input["action"]==="download") {
                return $writer->getDownloadResponse($this->input["content"]);
            // Preview
            } elseif ($this->input["action"]==="preview") {
                $this->vars["preview"] = array(
                    "title"=>$this->input["content"],
                    "html"=>$writer->getPreviewHtml(),
                );
            }
        }
    }
    private $template = "index.html";
    protected function getTemplateFile()
    {
        return constant("R_LIB_ROOT_DIR")."/assets/docs/html/".$this->template;
    }
    protected function getTemplateVars()
    {
        $vars = parent::getTemplateVars();
        $vars["header_file"] = constant("R_LIB_ROOT_DIR")."/assets/tester/html/header.html";
        $vars["footer_file"] = constant("R_LIB_ROOT_DIR")."/assets/tester/html/footer.html";
        return $vars;
    }
}
