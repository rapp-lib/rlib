<?php

namespace R\Lib\Form\Rule;

/**
 *
 */
class Tel extends BaseRule {

    /**
     * override
     */
    protected $message ="半角数字(ハイフンあり可)で入力してください";

    /**
     * override
     */
    public function check ($value) {

        return  ! strlen($value) || ctype_digit(preg_replace('!-!','',$value));
    }
}