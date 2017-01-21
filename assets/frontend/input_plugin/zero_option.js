InputPluginRegistry.registerPlugin("zero_option", function ($elm, params) {
    $elm.find('option:not([value])').attr("value","");
    $elm.find('option[value=""]').text(params.label);
});
