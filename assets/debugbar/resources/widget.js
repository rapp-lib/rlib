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
    var is_array = function ($v){
        return $.isArray($v) || $v instanceof Object;
    };
    var htmlspecialchars = function (ch){
        if (typeof ch !="string") return ch;
        ch = ch.replace(/&/g,"&amp;") ;
        ch = ch.replace(/"/g,"&quot;") ;
        ch = ch.replace(/'/g,"&#039;") ;
        ch = ch.replace(/</g,"&lt;") ;
        ch = ch.replace(/>/g,"&gt;") ;
        return ch ;
    };
    var indentValues = function ($values, $level) {
        $level = $level || 1;
        var $text = "";
        var $br_code = "<br/>";
        var $tab_code = "&nbsp;&nbsp;&nbsp;&nbsp;";
        if ( ! $values) return "";
        if ($values["__type"]) {
            $text += $values["__type"];
            delete $values["__type"];
        }
        for (var $k in $values) {
            var $v = $values[$k];
            $text += $br_code+$tab_code.repeat($level)+$k+" : ";
            if (is_array($v) && $v) {
                $text += indentValues($v, $level+1);
            } else if (is_array($v)) {
                $text += "array[0]";
            } else {
                $text += htmlspecialchars($v);
            }
        }
        return $text;
    };
    var ReportListWidget = PhpDebugBar.Widgets.ReportListWidget = PhpDebugBar.Widget.extend({
        tagName: 'div',
        className: csscls('report-liast'),
        render: function() {
            this.$el.attr({
                border: "0",
                width: "100%",
                height: "100%"
            });
            this.bindAttr('data', function(data_list) {
                var html = "";
                for (var i in data_list) {
                    var data = data_list[i];
                    html += '<div id="'+data.id+'" '
                        +'onclick="var e=document.getElementById(\''+data.id+'\');'
                        +'e.style.height =\'auto\'; e.style.cursor =\'auto\';" '
                        +'ondblclick="var e=document.getElementById(\''+data.id+'_detail\');'
                        +'e.style.display =\'block\'; e.style.cursor =\'auto\';" '
                        +'style="font-size:14px;text-align:left;overflow:hidden;'
                        +'margin:1px;padding:2px;font-family: monospace;'
                        +'border:#888888 1px solid;background-color:'
                        +'#000000;cursor:hand;line-height:20px;height:40px;color:'+data.color+'">'+data.pos
                        +'<div style="margin:0 0 0 10px">'+data.message+indentValues(data.params)+'</div>'
                    ;
                    html += '<div style="margin:0 0 0 10px;display:none;" id="'+data.id+'_detail">';
                    if (data.bts) {
                        html += 'Backtraces '+indentValues(data.bts);
                    }
                    html += '</div>';
                    html += '</div>';
                }
                this.$el.html(html);
            });
        }
    });
})(PhpDebugBar.$);
