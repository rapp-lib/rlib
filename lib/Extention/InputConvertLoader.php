<?php
namespace R\Lib\Extention;

class InputConvertLoader
{
    public static function getCallback ($name)
    {
        $class_name = get_class();
        $callback_method = "callback".str_camelize($name);
        if (method_exists($class_name,$callback_method)) {
            return array($class_name,$callback_method);
        }
    }

    /**
     * ファイルアップロード
     * field.storageに指定されたFileStorageにファイルを保存する
     */
    public static function callbackFileUpload ($value, $field_name, $field_def)
    {
        $file = util("FileUpload")->getFileField($field_name);
        $result = util("FileUpload")->storeUploadedFile($field_def["storage"], $file);
        // アップロードエラーの場合は書き換えない
        if ($result["error"]) {
            if ($result["error"]["type"] != "nofile") {
                report_warning("ファイルアップロード失敗", array(
                    "field_name" => $field_name,
                    "storage" => $field_def["storage"],
                    "error" => $result["error"],
                    "file" => $file,
                ));
            }
            return $value;
        }
        // アップロードの正常終了
        report_info("File Uploaded",array(
            "field_name" => $field_name,
            "storage" => $field_def["storage"],
            "code" => $result["code"],
            "file" => $file,
        ));
        return $result["code"];
    }
}