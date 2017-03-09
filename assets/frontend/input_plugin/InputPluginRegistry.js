window.InputPluginRegistry = {
    plugins : {},
    elements : {},
    registerPlugin : function (pluginId, callback) {
        InputPluginRegistry.plugins[pluginId] = callback;
    },
    registerElement : function (elementId, paramSet) {
        InputPluginRegistry.elements[elementId] = paramSet;
    },
    applyAll : function () {
        var $elms = $('*[data-plugin-elmid][data-plugin-applied!=yes]');
        $elms.each(function(){
            var $elm = $(this);
            var elementId = $elm.attr("data-plugin-elmid");
            var paramSet = InputPluginRegistry.elements[elementId];
            for (var pluginId in paramSet) {
                InputPluginRegistry.plugins[pluginId]($elm, paramSet[pluginId]);
            }
            $elm.attr("data-plugin-applied","yes");
        });
    }
};
$(function(){
    InputPluginRegistry.applyAll();
});