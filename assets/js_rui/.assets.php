<?php
    if ( ! $frontend) { return; }

    // jquery
    $frontend->registerJsUrl("jquery", "1.10.2", '//code.jquery.com/jquery-1.10.2.min.js');
    $frontend->registerJsUrl("jquery", "2.2.4", '//code.jquery.com/jquery-2.2.4.min.js');

    // underscore
    $frontend->registerJsUrl("underscore", "1.6.0", '//cdnjs.cloudflare.com/ajax/libs/underscore.js/1.6.0/underscore-min.js');

    // jquery.datepick
    $frontend->registerJsUrl("jquery.datepick", "1", $url.'/jquery.datepick/jquery.datepick.min.js')
        ->required("jquery","*")
        ->required("jquery.datepick-ja")
        ->required("jquery.datepick-css");
    $frontend->registerCssUrl("jquery.datepick-css", "1", $url.'/jquery.datepick/jquery.datepick.css');
    $frontend->registerJsUrl("jquery.datepick-ja", "1", $url.'/jquery.datepick/jquery.datepick-ja.js');

    // jquery.colorbox
    $frontend->registerCssUrl("jquery.colorbox", "1.6,4", '//cdnjs.cloudflare.com/ajax/libs/jquery.colorbox/1.6.4/i18n/jquery.colorbox-ja.js');

    // nicEdit
    $frontend->registerJsUrl("nicEdit", "0.9.24", '//cdn.jsdelivr.net/nicedit/0.9r24/nicEdit.js');
    $frontend->registerJsUrl("nicEdit", "0.9", $url.'/nicEdit.js')
        ->required("nicEdit-css");
    $frontend->registerCssUrl("nicEdit-css", "0.9", $url.'/nicEdit.css');

    // rui.require
    $frontend->registerJsUrl("rui.require", "1", $url.'/rui.js');

    // rui.ajaxr
    $frontend->registerJsUrl("rui.ajaxr", "1", $url.'/jquery.ajaxr/jquery.ajaxr.js')
        ->required("jquery","2.*");

    // rui.mi
    $frontend->registerJsUrl("rui.mi", "2", $url.'/jquery.mi/jquery.mi.js')
        ->required("jquery","2.*");
    $frontend->registerJsUrl("rui.mi", "1", $url.'/rui.mi/index.js')
        ->required("jquery","2.*");

    // rui.wysiwyg
    $frontend->registerJsUrl("rui.wysiwyg", "1", $url.'/rui.wysiwyg/index.js')
        ->required("jquery","2.*")
        ->required("nicEdit");

    // rui.zip
    $frontend->registerJsUrl("rui.zip", "1", $url.'/rui.zip/index.js')
        ->required("jquery","2.*");
    $frontend->registerJsUrl("rui.zip", "2", $url.'/ZipCoder/ZipCoder.js')
        ->required("jquery","2.*");

    // rui.syncselect
    $frontend->registerJsUrl("rui.syncselect", "1", $url.'/rui.syncselect/index.js')
        ->required("jquery","2.*");

    // rui.synchro
    $frontend->registerJsUrl("rui.synchro", "1", $url.'/Synchro/Synchro.js')
        ->required("jquery","2.*");

    // rui.viframe
    $frontend->registerJsUrl("rui.viframe", "1", $url.'/jquery.viframe/jquery.viframe.js')
        ->required("jquery","2.*");
    $frontend->registerJsUrl("rui.vifupload", "1", $url.'/jquery.viframe/jquery.vifupload.js')
        ->required("rui.viframe");
    $frontend->registerJsUrl("rui.vifhistory", "1", $url.'/jquery.viframe/jquery.vifhistory.js')
        ->required("rui.viframe");

    // rui.japcal
    $frontend->registerJsUrl("rui.japcal", "1", $url.'/index.js')
        ->required("jquery","2.*");

    // rui.datefix
    $frontend->registerJsUrl("rui.datefix", "1", $url.'/index.js')
        ->required("jquery","2.*");

    // rui.popup
    $frontend->registerJsUrl("rui.popup", "1", $url.'/index.js')
        ->required("jquery","2.*");

    // rui.datepick
    $frontend->registerJsUrl("rui.datepick", "1", $url.'/jquery.datepick/index.js')
        ->required("jquery.datepick");
