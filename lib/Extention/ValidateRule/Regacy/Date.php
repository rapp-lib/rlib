<?php

namespace R\Lib\Form\Rule;

/**
 *
 */
class Date extends BaseRule {

    /**
     * override
     */
    protected $message ="正しい日付を入力してください";

    /**
     * override
     */
    public function check ($value) {

        if (preg_match('!^(\d+)[/-]+(\d+)[/-](\d+)$!',$value,$match)) {

            list(,$year,$month,$date) =$match;
            $is_valid_date =checkdate($month,$date,$year);
        }

        return  ! strlen($value) || $is_valid_date;
    }
}