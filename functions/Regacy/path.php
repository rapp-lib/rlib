<?php

    //-------------------------------------
    // PathからPageを得る（主にRouting時）
    // extract_url_paramsでURL内パラメータも取得
    function path_to_page ($path, $extract_url_params=false) {
        report_warning("@deprecated path_to_page");
        return route($path)->getPage();
    }

    //-------------------------------------
    // PageからPathを得る
    function page_to_path ($page) {
        report_warning("@deprecated page_to_path");
        return route($page)->getPath();
    }

    //-------------------------------------
    // Pathからファイル名を得る
    function path_to_file ($path) {
        report_warning("@deprecated path_to_file");
        return route($path)->getPage();
    }

    //-------------------------------------
    // ファイル名からURLを得る
    function file_to_url ($file, $full_url=false) {
        report_warning("@deprecated file_to_url");
        return $full_url ? route("file:".$file)->getFullUrl() : route("file:".$file)->getUrl();
    }

    //-------------------------------------
    // URLからPathを得る
    function url_to_path ($url, $index_filename="index.html") {
        report_warning("@deprecated url_to_path");
        return route("url:".$path)->getPath();
    }

    //-------------------------------------
    // Pageからファイル名を得る
    function page_to_file ($page) {
        report_warning("@deprecated page_to_file");
        return route($page)->getFile();
    }


    //-------------------------------------
    // 対象ファイルがDocumentRoot配下にあるか確認
    function is_public_file ($file) {
        report_warning("@deprecated is_public_file");
        return strlen(route("file:".$file)->getUrl());
    }

    //-------------------------------------
    // 指定したpathがリストに該当するか
    function in_path ($path, $list) {
        report_warning("@deprecated in_path");

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
