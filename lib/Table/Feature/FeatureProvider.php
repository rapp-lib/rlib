<?php
namespace R\Lib\Table\Feature;

interface FeatureProvider
{
    /**
     * 機能を登録する処理を記述する
     * TableFeatureCollection::registerから呼び出される
     */
    public function register($features);
}
