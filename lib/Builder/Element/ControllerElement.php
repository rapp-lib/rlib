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
            if ($this->getFlagAttr("use_reminder", false)) {
                $pagesets[] = array("type"=>"reminder");
            }
        } elseif ($this->getAttr("type") == "form") {
            $pagesets[] = array("type"=>"form",
                "param_fields"=>$this->getFlagAttr("param_fields",array()),
                "use_mail"=>$this->getFlagAttr("use_mail",false),
                "skip_confirm"=>$this->getFlagAttr("skip_confirm", false),
                "skip_complete"=>$this->getFlagAttr("skip_complete", false));
        } elseif ($this->getAttr("type") == "list") {
            $pagesets[] = array("type"=>"list",
                "param_fields"=>$this->getFlagAttr("param_fields",array()));
            if ($this->getFlagAttr("use_detail", true)) {
                $pagesets[] = array("type"=>"detail");
            }
        } elseif ($this->getAttr("type") == "detail") {
            $pagesets[] = array("type"=>"detail",
                "param_fields"=>$this->getFlagAttr("param_fields",array()));
        } elseif ($this->getAttr("type") == "master") {
            $pagesets[] = array("type"=>"list",
                "param_fields"=>$this->getFlagAttr("param_fields",array()));
            $pagesets[] = array("type"=>"form",
                "param_fields"=>$this->getFlagAttr("param_fields",array()),
                "use_mail"=>$this->getFlagAttr("use_mail",false),
                "is_master"=>$this->getFlagAttr("is_master",true),
                "skip_confirm"=>$this->getFlagAttr("skip_confirm", true),
                "skip_complete"=>$this->getFlagAttr("skip_complete", true));
            if ($this->getFlagAttr("use_detail", false)) {
                $pagesets[] = array("type"=>"detail");
            }
            if ($this->getFlagAttr("use_delete", true)) {
                $pagesets[] = array("type"=>"delete");
            }
            if ($this->getFlagAttr("use_csv", false)) {
                $pagesets[] = array("type"=>"csv");
                if ($this->getFlagAttr("use_import", false)) {
                    $pagesets[] = array("type"=>"import",
                        "param_fields"=>$this->getFlagAttr("param_fields",array()));
                }
            }
        }
        // Pagesetの登録
        foreach ($pagesets as $pageset_attrs) {
            $pageset_name = $pageset_attrs["type"];
            $this->children["pageset"][$pageset_name]
                = new PagesetElement($pageset_name, $pageset_attrs, $this);
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
     * 生成パターンを切り替えるフラグの取得
     */
    public function getFlagAttr ($name, $default=false)
    {
        $flag = $this->getAttr($name);
        return isset($flag) ? $flag : $default;
    }
    /**
     * 認証必須設定
     */
    public function getPrivRequired ()
    {
        return $this->getAttr("priv_required");
    }
    /**
     * type
     */
    public function getType ()
    {
        return $this->getAttr("type");
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
        $cols = $this->getTable()->getInputCols();
        // use_atによるフィルタリング
        $filtered_cols = array();
        foreach ($cols as $i => $col) {
            if (is_array($use_at = $col->getAttr("use_at"))) {
                foreach ($use_at as $controller_name) {
                    if ($controller_name == $this->getName()) $filtered_cols[] = $col;
                }
            }
        }
        return $filtered_cols ?: $cols;
    }
    /**
     * 一覧画面に表示するColの取得
     */
    public function getListCols ()
    {
        $cols = $this->getInputCols();
        $cols = array_filter($cols, function($col){
            return ! in_array($col->getAttr("type"),array(
                "assoc", "password", "textarea", "checklist", "checkbox", "file"));
        });
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
     * @getter Pageset
     */
    public function getIndexPageset ()
    {
        // 一番はじめに追加されたPagesetを返す
        foreach ($this->getPagesets() as $pageset) return $pageset;
        return null;
    }
    /**
     * @getter Page
     */
    public function getIndexPage ()
    {
        return $this->getIndexPageset()->getIndexPage();
    }
    /**
     * リンク先情報の取得
     */
    public function getLinks ()
    {
        $links = array();
        foreach ((array)$this->getAttr("link_to") as $controller_name) {
            $link = array();
            $link["controller"] = $this->getSchema()->getControllerByName($controller_name);
            // 外部キーによる関係を探す
            if ($this->getTable() && $link["controller"]->getTable()) {
                $link["fkey_col"] = $link["controller"]->getTable()
                    ->getColByAttr("def.fkey_for", $this->getTable()->getName());
            }
            $links[] = $link;
        }
        return $links;
    }
    /**
     * リンク参照元情報の取得
     */
    public function getLinkedController ()
    {
        // 参照元としては、最初の1件目のみ有効
        foreach ($this->getSchema()->getControllers() as $controller) {
            foreach ($controller->getLinks() as $link) {
                if ($link["controller"] == $this) return $controller;
            }
        }
        return null;
    }

// -- マイページ機能用

    /**
     * Tableのクエリ組み立てChainへの追加分を返す
     */
    public function getTableChain ($type)
    {
        $append = "";
        if ($this->getFlagAttr("is_mypage")) {
            if ($type=="find") $append .= '->findMine()';
            else if ($type=="save") $append .= '->setMine()';
        }
        return $append;
    }
    /**
     * アカウント自身を対象としたControllerであるかどうかを返す
     */
    public function isAccountMyPage ()
    {
        if ($this->getFlagAttr("is_mypage") && $this->getRole()) {
            if ($role_table = $this->getRole()->getAuthTable()) {
                return $this->getTable()->getName() == $role_table->getName();
            }
        }
        return false;
    }
}
