<?php
    return array(
        "ext" => array(
            "jquery" => array("//code.jquery.com/jquery-2.2.4.min.js"),
            //"jquery" => array("//code.jquery.com/jquery-3.2.1.slim.min.js"),
            "underscore" => array("https://cdnjs.cloudflare.com/ajax/libs/lodash.js/4.17.4/lodash.min.js"),
            //"underscore" => array('//cdnjs.cloudflare.com/ajax/libs/underscore.js/1.6.0/underscore-min.js'),
            "axios" => array("https://cdnjs.cloudflare.com/ajax/libs/axios/0.16.2/axios.min.js"),
            "ckeditor" => array("//cdn.ckeditor.com/4.6.0/standard/ckeditor.js"),

            "toastr" => array("//cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.3/toastr.min.js", array("jquery")),
        ),
        "local" => array(
            "util.appendStyle" => array('util/appendStyle.js'),
            "util.appendCSS" => array('util/appendCSS.js'),
            "util.DOMChangeListener" => array('util/DOMChangeListener.js', array("jquery")),
            "util.FormObserver" => array('util/FormObserver.js', array("jquery", "util.DOMChangeListener", "util.FormValidator")),
            "util.FormValidator" => array('util/FormValidator.js', array("jquery")),
            // config
            "config.toastr" => array('config/toastr.js', array("toastr", "util.appendCSS")),
            // rui
            "rui.show-errors" => array('rui.show-errors/rui.show-errors.js', array("jquery")),
            "rui.mi" => array('mi-3.0/jquery.mi.js', array("jquery", "InputPluginRegistry")),
            "rui.sort" => array('rui.sort/sort.js', array("jquery")),
            "rui.zip" => array('ZipCoder/ZipCoder.js', array("jquery")),
            // rui.viframe
            "rui.viframe" => array('jquery.viframe/jquery.viframe.js', array("jquery", "InputPluginRegistry")),
            "rui.vifupload" => array('jquery.viframe/jquery.vifupload.js', array("rui.viframe")),
            "rui.vifhistory" => array('jquery.viframe/jquery.vifhistory.js', array("rui.viframe")),
            // input_plugin
            "InputPluginRegistry" => array('input_plugin/InputPluginRegistry.js', array("jquery")),
            "input_plugin.zero_option" => array('input_plugin/zero_option.js', array("jquery", "InputPluginRegistry")),
            "input_plugin.split_text" => array('input_plugin/split_text.js', array("jquery", "InputPluginRegistry")),
            "input_plugin.date_select" => array('input_plugin/date_select.js', array("jquery", "InputPluginRegistry")),
            "input_plugin.radio_set_first" => array('input_plugin/radio_set_first.js', array("jquery", "InputPluginRegistry")),
            "input_plugin.ckeditor" => array('input_plugin/ckeditor.js', array("jquery", "ckeditor", "InputPluginRegistry")),
            "input_plugin.sync_select" => array('input_plugin/sync_select.js', array("jquery", "InputPluginRegistry")),
        ),
    );
