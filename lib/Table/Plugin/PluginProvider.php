<?php
namespace R\Lib\Table\Plugin;

interface PluginProvider
{
    /**
     * 機能を登録する処理を記述する
     * TableFeatureCollection::registerPluginから呼び出される
     */
    public function registerPlugin($features);
}
