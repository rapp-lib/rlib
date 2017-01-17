window.InputPluginRegistry = {
    plugins : {},
    elements : {},
    registerPlugin : function (pluginId, callback) {
        InputPluginRegistry.plugins[pluginId] = callback;
    },
    registerElement : function (elementId, paramSet) {
        InputPluginRegistry.elements[elementId] = paramSet;
        InputPluginRegistry.apply(elementId);
    },
    apply : function (elementId, $block) {
        $(function(){
            $block = $block || $(document);
            var $elm = $block.find('[data-plugin-elmid='+elementId+']');
            var paramSet = InputPluginRegistry.elements[elementId];
            for (var pluginId in paramSet) {
                InputPluginRegistry.plugins[pluginId]($elm, paramSet[pluginId]);
            }
        });
    }
};