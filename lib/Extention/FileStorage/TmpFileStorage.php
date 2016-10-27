<?php
namespace R\Lib\Extention\FileStorage;

use R\Lib\FileStorage\FileStorage;

/**
 * session_idでアクセス制御を行うファイル保存ルール
 * 同一Session内であれば全てのアクセスが許可される
 * 保存先: TMP_DIR/file_storage/tmp/YYYY/MM/DD/HH/II-SS-XXXXXXX.EXT
 */
class TmpFileStorage implements FileStorage
{
    protected $storage_name = "tmp";
    /**
     * 新規のcodeを発行する
     */
    protected function createCode ($src_file, $meta=array())
    {
        $ext = preg_match('!\.([^\./]+)$!',$meta["original_filename"],$match) ? ".".$match[1] : "";
        return $this->storage_name.":/".date("Y/m/d/H/i-s-").mt_rand(1000000,9999999).$ext;
    }
    /**
     * codeからファイル名を構築する
     */
    protected function createFilename ($code)
    {
        return registry("Path.tmp_dir")."/file_storage/".preg_replace('!^(\w+):!','$1',$code);
    }
    /**
     * @override FileStorage
     */
    public function create ($src_file=null, $meta=array())
    {
        $code = $this->createCode($src_file, $meta);
        $file = $this->createFilename($code);
        $meta_file = $file.".meta";
        $meta["session_id"] = session_id();
        if (file_exists($src_file)) {
            util("File")->move($src_file, $file);
        } else {
            util("File")->create($file);
        }
        util("File")->write($meta_file, json_encode($meta));
        return $code;
    }
    /**
     * @override FileStorage
     */
    public function getFile ($code)
    {
        $file = $this->createFilename($code);
        if ( ! file_exists($file)) {
            return null;
        }
        if ( ! $this->isAccessible($code)) {
            return null;
        }
        return $file;
    }
    /**
     * @override FileStorage
     */
    public function getMeta ($code)
    {
        $file = $this->createFilename($code);
        $meta_file = $file.".meta";
        if ( ! file_exists($meta_file)) {
            return null;
        }
        $meta = (array)json_decode(util("File")->read($meta_file));
        return $meta;
    }
    /**
     * @override FileStorage
     */
    public function updateMeta ($code, $meta)
    {
        $file = $this->createFilename($code);
        $meta_file = $file.".meta";
        util("File")->write($meta_file, json_encode($meta));
    }
    /**
     * @override FileStorage
     */
    public function isAccessible ($code, $use_case=null)
    {
        $meta = $this->getMeta($code);
        return $meta["session_id"] && $meta["session_id"] == session_id();
    }
    /**
     * @override FileStorage
     */
    public function remove ($code)
    {
        $file = $this->createFilename($code);
        $meta_file = $file.".meta";
        util("File")->remove($file);
        util("File")->remove($meta_file);
    }
    /**
     * @override FileStorage
     */
    public function download ($code)
    {
        $file = $this->getFile($code);
        $meta = $this->getMeta($code);
        // ファイルの存在確認
        if ( ! $file) {
            header("HTTP/1.1 404 Not Found");
            return false;
        }
        // ダウンロード権限確認
        if ( ! $this->isAccessible($code,"download")) {
            header("HTTP/1.1 403 Forbidden");
            return false;
        }
        // Content-Typeヘッダ送信
        $content_type = isset($meta["type"]) ? $meta["type"] : "application/octet-stream";
        header("Content-Type: ".$content_type);
        // Content-Dispositionヘッダ送信
        $filename = isset($meta["original_filename"]) ? basename($meta["original_filename"]) : basename($file);
        header("Content-Disposition: attachment; filename=".$filename);
        // ファイルの内容の送信
        readfile($file);
        return true;
    }
}