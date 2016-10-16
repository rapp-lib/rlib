<?php
namespace R\Plugin\Smarty\SmartyPlugin;

/**
 * @deprecated
 * {{a ...}}{{/a}}タグの実装
 */
class SmartyBlockA
{
    protected static $current_form = null;

    /**
     * @overload
     */
    public static function smarty_block ($params, $content, $smarty_template, $repeat)
    {
        return SmartyBlockA::linkageBlock("a", $params, $content, $template, $repeat);
    }

    /**
     * @deprecated
     * 旧LINK系のタグの構築（a/formタグで使用）
     */
    public static function linkageBlock ($type, $params, $content, $template, $repeat) {

        // 開始タグ処理
        if ($repeat) {

            if ($type == "form") {
                self::$current_form = $params;
            }

        // 終了タグ処理
        } else {

            if ($type == "form") {
                self::$current_form = null;
            }

            $attr_html ="";
            $url_params =array();
            $hidden_input_html ="";

            $dest_url =$params["href"]
                    ? $params["href"]
                    : $params["action"];
            $anchor =$params["anchor"];
            $method =$params["method"]
                    ? $params["method"]
                    : "post";
            $values =$params["values"];

            unset($params["href"]);
            unset($params["action"]);
            unset($params["anchor"]);
            unset($params["method"]);
            unset($params["values"]);

            // URLの決定

            // href/actionによるURL指定
            if ($dest_url) {

                $dest_url =apply_url_rewrite_rules($dest_url);

            // _pageによるURL指定
            } else if ($params["_page"]) {

                $dest_url =page_to_url($params["_page"]);

                if ( ! $dest_url) {

                    report_warning("Link page is-not routed.",array(
                        "page" =>$params["_page"],
                    ));
                }

                unset($params["_page"]);

            // _pathでのURL指定
            } elseif ($params["_path"]) {

                // 相対指定
                if (preg_match('!^\.!',$params["_path"])) {

                    $cur =dirname(registry('Request.request_path'));
                    $file =registry('Request.html_dir')."/".$cur."/".$params["_path"];
                    $dest_url =file_to_url(realpath($file));

                } else {

                    $dest_url =path_to_url($params["_path"]);
                }

                if ( ! $dest_url) {

                    report_warning("Lin path is-not routed.",array(
                        "path" =>$params["_path"],
                    ));
                }

                unset($params["_path"]);
            }

            // URLパラメータの付与

            // _query
            if ($params["_query"]) {

                if (is_string($params["_query"])) {

                    foreach (explode("&",$params["_query"]) as $kvset) {

                        list($k,$v) =explode("=",$kvset,2);
                        $url_params[$k] =$v;
                    }

                } else {

                    foreach ($params["_query"] as $k => $v) {

                        $url_params[$k] =$v;
                    }
                }

                unset($params["_query"]);
            }

            // パラメータの選別
            foreach ($params as $key => $value) {

                if (preg_match('!^_(.*)$!',$key,$match)) {

                    $param_name =$match[1];

                    if (is_array($url_params[$param_name]) && is_array($value)) {

                        $url_params[$param_name] =array_merge($url_params[$param_name],$value);

                    } else {

                        $url_params[$param_name] =$value;
                    }

                } else {

                    $attr_html .=' '.$key.'="'.$value.'"';
                }
            }

            $dest_url =url($dest_url,$url_params,$anchor);

            $html ="";

            // タグ別の処理
            if ($type == 'form') {

                $html .='<form method="'.$method.'" action="'.$dest_url.'"'.$attr_html.'>';
                $html .=$hidden_input_html;
                $html .=$content.'</form>';

            } elseif ($type == 'button') {

                $html .='<form method="'.$method.'" action="'.$dest_url.'"'.$attr_html.'>';
                $html .='<input type="submit" value="'.$content.'" /></form>';

            } elseif ($type == 'a') {

                $html .='<a href="'.$dest_url.'"'.$attr_html.'>';
                $html .=$content.'</a>';
            }

            print $html;
        }
    }
}