<?php

namespace R\Lib\Form\Rule;

/**
 *
 */
abstract class BaseRule {

    protected $message ="入力が不正です";
    protected $params =array();

    /**
     *
     */
    public function __construct ($params)  {

        $this->params =$params;
    }

    /**
     *
     */
    public function check ($value) {

        return false;
    }

    /**
     *
     */
    public function getMessage () {

        return $this->message;
    }
}