<?php
    if ( ! $asset) { return; }

    // jquery
    $asset->registerJsUrl("jquery:1.10.2", '//code.jquery.com/jquery-1.10.2.min.js');
    $asset->registerJsUrl("jquery:2.2.4", '//code.jquery.com/jquery-2.2.4.min.js');

    // underscore
    $asset->registerJsUrl("underscore:1.6.0", '//cdnjs.cloudflare.com/ajax/libs/underscore.js/1.6.0/underscore-min.js');

    // jquery.datepick
    $asset->registerJsUrl("jquery.datepick", $url.'/jquery.datepick/jquery.datepick.min.js')
        ->required("jquery")
        ->required("jquery.datepick-ja")
        ->required("jquery.datepick-css");
    $asset->registerCssUrl("jquery.datepick-css", $url.'/jquery.datepick/jquery.datepick.css');
    $asset->registerJsUrl("jquery.datepick-ja", $url.'/jquery.datepick/jquery.datepick-ja.js');

    // jquery.colorbox
    $asset->registerCssUrl("jquery.colorbox:1.6,4", '//cdnjs.cloudflare.com/ajax/libs/jquery.colorbox/1.6.4/i18n/jquery.colorbox-ja.js');

    // nicEdit
    $asset->registerJsUrl("nicEdit:0.9.24", '//cdn.jsdelivr.net/nicedit/0.9r24/nicEdit.js');
    $asset->registerJsUrl("nicEdit:0.9", $url.'/nicEdit.js')
        ->required("nicEdit-css");
    $asset->registerCssUrl("nicEdit-css:0.9", $url.'/nicEdit.css');

    // ckeditor
    $asset->registerJsUrl("ckeditor:4.6.0", "//cdn.ckeditor.com/4.6.0/standard/ckeditor.js");

    // rui.require
    $asset->registerJsUrl("rui.require", $url.'/rui.require/rui.js');

    // rui.ajaxr
    $asset->registerJsUrl("rui.ajaxr", $url.'/jquery.ajaxr/jquery.ajaxr.js')
        ->required("jquery");

    // rui.mi
    $asset->registerJsUrl("rui.mi:2.0.0", $url.'/jquery.mi/jquery.mi.js')
        ->required("jquery");
    $asset->registerJsUrl("rui.mi:1.0.0", $url.'/rui.mi/index.js')
        ->required("jquery");

    // rui.wysiwyg
    $asset->registerJsUrl("rui.wysiwyg", $url.'/rui.wysiwyg/index.js')
        ->required("jquery")
        ->required("nicEdit");

    // rui.zip
    $asset->registerJsUrl("rui.zip:1.0.0", $url.'/rui.zip/index.js')
        ->required("jquery");
    $asset->registerJsUrl("rui.zip:2.0.0", $url.'/ZipCoder/ZipCoder.js')
        ->required("jquery");

    // rui.syncselect
    $asset->registerJsUrl("rui.syncselect", $url.'/rui.syncselect/index.js')
        ->required("jquery");

    // rui.synchro
    $asset->registerJsUrl("rui.synchro", $url.'/Synchro/Synchro.js')
        ->required("jquery");

    // rui.viframe
    $asset->registerJsUrl("rui.viframe", $url.'/jquery.viframe/jquery.viframe.js')
        ->required("jquery");
    $asset->registerJsUrl("rui.vifupload", $url.'/jquery.viframe/jquery.vifupload.js')
        ->required("rui.viframe");
    $asset->registerJsUrl("rui.vifhistory", $url.'/jquery.viframe/jquery.vifhistory.js')
        ->required("rui.viframe");

    // rui.japcal
    $asset->registerJsUrl("rui.japcal", $url.'/rui.japcal/index.js')
        ->required("jquery");

    // rui.datefix
    $asset->registerJsUrl("rui.datefix", $url.'/rui.datefix/index.js')
        ->required("rui.require")
        ->required("jquery");

    // rui.popup
    $asset->registerJsUrl("rui.popup", $url.'/rui.popup/index.js')
        ->required("rui.require")
        ->required("jquery");

    // rui.datepick
    $asset->registerJsUrl("rui.datepick", $url.'/jquery.datepick/index.js')
        ->required("jquery.datepick");

    // rui.show-erors
    $asset->registerJsUrl("rui.show-errors", $url.'/rui.show-errors/rui.show-errors.js')
        ->required("jquery");

    // input_plugin
    $asset->registerJsUrl("InputPluginRegistry", $url.'/input_plugin/InputPluginRegistry.js')
        ->required("jquery");
    $asset->registerJsUrl("input_plugin.zero_option", $url.'/input_plugin/zero_option.js')
        ->required("InputPluginRegistry");
    $asset->registerJsUrl("input_plugin.split_text", $url.'/input_plugin/split_text.js')
        ->required("InputPluginRegistry");
    $asset->registerJsUrl("input_plugin.show_uploaded_file", $url.'/input_plugin/show_uploaded_file.js')
        ->required("InputPluginRegistry")
        ->required("config.current_webroot_url");

    // config
    $asset->register("config.current_webroot_url", 'window.current_webroot_url = "'.app()->router->getWebroot()->getConfig("webroot_url").'";', "js_code");
