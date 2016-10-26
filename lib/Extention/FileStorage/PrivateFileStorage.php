<?php
namespace R\Lib\Extention\FileStorage;
use R\Lib\FileStorage\FileStorage;

class PrivateFileStorage implements FileStorage
{
    /**
     * @override FileStorage
     */
    public function create ($src_file, $meta=array())
    {
        $ext = preg_match('!\.([^\./]+)$!',$src_file,$match) ? ".".$match[1] : "";
        $base = "/".date("Y/m/d/H")."/".md5($src_file).$ext;
        $code = "private:".$base;
        $file = $this->getFile($code);
        $meta_file = $base.".meta";
        $meta["session_id"] = session_id();
        if (file_exists($src_file)) {
            util("File")->move($src_file, $file);
        } else {
            util("File")->create($file);
        }
        util("File")->write($meta_file, serialize($meta));
        return $code;
    }
    /**
     * @override FileStorage
     */
    public function getFile ($code)
    {
        $file = registry("Path.tmp_dir")."/storage/private/".$code;
        if ( ! file_exists($file)) {
            return null;
        }
        if ( ! $this->isAccessible()) {
            return null;
        }
        return $file;
    }
    /**
     * @override FileStorage
     */
    public function getUrl ($code)
    {
        return false;
    }
    /**
     * @override FileStorage
     */
    public function getMeta ($code)
    {
        $file = static::getFile($code);
        $meta_file = $file.".meta";
        if ( ! $file || ! file_exists($meta_file)) {
            return null;
        }
        return (array)unserialize($file.".meta");
    }
    /**
     * @override FileStorage
     */
    public function updateMeta ($code, $meta)
    {
    }
    /**
     * @override FileStorage
     */
    public function isAccessible ($use_case=null)
    {
    }
    /**
     * @override FileStorage
     */
    public function remove ($code)
    {
        $file = registry("Path.tmp_dir")."/storage/private/".$code;
        $meta_file = $file.".meta";
        util("File")->remove($file);
        util("File")->remove($meta_file);
    }
}