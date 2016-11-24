<?php
namespace R\Lib\FileStorage;

/**
 * ファイルの保存ルール
 */
interface FileStorage
{
    public function create ($src_file=null, $meta=array());
    public function getFile ($code);
    public function getMeta ($code);
    public function getContents ($code);
    public function putContents ($code, $data);
    public function updateMeta ($code, $meta);
    public function remove ($code);
    public function download ($code);
    public function isAccessible ($code, $use_case=null);
}
