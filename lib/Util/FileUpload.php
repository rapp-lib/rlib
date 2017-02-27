<?php
namespace R\Lib\Util;

class FileUpload
{
    public static function storeUploadedFile ($storage_name, $file)
    {
        if ($error = util("FileUpload")->getError($file)) {
            return array("error"=>$error);
        }
        if (file_exists($file["tmp_name"])) {
            chmod($file["tmp_name"], 0664);
        }
        // アップロードファイルをFileStorageに転送
        $stored_file = app()->file_storage->create($storage_name, $file["tmp_name"], array(
            "original_filename" => basename($file["name"]),
            "content_type" => $file["type"],
        ));
        if ( ! $stored_file) {
            return array("error"=>array("type"=>"storage", "message"=>"StoredFile作成失敗"));
        }
        return array("stored_file"=>$stored_file, "code"=>$stored_file->getCode());
    }
    /**
     * アップロードファイルを取得する
     */
    public static function getFileField ($field_name)
    {
        // $_FILESから情報を取得
        $file = null;
        $parts = explode('.',$field_name);
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
        return $file;
    }
    /**
     * アップロードファイルのエラーを確認
     */
    public static function getError ($file)
    {
        // エラー判定
        // 値: 4; ファイルはアップロードされませんでした。
        if ( ! isset($file) || $file["error"] == UPLOAD_ERR_NO_FILE) {
            $file["error_detail"] = array("type"=>"nofile");
        // 値: 0; エラーはなく、ファイルアップロードは成功しています。
        } elseif ($file["error"] == UPLOAD_ERR_OK) {
            $file["error_detail"] = false;
        // 値: 3; アップロードされたファイルは一部のみしかアップロードされていません。
        } elseif ($file["error"] == UPLOAD_ERR_PARTIAL) {
            $file["error_detail"] = array("type"=>"partial", "message"=>"ファイルのアップロードが不完全です、Content-Lengthの指定誤りの可能性。");
        // 値: 1; アップロードされたファイルは、php.ini の upload_max_filesize ディレクティブの値を超えています。
        // 値: 2; アップロードされたファイルは、HTML フォームで指定された MAX_FILE_SIZE を超えています。
        } elseif ($file["error"] == UPLOAD_ERR_INI_SIZE || $file["error"] == UPLOAD_ERR_FORM_SIZE) {
            $file["error_detail"] = array("type"=>"nofile");
        // 値: 6; テンポラリフォルダがありません。
        // 値: 7; ディスクへの書き込みに失敗しました。
        // 値: 8; PHP の拡張モジュールがファイルのアップロードを中止しました。
        } elseif ($file["error"] > 4) {
            $file["error_detail"] = array("type"=>"internal");
        // is_uploaded_fileの不正
        } elseif (strlen($file["tmp_name"]) && ! is_uploaded_file($file["tmp_name"])) {
            $file["error_detail"] = array("type"=>"security");
        } else {
            $file["error_detail"] = array("type"=>"unknown");
        }
        return $file["error_detail"];
    }
}