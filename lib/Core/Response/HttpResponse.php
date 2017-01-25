<?php
namespace R\Lib\Core\Response;

use R\Lib\Core\Contract\Response;
use R\Lib\Core\Exception\ResponseException;

class HttpResponse implements Response
{
    protected $output;
    public function __construct ($output)
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
            $output["data"] = json_encode($output["vars"]);
            if ( ! $output["content_type"]) {
                $output["content_type"] = "application/json; charset=utf-8";
            }
        }
        if ($output["type"] == "download") {
            if (isset($output["file"])) {
                if ( ! is_readable($output["file"])) {
                    report_error("ダウンロードファイルの指定が不正です",array(
                        "file" => $output["file"],
                    ));
                }
            } elseif (isset($output["stored_file"])) {
                if ( ! is_a($output["stored_file"], 'R\Lib\FileStorage\StoredFile')) {
                    report_error("StoredFileの指定が不正です",array(
                        "stored_file" => $output["stored_file"],
                    ));
                }
            } elseif (isset($output["data"])) {
                //
            } else {
                report_error("ダウンロード対象の指定が不正です",array(
                    "output" => $output,
                ));
            }
        }
        $this->output = $output;
    }
    public function raise ()
    {
        throw new ResponseException($this);
    }
    public function render ()
    {
        $output = $this->output;
        report("Render Response",array(
            "route" => app()->router->getCurrentRoute(),
            "request" => app()->request,
            "output_type" => $output["type"],
        ));
        // エラー応答
        if ($output["type"] == "error") {
            $response_code = $output["response_code"];
            if ( ! $response_code) {
                $response_code = 500;
            }
            header("HTTP", true, $response_code);
            $error_doc = constant("R_LIB_ROOT_DIR")."/assets/error/".$response_code.".php";
            if (file_exists($error_doc)) {
                include($error_doc);
            } else {
                print $response_code;
            }
            return true;
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
        // HTML表示以外の出力であればバッファを消去
        if ( ! preg_match('!^text/html!i',$output["content_type"])) {
            while (ob_get_level()) {
                ob_get_clean();
            }
        }
        // Content-Typeヘッダの送信
        if (isset($output["content_type"])) {
            header("Content-Type: ".$output["content_type"]);
        } elseif (isset($output["download"])) {
            header("Content-Type: application/octet-stream");
        }
        // ダウンロードファイル名の補完
        if (isset($output["download"])) {
            if (is_string($output["download"])) {
                header("Content-Disposition: attachment; filename=".$output["download"]);
            } elseif (isset($output["file"])) {
                header("Content-Disposition: attachment; filename=".basename($output["file"]));
            }
        }
        // 形式に従って出力
        if (isset($output["data"])) {
            echo($output["data"]);
        } elseif (isset($output["file"])) {
            if (is_readable($output["file"])) {
                readfile($output["file"]);
            }
        } elseif (isset($output["stored_file"])) {
            if (is_a($output["stored_file"], 'R\Lib\FileStorage\StoredFile')) {
                $output["stored_file"]->download();
            }
        }
    }
}