InputPluginRegistry.registerPlugin("sync_select", function ($elm, params) {
    var $parent = $elm.closest("form").find(params["parent_elm"]);
    var ajax_url = params["ajax_url"];
    var ajax_key_param = params["ajax_key_param"] || "key";
    var dataset = params["dataset"] || {}; // {PARENT_ID : [[K:V], ...], ...}
    // @deprecated
    if (params["values_set"]) {
        for (var x in params["values_set"]) {
            dataset[x] = [];
            for (var y in params["values_set"][x]) {
                dataset[x].push(y, params["values_set"][x][y]);
            }
        }
    }
    var loadValues = function(set_id, after){
        if ( ! dataset[set_id] && ajax_url) {
            $.ajax({
                url: ajax_url,
                data: {key:set_id},
                dataType: 'json',
                success: function(data){
                    dataset[set_id] = data;
                    if (after) after(dataset[set_id] || {});
                }
            });
        } else {
            if (after) after(dataset[set_id] || {});
        }
    };
    var syncOptions = function(e){
        loadValues($parent.val(), function(values){
            $elm.children('[value]').remove();
            for (var i in values) {
                $elm.append($("<option>").attr("value",values[i][0]).text(values[i][1]));
            }
            $elm.trigger("change");
            if (e.afterSyncOptions) e.afterSyncOptions();
        });
    };
    $parent.on("change", syncOptions);
    var preset_value = $elm.val();
    syncOptions({afterSyncOptions:function(){
        $elm.val(preset_value);
    }});
});
