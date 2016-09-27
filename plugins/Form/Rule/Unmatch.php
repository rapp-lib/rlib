<?php

namespace R\Lib\Form\Rule;

/**
 *
 */
class Unmatch extends BaseRule {

    /**
     * override
     */
    protected $message ="一致していません";

    /**
     * override
     */
    public function check ($value) {

        return  strcmp($value,$this->params["option"]) == 0;
    }
}