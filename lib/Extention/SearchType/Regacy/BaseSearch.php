<?php
namespace R\LibExtention\SearchType\Regacy;

/**
 *
 */
abstract class BaseSearch {

    protected $setting;

    /**
     * [__construct description]
     * @param [type] $setting [description]
     */
    public function __construct ($setting) {

        $this->setting =$setting;
    }

    public function getQuery ($input) {

        return "1=0";
    }
}