<?php
namespace R\Lib\Extention\FileStorage;

use R\Lib\Extention\FileStorage\LocalFileStorage;

/**
 * session_idでアクセス制御を行うファイル保存ルール
 * 同一Session内であれば全てのアクセスが許可される
 */
class TmpFileStorage extends LocalFileStorage
{
    protected $storage_name = "tmp";
    /**
     * @override LocalFileStorage
     */
    public function create ($src_file=null, $meta=array())
    {
        $meta["session_id"] = session_id();
        return parent::create($src_file, $meta);
    }
    /**
     * @override FileStorage
     */
    public function isAccessible ($code, $use_case=null)
    {
        if ($use_case == "create") {
            return true;
        }
        $meta = $this->getMeta($code);
        return $meta["session_id"] && $meta["session_id"] == session_id();
    }
}