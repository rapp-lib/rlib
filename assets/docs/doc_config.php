<?php
    return array(
        "output_dir"=>constant("R_APP_ROOT_DIR")."/tmp/docs/output",
        "overwrite_config"=>array(
            //"http.webroots.www.base_dir"=>constant("R_APP_ROOT_DIR")."/html",
        ),
        "docs"=>array(
            "db_reverse_schema"=>array(
                "format"=>'\R\Lib\Doc\Format\DbReverseSchemaCsvFormat',
                "writer"=>'\R\Lib\Doc\Writer\CsvWriter',
            ),
            "url_list"=>array(
                "format"=>'\R\Lib\Doc\Format\UrlListCsvFormat',
                "writer"=>'\R\Lib\Doc\Writer\CsvWriter',
            ),
        ),
    );
