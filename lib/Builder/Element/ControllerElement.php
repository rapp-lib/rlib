<?php
namespace R\Lib\Builder\Element;

/**
 *
 */
class ControllerElement extends Element_Base
{
    protected function init ()
    {
        $pagesets = array();
        // Pagesetの補完
        if ($this->getAttr("type") == "index") {
            $pagesets[] = array("type"=>"blank");
        } elseif ($this->getAttr("type") == "login") {
            $pagesets[] = array("type"=>"login");
        } elseif ($this->getAttr("type") == "master") {
            if ($this->getAttr("usage") != "form") {
                $pagesets[] = array("type"=>"show");
            } elseif ($this->getAttr("usage") != "view") {
                $pagesets[] = array("type"=>"form");
            }
            if ($this->getAttr("use_csv")) {
                $pagesets[] = array("type"=>"csv");
            }
        }
        // Pagesetの登録
        foreach ($pagesets as $pageset) {
            $pageset_name = $pageset["name"] ? $pageset["name"] : $pageset["type"];
            $this->children[$pageset_name] = new PagesetElement($pageset_name, $pageset, $this);
        }
    }
    /**
     * ラベルの取得
     */
    public function getLabel ()
    {
        return $this->getAttr("label");
    }
    /**
     * クラス名の取得
     */
    public function getClassName ()
    {
        return str_camelize($this->getName())."Controller";
    }
    /**
     * 認証必須設定
     */
    public function getPrivRequired ()
    {
        return $this->getAttr("priv_required");
    }
    /**
     * 関係するRoleの取得
     */
    public function getRole ()
    {
        $role_name = $this->getAttr("access_as");
        return $this->getSchema()->getRole($role_name);
    }
    /**
     * 関係するTableの取得
     */
    public function getTable ()
    {
        $table_name = $this->getAttr("table");
        return $this->getSchema()->getTable($table_name);
    }
    /**
     * 入力画面に表示するColの取得
     */
    public function getInputCols ()
    {
        $cols = array();
        foreach ($this->getTable()->getCols() as $col) {
            if ($col->getAttr("type")) {
                $cols[] = $col;
            }
        }
        return $cols;
    }
    /**
     * 一覧画面に表示するColの取得
     */
    public function getListCols ()
    {
        $cols = $this->getInputCols();
        $cols = array_slice($cols,0,5);
        return $cols;
    }
    /**
     * @getter Pagesets
     */
    public function getPagesets ()
    {
        return (array)$this->children["pagesets"];
    }
    public function getPageset ($name)
    {
        return $this->children["pagesets"][$name];
    }
}
