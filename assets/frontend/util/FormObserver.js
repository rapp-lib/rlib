
window.FormObserver = function ($form, state, o) {
    var self = this;
    self.$form = $form;
    self.state = state || {};
    self.o = o || {};
    self.rules = self.state.rules || [];
    self.errors = self.state.errors || [];
    // フォームにエラー定義を表示する
    self.applyRules = function(){
        // ルールを表示する
        for (var i in self.rules) {
            var item = self.rules[i];
            var $input = self.findInputByFieldName(item.field_name);
            if (self.o.apply_rule_callback) self.o.apply_rule_callback($input, item);
        }
    };
    // フォームにエラー定義を表示する
    self.applyErrors = function(){
        // エラーを表示する
        for (var i in self.errors) {
            var item = self.errors[i];
            var $input = self.findInputByFieldName(item.field_name);
            if (self.o.apply_error_callback) self.o.apply_error_callback($input, item);
        }
    };
    // field_nameに対応するinput要素を取得
    self.findInputByFieldName = function(field_name){
        var parts = field_name.split(".");
        var selector = "_dummy_";
        if (parts.length==1) selector = '[name="'+parts[0]+'"]';
        else if (parts.length==2) selector = '[name="'+parts[0]+"["+parts[1]+"]"+'"]';
        else if (parts.length==3) {
            if (parts[1]=="*") selector = '[name^="'+parts[0]+"["+'"][name$="'+"]["+parts[2]+"]"+'"]';
            else selector = '[name="'+parts[0]+"["+parts[1]+"]["+parts[2]+"]"+'"]';
        }
        return self.$form.find(selector);
    };
    // 初期化
    self.init = function(){
        self.applyErrors();
        self.applyRules();
        new DOMChangeListener(self.$form, "insert", function($form, nodes){
            self.applyRules();
        });
    };
    self.init();
};
