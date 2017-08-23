<?php
namespace R\Lib\Builder\Element;

class MailElement extends Element_Base
{
    public function init ()
    {
    }
    /**
     * テンプレート設定名を取得
     */
    public function getTemplateEntry ()
    {
        return $this->getparent()->getTemplateEntry().".mail_template";
    }
    /**
     * テンプレートファイル名を取得
     */
    public function getTemplateFile ()
    {
        return $this->getName().".php";
    }
}
