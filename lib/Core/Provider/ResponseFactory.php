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
        // View応答
        if ($output["type"] == "view") {
            $output["data"] = app()->view($output["file"], $output["vars"]);
            if ( ! $output["content_type"]) {
                $output["content_type"] = "text/html; charset=utf-8";
            }
        }
        // JSONデータ応答
        if ($output["type"] == "json") {
            unset($output["vars"]["forms"]);
            $output["data"] = json_encode($output["vars"]);
            $output["headers"]["Access-Control-Allow-Origin"] = "*";
            if ( ! $output["content_type"]) {
                $output["content_type"] = "application/json; charset=utf-8";
            }
        }
        // データ出力応答
        if ($output["type"] == "output") {
            if (isset($output["html"])) {
                if ( ! $output["content_type"]) {
                    $output["content_type"] = "text/html; charset=utf-8";
                }
            }
        }
        // ダウンロード応答
        if ($output["type"] == "download") {
            if (isset($output["file"]) && ! is_readable($output["file"])) {
                report_error("ダウンロードファイルの指定が不正です",array(
                    "file" => $output["file"],
                ));
                if ( ! $output["content_type"]) {
                    $output["content_type"] = "application/octet-stream";
                }
            } elseif (isset($output["stored_file"]) && ! $output["stored_file"] instanceof StoredFile) {
                report_error("StoredFileの指定が不正です",array(
                    "stored_file" => $output["stored_file"],
                ));
            }
        }
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
    public function redirect ($route, $url_params=array(), $url_anchor=null, $output=array())
    {
        $output["type"] = "redirect";
        $output["url"] = is_object($route) ? $route->getUrl() : app()->route($route)->getUrl();
        $output["url_params"] = $url_params;
        $output["url_anchor"] = $url_anchor;
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
    /**
     * 応答の送信
     */
    public function respond ($response)
    {
        report_buffer_end(true);
        $output = $response->getOutput();
        if (php_sapi_name()==="cli") {
            // report("Render Response",array(
            //     "console" => app()->console,
            // ));
        } else {
            report("Render Response",array(
                "route" => app()->router->getCurrentRoute(),
                "request" => app()->request,
                "response" => $this,
            ));
        }
        // エラー応答
        if ($output["type"] == "error") {
            if (php_sapi_name()==="cli") {
                return true;
            } else {
                $response_code = $output["code"];
                if ( ! $response_code) {
                    $response_code = 500;
                }
                header("HTTP/1.1 ".$response_code, true, $response_code);
                $error_doc = constant("R_LIB_ROOT_DIR")."/assets/error/".$response_code.".php";
                if (file_exists($error_doc)) {
                    include($error_doc);
                } else {
                    print $response_code;
                }
                return true;
            }
        }
        // 転送応答
        if ($output["type"] == "redirect") {
            $url = url($output["url"], $output["url_params"]);
            if (app()->debug()) {
                print tag("a",array("href"=>$url),'<div style="padding:20px;'
                    .'background-color:#f8f8f8;border:solid 1px #aaaaaa;">'
                    .'Redirect ... '.$url.'</div>');
            } else {
                header("Location: ".$url);
            }
            return true;
        }
        // デバッグ中であればHTMLとして扱いバッファを消去しない
        if (app()->debug() && app()->request && app()->request["no_buffer_clear"]) {
            $output["content_type"] = "text/html; charset=utf-8";
        }
        $buffer_clear = true;
        // HTML表示であればバッファを消去しない
        if (preg_match('!^text/html!i',$output["content_type"])) {
            $buffer_clear = false;
        }
        if ($buffer_clear) {
            while (ob_get_level()) {
                ob_get_clean();
            }
        }
        // ヘッダの送信
        if (isset($output["content_type"])) {
            $output["headers"]["Content-Type"] =$output["content_type"];
        }
        if (is_array($output["headers"])) {
            foreach ($output["headers"] as $k=>$v) {
                header($k.": ".$v);
            }
        }
        // テンプレート/JSON
        if ($output["type"]=="view" || $output["type"]=="json") {
            echo($output["data"]);
            return true;
        }
        // ダウンロード
        if ($output["type"]=="download") {
            // ファイル名
            if (isset($output["filename"])) {
                header("Content-Disposition: attachment; filename=".$output["filename"]);
            } elseif (isset($output["file"])) {
                header("Content-Disposition: attachment; filename=".basename($output["file"]));
            }
            // 出力
            if (isset($output["file"])) {
                readfile($output["file"]);
            } elseif (isset($output["data"])) {
                echo($output["data"]);
            } elseif (isset($output["stored_file"])) {
                $output["stored_file"]->download();
            }
            return true;
        }
    }
}
