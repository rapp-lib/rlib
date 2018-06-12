(function($) {
    var csscls = PhpDebugBar.utils.makecsscls('phpdebugbar-widgets-');
    var HtmlVariableListWidget = PhpDebugBar.Widgets.HtmlVariableListWidget = PhpDebugBar.Widgets.KVListWidget.extend({
        className: csscls('kvlist htmlvarlist'),
        itemRenderer: function(dt, dd, key, value) {
            $('<span />').attr('title', key).text(key).appendTo(dt);
            dd.html(value);
        }
    });
    var FreeHtmlWidget = PhpDebugBar.Widgets.FreeHtmlWidget = PhpDebugBar.Widget.extend({
        tagName: 'div',
        className: csscls('free-html'),
        render: function() {
            this.$el.attr({
                border: "0",
                width: "100%",
                height: "100%"
            });
            this.bindAttr('data', function(html) { this.$el.html(html); });
        }

    });
})(PhpDebugBar.$);
