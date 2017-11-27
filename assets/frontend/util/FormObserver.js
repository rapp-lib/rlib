window.FormObserver = function ($form, state, o) {
    var self = this;
    self.$form = $form;
    self.state = state || {};
    self.o = o || {};
    self.field_names = self.state.field_names || [];
    self.rules = self.state.rules || [];
    self.errors = self.state.errors || [];
    self.refresh_callback = self.o.refresh_callback || function(self, $input, rules, errors){};

    self.getValidator = function(){
        if ( ! self.validator) self.validator = new FormValidator(self);
        return self.validator;
    };
    self.className = function(string){
        return string.replace(/[^\w\d-_]/g, '-');
    };

// -- field_nameから情報を取得

    self.getInputSelector = function(field_name){
        var parts = field_name.split(".");
        var selector = null;
        if (parts.length==1) selector = '[name="'+parts[0]+'"]';
        else if (parts.length==2) selector = '[name="'+parts[0]+"["+parts[1]+"]"+'"]';
        else if (parts.length==3) {
            if (parts[1]=="*") selector = '[name^="'+parts[0]+"["+'"][name$="'+"]["+parts[2]+"]"+'"]';
            else selector = '[name="'+parts[0]+"["+parts[1]+"]["+parts[2]+"]"+'"]';
        }
        return selector;
    };
    self.getInputElement = function(field_name){
        var selector = self.getInputSelector(field_name);
        return $(selector) || $("__dummy");
    };
    self.getInputValue = function(field_name){
        return self.getInputElement(field_name).val();
    };
    self.fieldNameIsMatchRule = function(field_name_ptn, field_name){
        // Ruleの判定の場合は、入力側のfieldset_indexを無視する
        field_name = field_name.replace(/^([^\.]+)\.([^\.]+)\.([^\.]+)$/,'$1.*.$3');
        return field_name == field_name_ptn;
    };
    self.fieldNameIsMatchError = function(field_name_ptn, field_name, fieldset_index){
        // Errorの判定の場合は、パターン側にfieldset_indexを埋め込む
        field_name_ptn = field_name_ptn.replace(/^([^\.]+)\.([^\.]+)\.([^\.]+)$/,'$1.'+fieldset_index+'.$3');
        return field_name == field_name_ptn;
    };

// -- input要素から情報を取得する

    self.getFieldNameByElement = function($input){
        return $input.attr('name').replace(/\[/g,'.').replace(/\]/g,'');
    };
    self.getRulesByElement = function($input){
        var field_name = self.getFieldNameByElement($input);
        var rules = [];
        for (var i in self.rules) {
            if (self.fieldNameIsMatchRule(self.rules[i].field_name, field_name)) rules.push(self.rules[i]);
        }
        return rules;
    };
    self.getErrorsByElement = function($input){
        var field_name = self.getFieldNameByElement($input);
        var errors = [];
        for (var i in self.errors) {
            if (self.fieldNameIsMatchError(self.errors[i].field_name, field_name, self.errors[i].fieldset_index)) errors.push(self.errors[i]);
        }
        return errors;
    };

// -- 更新制御

    // 表示の更新
    self.refresh = function(){
        for (var i in self.field_names) {
            var $inputs = self.getInputElement(self.field_names[i]);
            $inputs.each(function(){
                var $input = $(this);
                var errors = self.getErrorsByElement($input);
                var rules = self.getRulesByElement($input);
                self.refresh_callback(self, $input, rules, errors);
            });
        }
    };

    // 更新処理の初期登録
    self.init = function(){
        // 初期更新
        self.refresh();
        // 入力時の更新
        for (var i in self.field_names) {
            var selector = self.getInputSelector(self.field_names[i]);
            self.$form.on('change blur', selector, function(){
                var $input = $(this);
                $input.addClass("changed");
                self.refresh();
            });
        }
        // DOM追加時の更新
        new DOMChangeListener(self.$form, "insert", function($form, nodes){
            self.refresh();
        });
    };
    self.init();
};
