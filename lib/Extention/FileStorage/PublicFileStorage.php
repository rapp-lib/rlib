<?php
namespace R\Lib\Extention\FileStorage;

use R\Lib\Extention\FileStorage\LocalFileStorage;

/**
 * 自由にアクセス可能なファイル保存ルール
 */
class PublicFileStorage extends LocalFileStorage
{
    protected $storage_name = "public";
    /**
     * @override FileStorage
     */
    public function isAccessible ($code, $use_case=null)
    {
        return true;
    }
}