<?php
namespace R\Lib\Doc\Content;
use R\Lib\Http\Uri;

class DirTree extends Content_Base
{
    protected $header = array("title");
    public function setHeader($header)
    {
        $this->header = $header;
    }
    public function addPath($path, $attrs=array())
    {
        if ($path instanceof Uri) $path = "".$path->withoutAuthority();
        $parts = explode('/', $path);
        if ( ! $this->data["tree"]) $this->data["tree"] = array();
        $tree = & $this->data["tree"];
        foreach ($parts as $part) {
            if ( ! $part) continue;
            $tree = & $tree[$part];
        }
        $tree["@"] = $attrs;
    }

// --

    public function exportCsv()
    {
        return $this->flatten($this->data["tree"], array());
    }
    private function flatten($tree, $parts)
    {
        $lines = array();
        if ($tree["@"]) {
            $lines[] = $this->mapHeader($tree["@"], $parts);
            unset($tree["@"]);
        }
        foreach ($tree as $part=>$sub_tree) {
            $sub_parts = array_merge($parts, array($part));
            foreach ($this->flatten($sub_tree, $sub_parts) as $line) $lines[] = $line;
        }
        return $lines;
    }
    private function mapHeader($attrs, $parts)
    {
        $line = array();
        foreach ($this->header as $k) $line[] = $attrs[$k];
        foreach ($parts as $part) $line[] = $part;
        return $line;
    }
}
