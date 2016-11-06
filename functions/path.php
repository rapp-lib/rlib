<?php

    //-------------------------------------
    // PathからPageを得る（主にRouting時）
    // extract_url_paramsでURL内パラメータも取得
    function path_to_page ($path, $extract_url_params=false) {
        return route($path)->getPage();
    }

    //-------------------------------------
    // PageからPathを得る
    function page_to_path ($page) {
        return route($page)->getPath();
    }

    //-------------------------------------
    // Pathからファイル名を得る
    function path_to_file ($path) {
        return route($path)->getPage();
    }

    //-------------------------------------
    // ファイル名からURLを得る
    function file_to_url ($file, $full_url=false) {
        return $full_url ? route("file:".$file)->getFullUrl() : route("file:".$file)->getUrl();
    }

    //-------------------------------------
    // PathからURLを得る（主にRedirectやHREFに使用）
    function path_to_url ($path, $full_url=false) {
        return $full_url ? route($path)->getFullUrl() : route($path)->getUrl();
    }

    //-------------------------------------
    // URLからPathを得る
    function url_to_path ($url, $index_filename="index.html") {
        return route("url:".$path)->getPath();
    }

    //-------------------------------------
    // Pageからファイル名を得る
    function page_to_file ($page) {
        return route($page)->getFile();
    }

    //-------------------------------------
    // PageからURLを得る（主にRedirectやHREFに使用）
    function page_to_url ($page, $full_url=false) {
        return $full_url ? route($page)->getFullUrl() : route($page)->getUrl();
    }

    //-------------------------------------
    // 対象ファイルがDocumentRoot配下にあるか確認
    function is_public_file ($file) {
        return strlen(route("file:".$file)->getUrl());
    }

    //-------------------------------------
    // 指定したpathがリストに該当するか
    function in_path ($path, $list) {

        $page =path_to_page($path);
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
