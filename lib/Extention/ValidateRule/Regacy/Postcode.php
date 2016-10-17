<?php

namespace R\Lib\Form\Rule;

/**
 *
 */
class Postcode extends BaseRule {

    /**
     * override
     */
    protected $message ="半角数字(ハイフンあり可)で入力してください";

    /**
     * override
     */
    public function check ($value) {

        return  ! strlen($value) || preg_match('!^(\d\d\d)-?(\d\d\d\d\d?)$!',$value,$match);
    }
}