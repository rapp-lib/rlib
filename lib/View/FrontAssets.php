<?php
namespace R\Lib\View;

/**
 * JSリソース管理
 */
class FrontAssets
{
    private $def_mods = array();
    private $loaded_mods = array();
    private $buffer = array();
    public function addRepo ($repo_uri)
    {
        if ($repo_uri instanceof \R\Lib\Http\Uri) {
            $base_dir = $repo_uri->getPageFile();
            $base_uri = dirname("".$repo_uri);
        } elseif (is_array($repo_uri)) {
            $base_dir = $repo_uri["dir"];
            $base_uri = $repo_uri["uri"];
        }
        $repo = include($base_dir."/.assets.php");
        foreach ((array)$repo["inc"] as $path) {
            $this->addRepo(array("uri"=>$base_uri."/".$path, "dir"=>$base_dir."/".$path));
        }
        foreach ((array)$repo["ext"] as $mod_name=>$ext) {
            $this->def($mod_name, $ext[0], $ext[1]);
        }
        foreach ((array)$repo["local"] as $mod_name=>$def) {
            $this->def($mod_name, $base_uri."/".$def[0], $def[1]);
        }
    }
    public function def ($mod_name, $uri, $dep_mod_names=array())
    {
        $this->def_mods[$mod_name] = array("uri"=>$uri, "deps"=>$dep_mod_names);
    }
    public function load ($mod_names)
    {
        if (is_string($mod_names)) $mod_names = array($mod_names);
        foreach ((array)$mod_names as $mod_name) {
            if ($this->loaded_mods[$mod_name]) continue;
            $mod = $this->def_mods[$mod_name];
            if ( ! $mod) {
                report_warning("Front Assetが定義されていません",array("mod_name"=>$mod_name));
            }
            $this->loaded($mod_name);
            $this->load($mod["deps"]);
            $this->buffer[] = array("type"=>"js_uri", "uri"=>$mod["uri"]);
        }
    }
    public function loaded ($mod_name)
    {
        $this->loaded_mods[$mod_name] = true;
    }
    public function script ($code, $dep_mod_names=array())
    {
        $this->load($dep_mod_names);
        $this->buffer[] = array("type"=>"js_code", "code"=>$code);
    }
    public function scriptUri ($uri, $dep_mod_names=array())
    {
        $this->load($dep_mod_names);
        $this->buffer[] = array("type"=>"js_uri", "uri"=>$uri);
    }
    public function render ($o=array())
    {
        report($this->buffer);
        $html = "<!-- assets-loading -->"."\n";
        foreach ($this->buffer as $data) {
            if ($data["type"]=="js_uri") $html .= tag('script',array("src"=>$data["uri"]),"");
            elseif ($data["type"]=="js_code") $html .= tag('script',array(),$data["code"]);
            $html .= "\n";
        }
        $html .= "<!-- /assets-loading -->"."\n";
        if ($o["clear"]) $this->buffer = array();
        return $html;
    }
}
