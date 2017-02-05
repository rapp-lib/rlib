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
            $pagesets[] = array("type"=>"index");
        } elseif ($this->getAttr("type") == "login") {
            $pagesets[] = array("type"=>"login");
            if ($this->getAttr("use_reminder")) {
                $pagesets[] = array("type"=>"reminder");
            }
        } elseif ($this->getAttr("type") == "form") {
            $pagesets[] = array("type"=>"form", "use_mail"=>true);
            if ($this->getAttr("use_mailcheck")) {
                $pagesets[] = array("type"=>"mailcheck");
            }
        } elseif ($this->getAttr("type") == "show") {
            $pagesets[] = array("type"=>"show");
        } elseif ($this->getAttr("type") == "master") {
            $pagesets[] = array("type"=>"show");
            $pagesets[] = array("type"=>"form", "skip_confirm"=>true, "skip_complete"=>true);
            $pagesets[] = array("type"=>"delete");
            if ($this->getAttr("use_csv")) {
                $pagesets[] = array("type"=>"csv");
                if ($this->getAttr("use_import")) {
                    $pagesets[] = array("type"=>"import");
                }
            }
        }
        // Pagesetの登録
        foreach ($pagesets as $pageset_attrs) {
            $pageset_name = $pageset_attrs["name"] ? $pageset_attrs["name"] : $pageset_attrs["type"];
            $this->children["pageset"][$pageset_name] = new PagesetElement($pageset_name, $pageset_attrs, $this);
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
        return $this->getSchema()->getRoleByName($role_name);
    }
    /**
     * 関係するTableの取得
     */
    public function getTable ()
    {
        $table_name = $this->getAttr("table");
        return $this->getSchema()->getTableByName($table_name);
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
     * @getter Pageset
     */
    public function getPagesets ()
    {
        return (array)$this->children["pageset"];
    }
    public function getPagesetByType ($type)
    {
        foreach ($this->getPagesets() as $pageset) {
            if ($pageset->getAttr("type")==$type) {
                return $pageset;
            }
        }
        return null;
    }
    /**
     * @getter Page
     */
    public function getIndexPage ()
    {
        //TODO: 1番目のPageが取得されてしまうので、制御を加える
        foreach ($this->getPagesets() as $pageset) {
            return $pageset->getIndexPage();
        }
        return null;
    }
}
