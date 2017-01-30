<?php
    builder()->registerTemplate("config.routing", $dir."/config/routing.config.php");
    builder()->registerTemplate("role.class", $dir."/login/MemberRole.php");
    builder()->registerTemplate("controller.class", $dir."/master/ProductMasterController.class.php");
    builder()->registerTemplate("page.html.index.index", $dir."/login/member_login.login.html");
    builder()->registerTemplate("page.html.index.index", $dir."/master/product_master.entry_exec.html");
    builder()->registerTemplate("page.html.index.index", $dir."/master/product_master.view_detail.html");
    builder()->registerTemplate("page.html.index.index", $dir."/master/product_master.view_list.html");
    builder()->registerTemplate("page.html.index.index", $dir."/master/product_master.entry_confirm.html");
    builder()->registerTemplate("page.html.index.index", $dir."/master/product_master.entry_csv_form.html");
    builder()->registerTemplate("page.html.index.index", $dir."/master/product_master.entry_form.html");
    builder()->registerTemplate("page.html.index.index", $dir."/index/product_master.index.html");
    builder()->registerTemplate("wrapper.html.footer", $dir."/wrapper/default_footer.html");
    builder()->registerTemplate("wrapper.html.header", $dir."/wrapper/default_header.html");
    builder()->registerTemplate("table.class", $dir."/table/MemberTable.php");
    builder()->registerTemplate("enum.class", $dir."/table/MemberEnum.php");
    //$dir."/_include/controller.php",
    //$dir."/_include/common.php",
    //$dir."/list/ProductPriceList.class.php",
    //$dir."/index/ProductMasterController.class.php",
    //$dir."/login/MemberLoginController.class.php",
    //$dir."/model/ProductModel.class.php",
