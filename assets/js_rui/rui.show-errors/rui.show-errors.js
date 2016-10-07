
// エラーメッセージを表示する
Rui.showErrors =function (o)
{
    var $form =o.$form;
    var errors =o.errors;

    for (i in errors) {
        var $input =$form.find('[name="'+errors[i].name+'"]');

        var $fieldBlock =$input.parents(".inputBlock").eq(0);
        var $msgbox =$('<p>')
                .addClass('errmsg')
                .text("※"+errors[i].message);
        $fieldBlock.addClass("inputError");
        $fieldBlock.append($msgbox);
    }
};

