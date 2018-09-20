InputPluginRegistry.registerPlugin("zero_option", function ($elm, params) {
    var setZeroOption = function() {
        $elm.find('option:not([value])').attr("value","");
        var $zero_option = $elm.find('option[value=""]');
        if ( ! $zero_option.length) {
            $zero_option = $('<option value=""></option>');
            $elm.prepend($zero_option);
        }
        if (params.label) $zero_option.text(params.label);
        if (params.remove) $zero_option.remove();
    };
    $elm.on("replace", setZeroOption);
    setZeroOption();
});
