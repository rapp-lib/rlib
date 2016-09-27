<?php

namespace R\Lib\Form\Rule;

/**
 *
 */
class Mail extends BaseRule {

    /**
     * override
     */
    protected $message ="正しいメールアドレスを入力してください";

    /**
     * override
     */
    public function check ($value) {

        return  ! strlen($value) || preg_match('/^([a-z0-9_]|\-|\.|\+)+@(([a-z0-9_]|\-)+\.)+[a-z]{2,6}$/i', $value);
    }
}