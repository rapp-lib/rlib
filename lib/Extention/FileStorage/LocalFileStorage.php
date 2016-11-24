<?php
namespace R\Lib\Extention\FileStorage;

use R\Lib\FileStorage\FileStorage;

/**
 * ローカルファイルシステムへのファイル保存
 * 保存先: TMP_DIR/file_storage/STORAGE-NAME/YYYY/MM/DD/HHIISS-XXXXXX.EXT
 */
abstract class LocalFileStorage implements FileStorage
{
    protected $storage_name = null;
    /**
     * 新規のcodeを発行する
     */
    protected function createCode ($src_file, $meta=array())
    {
        if ( ! isset($this->storage_name)) {
            report_error("storage_nameの指定がありません",array(
                "class" => get_class($this),
            ));
        }
        $ext = preg_match('!\.([^\./]+)$!',$meta["original_filename"],$match) ? ".".$match[1] : "";
        return $this->storage_name.":/".date("Y/m/d/His-").mt_rand(100000,999999).$ext;
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
        if ( ! $this->isAccessible($code, "create")) {
            return null;
        }
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
        if ( ! $this->isAccessible($code, "getFile")) {
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
        if ( ! $this->isAccessible($code, "getMeta")) {
            return null;
        }
        $meta = (array)json_decode(util("File")->read($meta_file), true);
        return $meta;
    }
    /**
     * @override FileStorage
     */
    public function getContents ($code)
    {
        $file = $this->createFilename($code);
        if ( ! file_exists($file)) {
            return null;
        }
        if ( ! $this->isAccessible($code, "getContents")) {
            return null;
        }
        return util("File")->read($file);
    }
    /**
     * @override FileStorage
     */
    public function putContents ($code, $data)
    {
        $file = $this->createFilename($code);
        if ( ! file_exists($file)) {
            return null;
        }
        if ( ! $this->isAccessible($code, "putContents")) {
            return null;
        }
        util("File")->write($file, $data);
    }
    /**
     * @override FileStorage
     */
    public function updateMeta ($code, $meta)
    {
        $file = $this->createFilename($code);
        $meta_file = $file.".meta";
        if ( ! $this->isAccessible($code, "updateMeta")) {
            return null;
        }
        util("File")->write($meta_file, json_encode($meta));
    }
    /**
     * @override FileStorage
     */
    public function remove ($code)
    {
        $file = $this->createFilename($code);
        $meta_file = $file.".meta";
        if ( ! $this->isAccessible($code, "remove")) {
            return null;
        }
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
            header('HTTP', true, 404);
            return false;
        }
        // ダウンロード権限確認
        if ( ! $this->isAccessible($code,"download")) {
            header('HTTP', true, 403);
            return false;
        }
        // Content-Typeヘッダ送信
        if (isset($meta["content_type"])) {
            header("Content-Type: ".$meta["content_type"]);
        }
        // Content-Dispositionヘッダ送信
        if (isset($meta["original_filename"])) {
            header("Content-Disposition: attachment; filename=".basename($meta["original_filename"]));
        }
        // ファイルの内容の送信
        readfile($file);
        return true;
    }
    /**
     * @override FileStorage
     */
    public function isAccessible ($code, $use_case=null)
    {
        return true;
    }
}