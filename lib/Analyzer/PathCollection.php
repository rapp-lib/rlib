<?php
namespace R\Lib\Analyzer;

/**
 * ファイル名集合の操作を行う機能セット
 */
class PathCollection
{
    private $paths = array();
    public function addPath($path, $attrs=array())
    {
        $p = & $this->paths;
        $path = preg_replace('!(^/)|(/$)!' ,'', $path);
        $parts = explode("/", $path);
        foreach ($parts as $part) {
            if ( ! is_array($p[$part])) $p[$part] = array();
            if ($part == "") continue;
            $p = & $p[$part];
        }
        foreach ($attrs as $k=>$v) $p["/"][$k] = $v;
    }
    public function removePath($path)
    {
        $p = & $this->paths;
        $path = preg_replace('!(^/)|(/$)!' ,'', $path);
        $parts = explode("/", $path);
        $last_part = array_pop($parts);
        foreach ($parts as $part) {
            if ( ! is_array($p[$part])) return;
            if ($part == "") continue;
            $p = & $p[$part];
        }
        if ($last_part==="*") foreach ($p as $k=>$v) unset($p[$k]);
        else unset($p[$last_part]);
    }
    public function addPaths($paths)
    {
        if ($paths instanceof PathCollection) {
            foreach ($paths->getFlatten() as $k=>$v) $this->addPath($k,$v);
        } elseif (is_array($paths)) {
            foreach ($paths as $path) $this->addPath($path);
        }
    }
    public function __($root="")
    {
        report($this->paths);
    }
    public function getTree($root="")
    {
        $p = & $this->paths;
        $path = preg_replace('!(^/)|(/$)!' ,'', $root);
        $parts = explode("/", $path);
        foreach ($parts as $part) {
            if ( ! is_array($p[$part])) return array();
            if ($part == "") continue;
            $p = & $p[$part];
        }
        return (array)$p;
    }
    public function getFlatten($root="")
    {
        $items = array();
        foreach ($this->getTree($root) as $node=>$children) {
            if ($node==="/") continue;
            $items[$root."/".$node] = $children["/"];
            foreach ($this->getFlatten($root."/".$node) as $k=>$v) {
                $items[$k] = $v;
            }
        }
        return $items;
    }

// --

    public static function scanDir($dir, $map_filter=null)
    {
        $paths = new PathCollection();
        foreach (new \DirectoryIterator($dir) as $f) {
            if ($f->isDot()) continue;
            $file = $f->getPathname();
            if ($map_filter) {
                $file = call_user_func($map_filter, $file);
                if (strlen($file)===0) continue;
            }
            $paths->addPath($file, array("is_dir"=>$f->isDir()));
            if ($f->isDir()) $paths->addPaths(self::scanDir($file, $map_filter));
        }
        return $paths;
    }
}
