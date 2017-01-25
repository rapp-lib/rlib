<?php
namespace R\Lib\Core\Provider;

use R\Lib\Core\Contract\Provider;

class ResponseFactory implements Provider
{
    /**
     * 応答の作成
     */
    public function output ($output)
    {
        $response = app()->make("response", array($output));
        return $response;
    }
    /**
     * View応答の作成
     */
    public function view ($file, $vars=array(), $output=array())
    {
        $output["type"] = "view";
        $output["file"] = $vars;
        $output["vars"] = $vars;
        return $this->output($output);
    }
    /**
     * JSON応答の作成
     */
    public function json ($vars, $output=array())
    {
        $output["type"] = "json";
        $output["vars"] = $vars;
        return $this->output($output);
    }
    /**
     * ファイルダウンロード応答の作成
     */
    public function download ($file, $output=array())
    {
        $output["type"] = "download";
        $output["file"] = "file";
        return $this->output($output);
    }
    /**
     * 転送応答の作成
     */
    public function redirect ($url, $url_params=array(), $output=array())
    {
        $output["type"] = "redirect";
        $output["url"] = $url;
        $output["url_params"] = $url_params;
        return $this->output($output);
    }
    /**
     * エラー応答の作成
     */
    public function error ($message, $code, $output=array())
    {
        $output["type"] = "error";
        $output["message"] = $message;
        $output["code"] = $code;
        return $this->output($output);
    }
}
