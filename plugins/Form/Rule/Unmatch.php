<?php

namespace R\Lib\Form\Rule;

/**
 *
 */
class Unmatch extends BaseRule {

    /**
     * override
     */
    protected $message ="重複しています";

    /**
     * override
     */
    public function check ($value) {

        return  strcmp($value,$this->params["option"]) != 0;
    }
}