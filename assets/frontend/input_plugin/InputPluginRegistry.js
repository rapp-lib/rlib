window.InputPluginRegistry = {
    plugins : {},
    registerPlugin : function (pluginId, callback) {
        InputPluginRegistry.plugins[pluginId] = callback;
    },
    apply : function ($root) {
        $root = $root || $(document);
        $root.find('*[data-rui-plugins][data-rui-plugins-applied!=yes]').each(function(){
            var $elm = $(this);
            $elm.data("rui-plugins-applied","yes");
            var paramSet = $elm.data("rui-plugins");
            if (typeof paramSet != "object") {
                console.error("@rui-plugins is not valid JSON", paramSet);
            } else {
                for (var pluginId in paramSet) {
                    if ( ! InputPluginRegistry.plugins[pluginId]) {
                        console.error("@rui-plugins."+pluginId+" is not registered", paramSet);
                    } else {
                        InputPluginRegistry.plugins[pluginId]($elm, paramSet[pluginId]);
                    }
                }
            }
        })
    }
};
jQuery(function() { InputPluginRegistry.apply(); });
jQuery(document).on("update-dom-structure", function() { InputPluginRegistry.apply($(this)); });
