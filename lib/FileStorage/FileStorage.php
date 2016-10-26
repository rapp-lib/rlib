<?php
namespace R\Lib\FileStorage;

/**
 * ファイルの保存ルール
 */
interface FileStorage
{
    public function create ($src_file=null, $meta=array());
    public function getFile ($code);
    public function getUrl ($code);
    public function getMeta ($code);
    public function updateMeta ($code, $meta);
    public function isAccessible ($use_case=null);
    public function remove ();
}
