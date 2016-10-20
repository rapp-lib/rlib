<?php
namespace R\LibExtention\SearchType\Regacy;

/**
 *
 */
class Eq extends BaseSearch {

    protected $setting;

    public function __construct ($setting) {

        $this->setting =$setting;
    }

    public function getQuery ($input) {

        // [Deprecated] rlib1系のQuery配列のみ対応した処理
        // target IN (...query... AND query_target=input)
        if ($this->setting["query"] && $this->setting["query_target"]) {

            $query =$this->setting["query"];
            $query["conditions"][] =array($this->setting["query_target"] => $input);

            return array($this->setting["target"].' IN ('.dbi()->st_select($query).')');

        // [Deprecated] rlib1系のQuery配列のみ対応した処理
        // EXISTS (...query... AND target = input)
        } elseif ($this->setting["query"]) {

            $query =$this->setting["query"];
            $query["conditions"][] =array($this->setting["target"] => $input);

            return array('EXISTS ('.dbi()->st_select($query).')');

        // target = input
        } else {

            return array($this->setting["target"] =>$input);
        }
    }
}