<?php

namespace R\Lib\Form\Rule;

/**
 *
 */
class Real extends BaseRule {

    /**
     * override
     */
    protected $message ="実数値で入力してください";

    /**
     * override
     */
    public function check ($value) {

        return  ! strlen($value) || ctype_digit(preg_replace('!(^-|\.)!','',$value));
    }
}