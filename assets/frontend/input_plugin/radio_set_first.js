InputPluginRegistry.registerPlugin("radio_set_first", function ($elm, params) {
    var name = $elm.attr("name");
    var $inputs = $elm.closest("form").find("input[name='"+name+"']");
    if ($inputs.filter(":checked").length == 0) {
        $inputs.eq(0).attr("checked", "checked");
    }
});
