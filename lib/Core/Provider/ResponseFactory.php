<?php
namespace R\Lib\Core\Provider;

use R\Lib\Core\Response\HttpResponse;
use R\Lib\Core\Contract\Provider;

class ResponseFactory implements Provider
{
    /**
     * 応答の作成
     */
    public function output ($output)
    {
        return new HttpResponse($output);
    }
    /**
     * 出力応答の作成
     */
    public function outputData ($data, $output)
    {
        $output["type"] = "output";
        $output["data"] = $data;
        return $this->output($output);
    }
    /**
     * HTML出力応答の作成
     */
    public function outputHtml ($html, $output)
    {
        $output["type"] = "output";
        $output["html"] = $html;
        return $this->output($output);
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
    public function downloadFile ($file, $output=array())
    {
        $output["type"] = "download";
        $output["file"] = $file;
        return $this->output($output);
    }
    /**
     * ファイルダウンロード応答の作成
     */
    public function downloadStoredFile ($stored_file, $output=array())
    {
        $output["type"] = "download";
        $output["stored_file"] = $stored_file;
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
