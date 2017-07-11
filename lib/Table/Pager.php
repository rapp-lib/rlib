<?php
namespace R\Lib\Table;

class Pager
{
    private $values;

    public function __construct ($count, $offset, $limit)
    {
        // 明らかに無効なoffsetであれば補正
        if ($offset < 0 || $offset > $count) {
            $offset = 0;
            $count = 0;
        }
        $this->values = array(
            "count" => $count,
            "offset" => $offset,
            "offset_end" => $offset+$limit>$count ? $count-$offset : $offset+$limit,
            "volume" => $limit,
            "current_page" => floor($offset/$limit)+1,
            "current_volume" => $count-$offset>$limit ? $limit : $count-$offset,
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
    public function getPages ($window)
    {
        $pages = array();
        // windowにより表示ページ数を指定
        // << < ...45[6]78. > >> window=5
        // << < ....5678[9] - -- window=5
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
        if ($this->values["first_page"] && $pages[0] != $this->values["first_page"]) {
            array_unshift($pages, "first");
        }
        if ($this->values["last_page"] && $pages[count($pages)-1] != $this->values["last_page"]) {
            array_push($pages, "last");
        }
        return $pages;
    }
}