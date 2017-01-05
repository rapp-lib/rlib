<?php
namespace R\Lib\Builder\Element;

/**
 *
 */
class ControllerElement extends Element_Base
{
    protected $actions = array();

    protected function init ()
    {
        $list =array();

        if ($this->getAttr("type") == "index") {
            $list["index"] =array("label"=>"INDEX", "has_html"=>true);
            $list["static"] =array("label"=>"STATIC", "has_html"=>false, "static"=>true);
        }
        if ($this->getAttr("type") == "login") {
            $list["index"] =array("label"=>"TOP", "has_html"=>false);
            $list["login"] =array("label"=>"ログイン", "has_html"=>true);
            $list["logout"] =array("label"=>"ログアウト", "has_html"=>false);
        }
        if ($this->getAttr("type") == "master") {
            $list["index"] =array("label"=>"TOP", "has_html"=>false);
            if ($this->getAttr("usage") != "form") {
                $list["view_list"] =array("label"=>"一覧", "has_html"=>true);
            }
            if ($this->getAttr("usage") != "view") {
                $list["entry_form"] =array("label"=>"入力", "has_html"=>true);
                $list["entry_confirm"] =array("label"=>"入力確認", "has_html"=>true);
                $list["entry_exec"] =array("label"=>"入力完了", "has_html"=>true);
            }
            if ($this->getAttr("usage") != "form" && $this->getAttr("usage") != "view") {
                $list["delete"] =array("label"=>"削除", "has_html"=>false);
            }
            if ($this->getAttr("usage") != "form" && $this->getAttr("use_csv")) {
                $list["view_csv"] =array("label"=>"CSVエクスポート", "has_html"=>false);
            }
            if ($this->getAttr("usage") != "view" && $this->getAttr("use_csv")) {
                $list["entry_csv_form"] =array("label"=>"CSVインポート", "has_html"=>true);
                $list["entry_csv_confirm"] =array("label"=>"CSVインポート 確認", "has_html"=>false);
                $list["entry_csv_exec"] =array("label"=>"CSVインポート 完了", "has_html"=>false);
            }
        }
        foreach ($list as $action_name => $action_attrs) {
            $this->actions[$action_name] = new ActionElement($action_name, $action_attrs, $this);
        }
    }
    /**
     * クラス名の取得
     */
    public function getClassName ()
    {
        return str_camelize($this->getName())."Controller";
    }
    /**
     * Actionの取得
     */
    public function getAction ($action_name=null)
    {
        if ( ! $action_name) {
            return $this->actions;
        }
        return $this->actions[$action_name];
    }
    /**
     * Roleの取得
     */
    public function getRole ()
    {
        return $this->getSchema()->getRole($this->getAttr("access_as"));
    }
    /**
     * Tableの取得
     */
    public function getTable ()
    {
        $table_name = $this->getAttr("table");
        return $table_name ? $this->getSchema()->getTable($table_name) : null;
    }
}
