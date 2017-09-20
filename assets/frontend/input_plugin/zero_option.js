InputPluginRegistry.registerPlugin("zero_option", function ($elm, params) {
    $elm.find('option:not([value])').attr("value","");
    var $zero_option = $elm.find('option[value=""]');
    if (params.label) $zero_option.text(params.label);
    if (params.remove) $zero_option.remove();
});
