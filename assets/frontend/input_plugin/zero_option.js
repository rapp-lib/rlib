window.input_plugin_zero_option = function (id, params) {
    $(function(){
        var $elm = $("#"+id);
        $elm.find('option:not([value])').attr("value","");
        $elm.find('option[value=""]').text(params.label);
    });
};