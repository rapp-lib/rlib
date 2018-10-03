InputPluginRegistry.registerPlugin("sync_select", function ($elm, params) {
    var $parents = [];
    if (typeof params["parent_elm"] !== "object") params["parent_elm"] = [ params["parent_elm"] ];
    for (var i in params["parent_elm"]) {
        $parents[i] = $elm.closest("form").find(params["parent_elm"][i]);
    }
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
    var getParentValue = function(){
        if ($parents.length==1) return $parents[0].val();
        else return $parents.map(function($elm){ return $elm.val(); });
    };
    var encodeValue = function(value){
        if ( ! value instanceof Array) return value;
        else return JSON.stringify(value);
    };
    var loadValues = function(set_id, after){
        if ( ! dataset[encodeValue(set_id)] && ajax_url) {
            $.ajax({
                url: ajax_url,
                data: {key:set_id},
                dataType: 'json',
                success: function(data){
                    dataset[encodeValue(set_id)] = data;
                    if (after) after(dataset[encodeValue(set_id)] || {});
                }
            });
        } else {
            if (after) after(dataset[encodeValue(set_id)] || {});
        }
    };
    var syncOptions = function(e){
        loadValues(getParentValue(), function(values){
            var preset_value = $elm.val();
            $elm.children('[value]').remove();
            $elm.trigger("replace");
            for (var i in values) {
                $elm.append($("<option>").attr("value",values[i][0]).text(values[i][1]));
            }
            if ($elm.children('[value="'+preset_value+'"]').length > 0) $elm.val(preset_value);
            else $elm.val("");
            $elm.trigger("change");
        });
    };
    for (var i in $parents) $parents[i].on("change", syncOptions);
    syncOptions();
});
