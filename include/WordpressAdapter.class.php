<?php

/**
*
*/
class WordpressAdapter {
    /**
     *
     */
    public static function wp_load ($wp_root_dir=null)
    {
        if (defined("WP_LOADED")) {
            return;
        }
        if ( ! $wp_root_dir) {
            $wp_root_dir =registry("Path.wp_root_dir");
        }

        define('WP_USE_THEMES', false);
        require_once($wp_root_dir."/wp-load.php");

        define('WP_LOADED', $wp_root_dir);
    }
}


    //-------------------------------------
    // 日付けを表示用にフォーマットして返す
    function conv_date_format ($format_str, $date) {

        return date($format_str, strtotime($date));

    }

    //-------------------------------------
    // 正しい日付けかを判定する
    function check_correct_date ($date) {

        $datetime_arr =explode(" ", $date);
        $date_arr =explode("-", $datetime_arr[0]);

        return  checkdate($date_arr[1], $date_arr[2], $date_arr[0]);

    }

    //-------------------------------------
    // 本文テキストのWPショートコードを解釈して表示する
    function convert_content ($post_content) {

        return nl2br(do_shortcode($post_content));

    }

    //-------------------------------------
    // 敬称をつけるかどうか判断して名前を返す
    function get_add_title_name ($name, $name_title) {

        if ($name_title) {
            $name .="さん";
        }

        return $name;

    }

    //-------------------------------------
    // 検索条件に基づいた記事を一件取得する
    function get_post_one ($args) {

        if (is_array($args)) {

            $defult_cond =array("posts_per_page" =>1);
            $query_cond =array_merge($args, $defult_cond);

        } elseif ($args) {

            $query_cond =array(
                "p" =>$args,
                "post_type" =>"any",
                "posts_per_page" =>1,
                "post_status" =>"publish"
            );
        }

        if ($query_cond) {

            // 記事データを取得
            $post =array_shift(query_posts($query_cond));

            if ($post) {
                // 記事に付随する各データを取得
                $post =add_relation_field($post);
            }

            return $post;
        }
    }

    //-------------------------------------
    // 検索条件に基づいた記事の一覧を取得する
    function get_post_list ($args =null) {

        // 記事を取得
        $posts =query_posts($args);

        foreach ($posts as $post_k => $post_v) {

            // 記事に付随するデータを取得
            $posts[$post_k] =add_relation_field($post_v);

        }

        return $posts;
    }

    //-------------------------------------
    // カスタムフィールドのデータを取得する
    function add_custom_field ($post) {

        // カスタムフィールドの取得
        $metas =$post->__get("");

        // カスタムフィールドの値を記事のオブジェクトに追加
        foreach ($metas as $meta_k => $meta_v) {

            if (count($meta_v) < 2 && $meta_v) {

                $post->$meta_k =array_shift($meta_v);

            } else if (count($v) >= 2 && $v) {

                $post->$meta_k =$meta_v;

            }

        }
        return $post;
    }

    //-------------------------------------
    // 記事のカテゴリ情報を取得
    function add_relation_cat ($post){

        // 投稿タイプの判断
        if($post->post_type =="post") {
            // カテゴリー情報を取得
            $category_obj =get_the_category($post->ID);
        } else {
            // タクソノミーのカテゴリー情報を取得
            $category_obj =wp_get_post_terms($post->ID, $post->post_type.'-cat', array('fields'=>'all'));
        }

        if (!empty($category_obj) && empty($category_obj->errors)) {

            foreach ($category_obj as $k => $v) {

                if ($post->post_type =="post") {
                    $parent_id =$v->category_parent;
                } else {
                    $parent_id =$v->parent;
                }

                if ($parent_id) {
                    // 親のカテゴリが存在する時、子のカテゴリを取得する
                    $children_cat[] =$category_obj[$k];
                } else {
                    // 親カテゴリを持たない場合、親カテゴリとして取得
                    $parent_cat[] =$category_obj[$k];
                }

            }

            if(!empty($parent_cat)){
                $post->parent_cat =$parent_cat;
            }

            if (!empty($children_cat)) {
                $post->children_cat =$children_cat;
            }
        }

        return $post;
    }

    //-------------------------------------
    // 記事のデータに紐付く各データを取得する
    function add_relation_field($post) {

        // カスタムフィールドのデータを取得
        $post =add_custom_field($post);

        // カテゴリ情報を取得
        $post =add_relation_cat($post);

        return $post;
    }

    //-------------------------------------
    // 関連記事の取得
    function get_relation_post ($ser_relation_arr) {

        if ($ser_relation_arr) {

            // 保存用表現から PHP の値を生成する
            $relation_arr =unserialize($ser_relation_arr);

            // 一件取得
            if (count($relation_arr) == 1) {

                // 条件指定
                $query_cond =array(
                    "p" =>$relation_arr[0],
                    "post_type" =>'any',
                    "post_status" =>'publish',
                );

                // 記事データを取得
                $simple_post =array_shift(get_posts($query_cond));

                if ($simple_post) {
                    // 記事に付随するデータを追加
                    $post =add_relation_field($simple_post, $c_field_flag, $category_flag);
                }

                return $post;

            } else {//複数件取得

                foreach($relation_arr as $v) {

                    // 条件指定
                    $query_cond =array(
                        "p" =>$v,
                        "post_type" =>'any',
                    );

                    // 記事データを取得
                    $simple_post =array_shift(get_posts($query_cond));

                    // 記事に付随するデータを追加
                    $posts[] =add_relation_field($simple_post, $c_field_flag, $category_flag);

                }

                return $posts;
            }
        }
    }

    //-------------------------------------
    // 検索条件に基づいた固定記事を取得する
    function get_fix_post ($id) {

        $post =array_shift(get_pages($id));

        // カスタムフィールドの取得
        $metas =get_post_meta($post->ID);

        // カスタムフィールドの値を記事のオブジェクトに追加
        foreach ($metas as $meta_k => $meta_v) {

            if (substr($meta_k,0,1) != "_") {
                if (count($meta_v) < 2 && $meta_v) {
                    $post->$meta_k =array_shift($meta_v);
                } else if (count($v) >= 2 && $v) {
                    $post->$meta_k =$meta_v;
                }
            }

        }

        return $post;

    }

    //-------------------------------------
    // 検索条件に基づいたカスタム投稿記事の一覧を取得する
    function get_castom_post_list ($args =null, $castom_name) {

        $posts =query_posts($args);

        foreach ($posts as $post_k => $post_v) {

            // カスタムフィールドの取得
            $metas =get_post_meta($post_v->ID);

            // カスタムフィールドの値を記事のオブジェクトに追加
            foreach ($metas as $meta_k => $meta_v) {

                if (substr($meta_k,0,1) != "_") {
                    if (count($meta_v) < 2 && $meta_v) {
                        $posts[$post_k]->$meta_k =array_shift($meta_v);
                    } else if (count($v) >= 2 && $v) {
                        $posts[$post_k]->$meta_k =$meta_v;
                    }
                }

            }

            // タクソノミー情報を取得
            $tax =array_shift(wp_get_post_terms($post_v->ID, $castom_name, array('fields'=>'all')));

            if ($tax) {
                // カテゴリー情報を記事のオブジェクトに追加
                foreach ($tax as $tax_k => $tax_v) {

                    $posts[$post_k]->$tax_k =$tax_v;

                }
            }

        }

        return $posts;

    }

    //-------------------------------------
    // 記事からカテゴリーIDを取得する
    function get_cat_id_array_by_post ($post, $children_flag=null) {

        $cat_array =array();

        foreach ($post->parent_cat as $p_cat) {
            array_push($cat_array, $p_cat->term_id);
        }

        if ($children_flag) {
            foreach ($post->children_cat as $c_cat) {
                array_push($cat_array, $c_cat->term_id);
            }
        }

        return $cat_array;
    }

    //-------------------------------------
    // 画像のURLを取得する
    function get_image_url ($id, $type="full") {

        $imgs =wp_get_attachment_image_src($id, $type);

        return $imgs[0];
    }

    //-------------------------------------
    // ファイルの容量を取得する
    function get_file_size ($id) {

        return floor(filesize(get_attached_file($id))/1000);

    }

    //-------------------------------------
    // 記事データから画像情報の配列を整形して返す
    function get_conv_image_arr ($post) {

        foreach ($post as $k => $v) {
            if (preg_match('/image\_\d/', $k, $m) && $v) {
                $img_key_arr =explode("_", $k);
                $img_arr[$img_key_arr[1]][$k] =$v;
            }
        }

        return $img_arr;

    }

    //-------------------------------------
    // 現在リクエストしているsqlクエリを表示する
    function print_query () {

        echo $GLOBALS['wp_query']->request;

    }

    //-------------------------------------
    // 現在表示している詳細画面のページング情報を取得する
    function get_detail_paging_list ($post_lists, $post_id) {

        $paging_list =array();

        foreach ($post_lists as $v) {
            if ($v->ID == $post_id && empty($paging_list)) {
                $paging_list["prev"] =$befor_id;
            } elseif (!empty($paging_list)) {
                $paging_list["next"] =$v->ID;
                break;
            }
            $befor_id =$v->ID;
        }

        return $paging_list;
    }

    //-------------------------------------
    // pagerの作成
    function build_pager ($offset, $length, $total ,$slider=10) {

        $pager =array();

        $total_dup =$total ? $total : 1;

        // 総ページ数
        $pages =ceil($total_dup/$length);
        $pager['numpages'] =$pages;

        // 最初のページと最後のページ
        $pager['firstpage'] =1;
        $pager['lastpage'] =$pages;

        // ページ配列の作成
        $pager['pages'] = array();

        for ($i=1; $i <= $pages; $i++) {

            $coffset = $length * ($i-1);

            $pager['pages'][$i] =$coffset;

            if ($coffset == $offset) {

                $pager['current'] = $i;
            }
        }

        if( ! isset($pager['current'])) {

            $pager['current'] =0;
        }

        // ページ長
        if ($maxpages) {

            $radio = floor($maxpages/2);
            $minpage = $pager['current'] - $radio;

            if ($minpage < 1) {

                $minpage = 1;
            }

            $maxpage = $pager['current'] + $radio - 1;

            if ($maxpage > $pager['numpages']) {

                $maxpage = $pager['numpages'];
            }

            $pager['maxpages'] = $maxpages;

        } else {

            $pager['maxpages'] = null;
        }

        // 前ページ
        $prev = $offset - $length;
        $pager['prev'] = ($prev >= 0) ? $prev : null;

        // 次ページ
        $next = $offset + $length;
        $pager['next'] = ($next < $total) ? $next : null;

        // 残りのページ数
        if ($pager['current'] == $pages) {

            $pager['remain'] = 0;
            $pager['to'] = $total;

        } else {

            if ($pager['current'] == ($pages - 1)) {

                $pager['remain'] = $total - ($length*($pages-1));

            } else {

                $pager['remain'] = $length;
            }

            $pager['to'] = $pager['current'] * $length;
        }

        $pager['from'] = (($pager['current']-1) * $length)+1;
        $pager['total'] =$total;
        $pager['offset'] =$offset + 1;
        $pager['length'] =$length;

        // スライダーの構築
        if ($slider) {

            $pager =build_slider($pager,$slider);
        }

        return $pager;
    }

    //-------------------------------------
    // スライダーの構築
    function build_slider ($pager ,$slider){

        $pager['pages_slider'] =array();

        $start =1;
        $prev_set =null;
        $next_set =$slider+1;
        $current =$pager['current'];
        $pages_count =count($pager['pages']);

        if ($current+ceil($slider/2) >= $pages_count
                && $pages_count-$slider>0) {

            $start =$pages_count - $slider + 1;

        } elseif ($current-floor($slider/2) > 0) {

            $start =$current - floor($slider/2);
        }

        if ($pages_count > $slider) {
            for ($i=$start; $i<$start+$slider && $i<=$pages_count; $i++) {

                $pager['pages_slider'][$i] =$pager['pages'][$i];
            }
        } else {
            $pager['pages_slider'] =$pager['pages'];
        }

        $pager['slider_prev'] =isset($pager['pages'][$pager['current']-($slider-1)/2-1])
                ? $pager['pages'][$pager['current']-($slider-1)/2-1]
                : null;

        $pager['slider_next'] =isset($pager['pages'][$pager['current']+($slider-1)/2+1])
                ? $pager['pages'][$pager['current']+($slider-1)/2+1]
                : null;

        $pager['pages_raw'] =$pager['pages'];
        $pager['pages'] =$pager['pages_slider'];

        return $pager;
    }
