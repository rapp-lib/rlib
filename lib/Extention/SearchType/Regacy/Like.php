<?php
namespace R\LibExtention\SearchType\Regacy;

/**
 *
 */
class Like extends BaseSearch {

    protected $setting;

    public function __construct ($setting) {

        $this->setting =$setting;
    }

    public function getQuery ($input) {

        return strlen($input)
                ? array($this->setting["target"]." LIKE " =>"%".$input."%")
                : null;
    }
}