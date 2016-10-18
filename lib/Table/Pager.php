<?php
namespace R\Lib\Table;
/*
    {{if $p = $ts->getPager()->set("url_params",$forms.search)->set("page_param_name")}}
        {{if $p->hasPage("prev")}}
            <a href="{{$p->getUrl("prev")}}">前ページ</a>
        {{/if}}
        {{foreach $p->getPageNums() as $page_num}}
            {{if $p->isCurrent($page_num)}}
                ( {{$page_num}} )
            {{else}}
                <a href="{{$p->getUrl($page_num)}}">( {{$page_num}} )</a>
            {{/if}}
        {{/foreach}}
        {{if $p->hasPage("next")}}
            <a href="{{$p->getUrl("next")}}">次ページ</a>
        {{/if}}
    {{/if}}
 */
class Pager
{
    private $result;
    private $values;

    public function __construct ($result, $count, $offset, $limit)
    {
        $this->result = $result;
        $this->values = array(
            "base_url" => page_to_url("."),
            "url_params" => array(),
            "page_param_name" => "p",
            "count" => $count,
            "offset" => $offset,
            "volume" => $limit,
            "current_page" => floor($offset/$limit)+1,
            "page_num" => floor($count/$limit)+1,
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
     * 値の設定
     */
    public function set ($name, $value)
    {
        $this->values[$name] = $value;
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
    public function hasPage ($page)
    {
        // 数字指定
        if (is_numeric($page)) {
            return $page > 0 && $page <= $this->values["page_num"];
        // 計算結果の名前を指定
        } else {
            return isset($this->values[$page."_page"]);
        }
    }
    /**
     * ページャーが有効かどうか
     */
    public function hasPages ()
    {
        return $this->values["count"] > 0;
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
            for ($i=1; $i<$window; $i++) {
                $page = $current-$i;
                if (count($pages)<$window-1 && $this->hasPage($page)) {
                    $pages[] = $page;
                }
                $page = $current+$i;
                if (count($pages)<$window-1 && $this->hasPage($page)) {
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
    /**
     * ページに対応するURLの取得
     */
    public function getUrl ($page)
    {
        // 不正なページ指定であれば現在のページを指定
        if ( ! $this->hasPage($page)) {
            $page = $this->values["current_page"];
        // 計算結果の名前を指定
        } elseif ( ! is_numeric($page)) {
            $page = $this->values[$page."_page"];
        }
        $base_url = $this->values["base_url"];
        $params = $this->values["url_params"];
        $page_param_name = $this->values["page_param_name"];
        $params[$page_param_name] = $page;
        if ($params[$page_param_name] == 1) {
            unset($params[$page_param_name]);
        }
        return url($base_url, $params);
    }
}