<?php

namespace R\Lib\Form\Rule;

/**
 *
 */
class Regex extends BaseRule {

    /**
     * override
     */
    protected $message ="正しい形式で入力してください";

    /**
     * override
     */
    public function check ($value) {

        return  ! strlen($value) || preg_match($this->params["option"],$value);
    }
}