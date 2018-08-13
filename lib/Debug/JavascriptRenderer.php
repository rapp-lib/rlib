<?php
namespace R\Lib\Debug;

use Barryvdh\Debugbar\JavascriptRenderer as IlluminateJavascriptRenderer;

class JavascriptRenderer extends IlluminateJavascriptRenderer
{
    public function __construct(DebugBar $debugBar, $baseUrl = null, $basePath = null)
    {
        parent::__construct($debugBar, $baseUrl, $basePath);
        $assets_dir = constant("R_LIB_ROOT_DIR")."/assets/debugbar/resources";
        $this->jsFiles[0] = $assets_dir."/".'debugbar.js';
        $this->jsFiles['rlib-widget'] = $assets_dir."/".'widget.js';
        $this->cssFiles['rlib-widget'] = $assets_dir."/".'widget.css';
        // $this->addAssets(array('widget.css'), array('widget.js'),
        //     constant("R_LIB_ROOT_DIR")."/assets/debugbar/resources/", null);
    }
    public function renderHead()
    {
        if (!$this->url) {
            return parent::renderHead();
        }

        $cssRoute = $this->url->route('debugbar.assets.css', array(
            'v' => $this->getModifiedTime('css')
        ));

        $jsRoute  = $this->url->route('debugbar.assets.js', array(
            'v' => $this->getModifiedTime('js')
        ));

        $html  = '';
        $html .= "<link rel='stylesheet' type='text/css' href='{$cssRoute}'>";
        $html .= "<script type='text/javascript' src='{$jsRoute}'></script>";

        if ($this->isJqueryNoConflictEnabled()) {
            $html .= '<script type="text/javascript">jQuery.noConflict(true);</script>' . "\n";
        }

        return $html;
    }
}
