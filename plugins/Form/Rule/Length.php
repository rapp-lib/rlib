<?php

namespace R\Lib\Form\Rule;

/**
 *
 */
class Length extends BaseRule {

    /**
     * override
     */
    protected $message ="文字で入力してください";

    /**
     * override
     */
    public function check ($value) {

        $value =str_replace("\r\n", "\n", $value);
        $length =mb_strlen($value,"UTF-8");
        $option =$this->params["option"];

        if ( ! $option) {

            return false;
        }

        list($min,$max) =explode("-",$option);

        if( ! ereg('-',$option) ){

            $this->message =$option."文字で入力してください";
            return $length == $option;

        } elseif ( ! strlen($min)) {

            $this->message =$max."文字までで入力してください";
            return $length <= $max;

        } elseif( ! strlen($max)) {

            $this->message =$min."文字以上で入力してください";
            return $length >= $min;

        } else {

            $this->message =$min."文字から".$max."文字までで入力してください";
            return ($length >= $min && $length <= $max);
        }
    }
}