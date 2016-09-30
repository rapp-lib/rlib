<?php

namespace R\Lib\Query\Search;
use R\Lib\Query\St;

/**
 *
 */
class Word extends BaseSearch {

    protected $setting;

    public function __construct ($setting) {

        $this->setting =$setting;
    }

    public function getQuery ($input) {

        $part_query =array();

        foreach (preg_split('![\s　]+!u',$input) as $keyword) {

            if ($keyword) {

                $part_query[] =array($this->setting["target"]." LIKE " =>"%".$keyword."%");
            }
        }

        $part_query =count($part_query) == 1
                ? $part_query[0]
                : $part_query;

        return $part_query;
    }
}