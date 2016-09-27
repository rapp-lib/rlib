<?php
/*
    2016/07/06
        core/string.php内の全関数の移行
        fileToUrl以外は置き換え終了。fileToUrlはWebapp.class作成する必要あり。

        getPageToPathMap 確認OK
        relativePath 確認OK
        relativePage 確認OK
        pathToPage 確認OK
        inPath
        pageToPath 確認OK
        pathToFile 確認OK
        fileToUrl 確認OK
        pathToUrl 確認OK
        urlToPath 確認OK
        pageToFile 確認OK
        pageToUrl 確認OK
        isPublicFile
 */
namespace R\Lib\Core;

use R\Lib\Core\Vars;
use R\Lib\Core\Webapp;
/**
 *
 */
class Path {

    /**
     * [get_page_to_path_map description]
     * @param  boolean $flip [description]
     * @return [type]      [description]
     */
    public static function & getPageToPathMap ($flip=false)
    {

        static $cache;

        if ( ! $cache) {

            foreach ((array)Vars::registry("Routing.page_to_path") as $k1 => $v1) {

                foreach ($v1 as $k2 => $v2) {

                    $cache["page_to_path"][$k1.".".$k2] =$v2;
                }
            }

            $cache["path_to_page"] =array_flip($cache["page_to_path"]);
        }

        return $cache[$flip ? "path_to_page" : "page_to_path"];
    }

    /**
     * [relative_path description]
     * @param  string $path [description]
     * @return [type]      [description]
     */
    public static function relativePath ($path)
    {

        if (preg_match('!^\.(.*)$!',$path,$match)) {

            $path =Path::fileToUrl(dirname(Vars::registry("Request.request_file"))).$path;
        }

        return $path;
    }

    /**
     * [relative_page description]
     * @param  string $page [description]
     * @return [type]      [description]
     */
    public static function relativePage ($page)
    {

        if (preg_match('!^([^\.]+)?(?:\.([^\.]+))?$!',$page,$match)) {

            $page ="";
            $page .=$match[1] ? $match[1] : Vars::registry("Request.controller_name");
            $page .=".";
            $page .=$match[2] ? $match[2] : "index";

        } elseif ($page == ".") {

            $page ="";
            $page .=Vars::registry("Request.controller_name");
            $page .=".";
            $page .=Vars::registry("Request.action_name");
        }

        return $page;
    }

    /**
     * [path_to_page description]
     * @param  string  $path [description]
     * @param  boolean  $extract_url_params    [description]
     * @return [type]           [description]
     */
    public static function pathToPage ($path, $extract_url_params=false)
    {

        $path =Path::relativePath($path);
        $path_to_page =& Path::getPageToPathMap(true);
        $page =$path_to_page[$path];
        $params =array();

        // 解決できない場合はパターンマッチ
        if ( ! $page) {

            foreach ($path_to_page as $to_path => $to_page) {

                if (preg_match_all('!\[([^\]]+)\]!',$to_path,$matches)) {

                    $param_keys =$matches[1];
                    $to_path_ptn ='!'.preg_quote($to_path,'!').'!';
                    $to_path_ptn =preg_replace('!\\\\\[.*?\\\\\]!','(.*?)',$to_path_ptn);
                    $to_path_ptn =preg_replace('!\(\.\*\?\)\!$!','(.*)!',$to_path_ptn);

                    if (preg_match($to_path_ptn,$path,$match)) {

                        array_shift($match);

                        foreach ($match as $k => $v) {

                            $params[$param_keys[$k]] =$v;
                        }

                        $path =$to_path;
                        $page =$to_page;
                    }
                }
            }
        }

        return $extract_url_params
                ? array($page,$path,$params)
                : $page;
    }

    /**
     * [in_path description]
     * @param  string  $path [description]
     * @param  [type]  $list    [description]
     * @return [type]           [description]
     */
    public static function inPath ($path, $list) {

        $page =Path::pathToPage($path);
        $result =false;

        foreach ((array)$list as $item) {

            // path指定
            if (preg_match('!^(?:path:)?(/[^\*]*)(\*)?$!',$item,$match)) {

                $result =$match[2]
                        ? strpos($path,$match[1])===0
                        : $path==$match[1];

            // page指定
            } elseif ($page && preg_match('!^(?:page:)?([^\*]+)(\*)?$!',$item,$match)) {

                $result =$match[2]
                        ? strpos($page,$match[1])===0
                        : $page==$match[1];
            }

            if ($result) {

                return $item;
            }
        }

        return false;
    }

    /**
     * [page_to_path description]
     * @param  string  $path [description]
     * @return [type]           [description]
     */
    public static function pageToPath ($page) {

        $page =Path::relativePage($page);
        $page_to_path =& Path::getPageToPathMap();
        $path =$page_to_path[$page];

        return $path;
    }

    /**
     * [path_to_file description]
     * @param  string  $path [description]
     * @return [type]           [description]
     */
    public static function pathToFile ($path) {

        $path =Path::relativePath($path);
        return Vars::registry("Path.html_dir").$path;
    }

    /**
     * [file_to_url description]
     * @param  string  $file [description]
     * @param  boolean  $full_url    [description]
     * @return [type]           [description]
     */
    public static function fileToUrl ($file, $full_url=false) {

        $document_root_url =$full_url
                ? Vars::registry('!Path.document_root_url')
                : "";
        $document_root_url =preg_replace('!/$!','',$document_root_url);

        // https指定であればURLの先頭を変更
        if ($full_url === "https") {

            if ($document_root_ssl_url =Vars::registry('Path.document_root_ssl_url')) {

                $document_root_url =preg_replace('!/$!','',$document_root_ssl_url);

            } else {

                $document_root_url =preg_replace('!^http://!','https://',$document_root_url);
            }

        // httpから始まるURLを返す必要がなければ切り取る
        } elseif ( ! $full_url) {

            $document_root_url =preg_replace('!^https?://[^/]+(/|$)!','',$document_root_url);
        }

        $pattern ='!^'.preg_quote(Vars::registry('!Path.document_root_dir')).'/?!';

        // DocumentRoot外のファイルにURLは存在しない
        if ( ! preg_match($pattern,$file)) {

            return null;
        }

        $url =preg_replace($pattern,$document_root_url."/",$file);
        $url =Webapp::applyUrlRewriteRules($url);

        return $url;
    }

    /**
     * [path_to_url description]
     * @param  string  $page [description]
     * @return [type]           [description]
     */
    public static function pathToUrl ($page, $full_url=false) {

        $file =Path::pathToFile($page);
        $url =Path::fileToUrl($file,$full_url);

        return $url;
    }

    /**
     * [url_to_path description]
     * @param  string  $url [description]
     * @param  [type]  $index_filename    [description]
     * @return [type]           [description]
     */
    public static function urlToPath ($url, $index_filename="index.html") {

        $document_root_dir =Vars::registry("Path.document_root_dir");
        $html_dir =Vars::registry("Path.html_dir");

        $url =preg_replace('!^https?://[^/]+!','',$url);
        $url =preg_replace('!\#.*$!','',$url);
        $url =preg_replace('!\?.*$!','',$url);

        $file =$document_root_dir.$url;
        $file =preg_replace('!/$!','/'.$index_filename,$file);
        $path =preg_replace('!^'.preg_quote($html_dir).'!','',$file);

        return $path;
    }

    /**
     * [page_to_file description]
     * @param  string  $page [description]
     * @return [type]           [description]
     */
    public static function pageToFile ($page) {

        $page =Path::relativePage($page);
        $path =Path::pageToPath($page);
        $file =$path
                ? Vars::registry("Path.html_dir").$path
                : null;

        return $file;
    }

    /**
     * [page_to_url description]
     * @param  string  $page [description]
     * @param  boolean  $full_url [description]
     * @return [type]           [description]
     */
    public static function pageToUrl ($page, $full_url=false) {

        $page =Path::relativePage($page);
        $file =Path::pageToFile($page);
        $url =Path::fileToUrl($file,$full_url);

        return $url;
    }

    /**
     * [is_public_file description]
     * @param  [type]  $file [description]
     * @return [type]           [description]
     */
    public static function isPublicFile ($file) {

        $dir =Vars::registry("Path.document_root_dir");

        return preg_match('!^'.preg_quote($dir,'!').'(.*?)$!',$file,$match)
                && ! preg_match('!/\.\.+/!',$match[1]);
    }
}