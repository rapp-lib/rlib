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
    public static function callbackFileUpload ($value, $field_name_parts, $field_def)
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
        // ファイルがアップロードされていなければ変換しない
        if ( ! isset($file)) {
            return $value;
        }
    }
}