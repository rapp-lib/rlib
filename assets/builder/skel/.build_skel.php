<?php
    $dir = __DIR__;
    return array(
        "deploy" => array(
            "schema" => array(
                function ($schema) {
                    $schema->getSchema()->fetch("config.routing", array("schema"=>$schema),
                        "/config/routing.config.php");
                },
            ),
            "table" => array(
                function ($table) {
                    if ($table->hasDef()) {
                        // Tableクラス
                        $table->getSchema()->fetch("classes.table", array("table"=>$table),
                            "/app/Table/".$table->getClassName().".php");
                    }
                    if ($table->getEnum()) {
                        // Enumクラス
                        $table->getSchema()->fetch("classes.enum", array("table"=>$table),
                            "/app/Enum/".$table->getEnum()->getClassName().".php");
                    }
                },
            ),
            "role" => array(
                function ($role) {
                    // ヘッダー/フッターHTMLファイル
                    $role->getSchema()->fetch("include_html.header", array("role"=>$role),
                        "/html/".$role->getHeaderPath());
                    $role->getSchema()->fetch("include_html.footer", array("role"=>$role),
                        "/html/".$role->getFooterPath());
                    // Roleクラス
                    $role->getSchema()->fetch("classes.role", array("role"=>$role),
                        "/app/Role/".$role->getClassName().".php");
                    // RoleControllerクラス
                    $role->getSchema()->fetch("classes.role_controller", array("role"=>$role),
                        "/app/Controller/".$role->getRollControllerClassName().".php");
                },
            ),
            "controller" => array(
                function ($controller) {
                    // Controllerクラス
                    $controller->getSchema()->fetch("classes.controller", array("controller"=>$controller),
                        "/app/Enum/".$table->getClassName().".php");
                },
            ),
            "page" => array(
                function ($page) {
                    if ($page->hasHtml()) {
                        // pageのHtmlファイル
                        $page->getSchema()->fetch("frame.page", array("page"=>$page),
                            "/html/".$page->getPath());
                    }
                },
            ),
        ),
        "pageset" => array(
            "index" => array(
                "index_page" => "index",
                "controller.template_file" => $dir."/pageset/index/index_controller.php",
                "pages.index.template_file" => $dir."/pageset/index/index.html",
                "pages.static.template_file" => null,// $dir."/pageset/index/static.html",
            ),
            "login" => array(
                "index_page" => "form",
                "controller.template_file" => $dir."/pageset/login/login_controller.php",
                "pages.form.template_file" => $dir."/pageset/login/form.html",
                "pages.logout.template_file" => null,// $dir."/pageset/login/logout.html",
            ),
            "show" => array(
                "index_page" => "list",
                "controller.template_file" => $dir."/pageset/show/show_controller.php",
                "pages.list.template_file" => $dir."/pageset/show/list.html",
                "pages.detail.template_file" => $dir."/pageset/show/detail.html",
            ),
            "form" => array(
                "index_page" => "form",
                "controller.template_file" => $dir."/pageset/form/form_controller.php",
                "pages.form.template_file" => $dir."/pageset/form/form.html",
                "pages.confirm.template_file" => $dir."/pageset/master/confirm.html",
                "pages.complete.template_file" => $dir."/pageset/master/complete.html",
            ),
            "delete" => array(
                "index_page" => "delete",
                "controller.template_file" => $dir."/pageset/delete/delete_controller.php",
                "pages.delete.template_file" => null,// $dir."/pageset/delete/delete.html",
            ),
            "csv" => array(
                "index_page" => "form",
                "controller.template_file" => $dir."/pageset/csv/csv_controller.php",
                "pages.form.template_file" => $dir."/pageset/csv/form.html",
                "pages.confirm.template_file" => null,// $dir."/pageset/csv/confirm.html",
                "pages.complete.template_file" => null,// $dir."/pageset/csv/complete.html",
            ),
        ),
        "include_html" => array(
            "header.template_file" => $dir."/include_html/header.html",
            "footer.template_file" => $dir."/include_html/footer.html",
        ),
        "classes" => array(
            "role_controller.template_file" => $dir."/classes/RoleControllerClass.php",
            "controller.template_file" => $dir."/classes/ControllerClass.php",
            "table.template_file" => $dir."/classes/TableClass.php",
            "enum.template_file" => $dir."/classes/EnumClass.php",
            "role.template_file" => $dir."/classes/RoleClass.php",
        ),
        "config" => array(
            "routing.template_file" => $dir."/config/routing.config.php",
        ),
        "frame" => array(
            "page.template_file" => $dir."/frame/page.html",
            "page_method_dec.template_file" => $dir."/frame/page_method_dec.php",
        ),
    );
