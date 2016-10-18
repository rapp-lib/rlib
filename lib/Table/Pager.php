<?php
namespace R\Lib\Table;

class Pager
{
    private $result;
    private $values;

    public function __construct ($result, $count, $offset, $limit)
    {
        $this->result = $result;
        // 明らかに無効なoffsetであれば補正
        if ($offset < 0 || $offset > $count) {
            $offset = 0;
            $count = 0;
        }
        $this->values = array(
            "count" => $count,
            "offset" => $offset,
            "offset_end" => $offset+$volume>$count ? $count-$offset : $offset+$volume,
            "volume" => $limit,
            "current_page" => floor($offset/$limit)+1,
            "page_num" => ceil($count/$limit),
        );
        // 現在のページにより前後のページを指定
        // -- - [1]23456789 > >>
        // << < 1[2]3456789 > >>
        // << < 12345678[9] - --
        if ($this->values["current_page"] > 1) {
            $this->values["first_page"] = 1;
            $this->values["prev_page"] = $this->values["current_page"] - 1;
        }
        if ($this->values["current_page"] < $this->values["page_num"]) {
            $this->values["last_page"] = $this->values["page_num"];
            $this->values["next_page"] = $this->values["current_page"] + 1;
        }
    }
    /**
     * 値の取得
     */
    public function get ($name)
    {
        return $this->values[$name];
    }
    /**
     * ページがリンクとして有効かどうか
     */
    public function getPage ($page)
    {
        // 数字指定
        if (is_numeric($page)) {
            return $page > 0 && $page <= $this->values["page_num"] ? $page : null;
        // 計算結果の名前を指定
        } else {
            return $this->values[$page."_page"];
        }
    }
    /**
     * 件数を指定してページ一覧を取得
     */
    public function getPages ($window=null)
    {
        $pages = array();
        // windowにより表示ページ数を指定
        // << < ...45[6]78. > >> window=5
        // << < ....5678[9] - -- window=5
        if (isset($window)) {
            $current = $this->values["current_page"];
            $pages[] = $current;
            for ($i=1; $i<$window; $i++) {
                $page = $current-$i;
                if (count($pages)<$window && $page > 0 && $page <= $this->values["page_num"]) {
                    $pages[] = $page;
                }
                $page = $current+$i;
                if (count($pages)<$window && $page > 0 && $page <= $this->values["page_num"]) {
                    $pages[] = $page;
                }
            }
            sort($pages);
        // windowの指定が無ければ全ページ表示
        } else {
            $pages = range(1,$this->values["page_num"]);
        }
        return $pages;
    }
}