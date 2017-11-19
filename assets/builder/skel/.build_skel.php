<?php
    $dir = __DIR__;
    return array(
        "deploy" => array(
            "schema" => array(
                function ($schema) {
                    $schema->getSchema()->fetch("config.routing", array("schema"=>$schema),
                        "/config/routing.config.php");
                    $schema->getSchema()->fetch("config.auth", array("schema"=>$schema),
                        "/config/auth.config.php");
                },
            ),
            "table" => array(
                function ($table) {
                    if ($table->hasDef()) {
                        // Tableクラス
                        $table->getSchema()->fetch("classes.table", array("table"=>$table),
                            "/app/Table/".$table->getClassName().".php");
                    }
                },
            ),
            "col" => array(
                function ($col) {
                },
            ),
            "enum" => array(
                function ($enum) {
                    // Enumクラス
                    $enum->getSchema()->fetch("classes.enum", array("enum"=>$enum),
                        "/app/Enum/".$enum->getClassName().".php");
                },
            ),
            "enum_set" => array(
                function ($enum_set) {
                },
            ),
            "role" => array(
                function ($role) {
                    // ヘッダー/フッターHTMLファイル
                    $role->getSchema()->fetch("include_html.header", array("role"=>$role),
                        "/html".$role->getHeaderPath());
                    $role->getSchema()->fetch("include_html.footer", array("role"=>$role),
                        "/html".$role->getFooterPath());
                    // RoleControllerクラス
                    $role->getSchema()->fetch("classes.role_controller", array("role"=>$role),
                        "/app/Controller/".$role->getRoleControllerClassName().".php");
                    // Roleクラス
                    //$role->getSchema()->fetch("classes.role", array("role"=>$role),
                    //    "/app/Role/".$role->getClassName().".php");
                },
            ),
            "controller" => array(
                function ($controller) {
                    // Controllerクラス
                    $controller->getSchema()->fetch("classes.controller", array("controller"=>$controller),
                        "/app/Controller/".$controller->getClassName().".php");
                },
            ),
            "pageset" => array(
                function ($pageset) {
                },
            ),
            "page" => array(
                function ($page) {
                    if ($page->hasHtml()) {
                        // pageのHtmlファイル
                        $page->getSchema()->fetch("parts.page_frame", array("page"=>$page),
                            "/html".$page->getPathFile());
                    }
                },
            ),
            "mail" => array(
                function ($mail) {
                    $mail->getSchema()->fetch($mail->getTemplateEntry(), array("mail"=>$mail),
                        "/mail/".$mail->getTemplateFile());
                },
            ),
        ),
        "pageset" => array(
            "index" => array(
                "index_page" => "index",
                "controller.template_file" => $dir."/pageset/index/index_controller.php",
                "pages.index.template_file" => $dir."/pageset/index/index.html",
                "pages.static.template_file" => null,// $dir."/pageset/index/static.html",
                "pages.index.label" => null,
            ),
            "login" => array(
                "index_page" => "login",
                "controller.template_file" => $dir."/pageset/login/login_controller.php",
                "pages.login.template_file" => $dir."/pageset/login/login.html",
                "pages.exit.template_file" => null,// $dir."/pageset/login/exit.html",
                "pages.login.label" => null,
            ),
            "reminder" => array(
                "index_page" => "reminder",
                "controller.template_file" => $dir."/pageset/reminder/reminder_controller.php",
                "pages.reminder.template_file" => $dir."/pageset/reminder/reminder.html",
                "pages.send.template_file" => $dir."/pageset/reminder/send.html",
                "pages.reset.template_file" => $dir."/pageset/reminder/reset.html",
                "pages.complete.template_file" => $dir."/pageset/reminder/complete.html",
                "pages.reminder.label" => null,
                "pages.send.label" => "URL通知",
                "pages.reset.label" => "パスワード入力",
                "pages.complete.label" => "パスワード更新完了",
                "mail_template.template_file" => $dir."/pageset/reminder/mail_template.php",
            ),
            "show" => array(
                "index_page" => "list",
                "controller.template_file" => $dir."/pageset/show/show_controller.php",
                "pages.list.template_file" => $dir."/pageset/show/list.html",
                "pages.detail.template_file" => $dir."/pageset/show/detail.html",
                "pages.list.label" => null,
                "pages.detail.label" => "詳細",
            ),
            "form" => array(
                "index_page" => "form",
                "controller.template_file" => $dir."/pageset/form/form_controller.php",
                "pages.form.template_file" => $dir."/pageset/form/form.html",
                "pages.confirm.template_file" => $dir."/pageset/form/confirm.html",
                "pages.complete.template_file" => $dir."/pageset/form/complete.html",
                "pages.form.label" => "入力",
                "pages.confirm.label" => "確認",
                "pages.complete.label" => "完了",
                "mail_template.template_file" => $dir."/pageset/form/mail_template.php",
            ),
            "delete" => array(
                "index_page" => "delete",
                "controller.template_file" => $dir."/pageset/delete/delete_controller.php",
                "pages.delete.template_file" => null,// $dir."/pageset/delete/delete.html",
            ),
            "csv" => array(
                "index_page" => "download",
                "controller.template_file" => $dir."/pageset/csv/csv_controller.php",
                "pages.download.template_file" => null,// $dir."/pageset/csv/download.html",
            ),
            "import" => array(
                "index_page" => "import",
                "controller.template_file" => $dir."/pageset/import/import_controller.php",
                "pages.import.template_file" => $dir."/pageset/import/import.html",
                "pages.complete.template_file" => null,// $dir."/pageset/csv/complete.html",
                "pages.import.label" => "アップロード",
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
            "auth.template_file" => $dir."/config/auth.config.php",
        ),
        "parts" => array(
            "page_frame.template_file" => $dir."/parts/page_frame.html",
            "page_method_dec.template_file" => $dir."/parts/page_method_dec.php",
            "col_input.template_file" => $dir."/parts/col_input.html",
            "col_show.template_file" => $dir."/parts/col_show.html",
            "col_mail.template_file" => $dir."/parts/col_mail.php",
        ),
    );
