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
    public static function callbackFileUpload ($value, $parts, $field_def)
    {
        $file = null;
        // 対象が配列ではない
        if (count($parts)==1) {
            $file = $_FILES[$parts[0]];
        // 対象が1次配列
        } elseif (count($parts)==2) {
            if (isset($_FILES[$parts[0]])) {
                foreach ($_FILES[$parts[0]] as $k => $v) {
                    if (isset($v[$parts[1]])) {
                        $file[$k] = $v[$parts[1]];
                    }
                }
            }
        // 対象が2次配列
        } elseif (count($parts)==3) {
            if (isset($_FILES[$parts[0]])) {
                foreach ($_FILES[$parts[0]] as $k => $v) {
                    if (isset($v[$parts[1]][$parts[2]])) {
                        $file[$k] = $v[$parts[1]][$parts[2]];
                    }
                }
            }
        }
        // ファイルがアップロードされていなければ書き換えない
        if ( ! isset($file)) {
            return $value;
        }
        // アップロードエラーの場合は書き換えない
        if ($file["error"] != UPLOAD_ERR_OK || ! is_uploaded_file($file["tmp_name"])) {
            report_warning("ファイルのアップロードに失敗しました",array(
                "field_name" => implode(".",$parts),
                "storage" => $field_def["storage"],
                "file" => $file,
                "file_error" => $file["error"] != UPLOAD_ERR_OK,
                "is_invalid_file" => ! is_uploaded_file($file["tmp_name"]),
            ));
            return $value;
        }
        // アップロード処理
        $meta = array(
            "original_filename" => basename($file["name"]),
            "type" => $file["type"],
        );
        $stored_file = file_storage()->create($field_def["storage"], $file["tmp_name"], $meta);
        if ( ! isset($stored_file)) {
            report_warning("アップロードファイルのStoredFile作成に失敗しました",array(
                "field_name" => implode(".",$parts),
                "storage" => $field_def["storage"],
                "file" => $file["tmp_name"],
                "meta" => $meta,
            ));
            return $value;
        }
        $code = $stored_file->getCode();
        // アップロードの正常終了
        report("ファイルが正常にアップロードできました",array(
            "field_name" => implode(".",$parts),
            "storage" => $field_def["storage"],
            "file" => $file,
            "code" => $code,
        ));
        return $code;
    }
}