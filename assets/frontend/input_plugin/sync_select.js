InputPluginRegistry.registerPlugin("sync_select", function ($elm, params) {
    var $parent = $elm.closest("form").find(params["parent_elm"]);
    var ajax_url = params["ajax_url"];
    var ajax_key_param = params["ajax_key_param"] || "key";
    var values_set = params["values_set"] || {};
    var loadValues = function(set_id){
        if ( ! values_set[set_id] && ajax_url) {
            $.ajax({
                url: ajax_url,
                async: false,
                data: {"key":set_id},
                dataType: 'json',
                success: function(data){ values_set[set_id] = data; }
            });
        }
        return values_set[set_id] || {};
    };
    var syncOptions = function(){
        $elm.children('[value]').remove();
        var values = loadValues($parent.val());
        for (var key in values) $elm.append($("<option>").attr("value",key).text(values[key]));
        $elm.trigger("change");
    };
    $parent.on("change", syncOptions);
    var preset_value = $elm.val();
    syncOptions();
    $elm.val(preset_value);
});
