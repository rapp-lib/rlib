<?php
namespace R\Lib\Debug;

use Barryvdh\Debugbar\JavascriptRenderer as IlluminateJavascriptRenderer;

class JavascriptRenderer extends IlluminateJavascriptRenderer
{
    public function renderHead()
    {
        if (!$this->url) {
            return parent::renderHead();
        }

        $cssRoute = $this->url->route('debugbar.assets.css', [
            'v' => $this->getModifiedTime('css')
        ]);

        $jsRoute  = $this->url->route('debugbar.assets.js', [
            'v' => $this->getModifiedTime('js')
        ]);

        $html  = '';
        $html .= "<link rel='stylesheet' type='text/css' href='{$cssRoute}'>";
        $html .= "<script type='text/javascript' src='{$jsRoute}'></script>";

        if ($this->isJqueryNoConflictEnabled()) {
            $html .= '<script type="text/javascript">jQuery.noConflict(true);</script>' . "\n";
        }

        return $html;
    }
}
