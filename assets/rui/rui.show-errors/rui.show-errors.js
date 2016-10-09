
// エラーメッセージを表示する
ruiShowErrors =function (o)
{
    var errors =o.errors;
    var $form =o.$form;

    for (i in errors) {
        var $input =$form.find('[name="'+errors[i].name+'"]');
        var $fieldBlock =$input.parents(".inputBlock").eq(0);
        var msg = "※"+errors[i].message;

        // 既存メッセージがあれば重複させない
        var msg_exists = false;
        $fieldBlock.find(".errmsg").each(function(){
            if ($(this).text()==msg) {
                msg_exists = true;
            }
        });
        if (msg_exists) {
            continue;
        }

        var $msgbox =$('<p>')
                .addClass('errmsg')
                .text(msg);
        $fieldBlock.addClass("inputError");
        $fieldBlock.append($msgbox);
    }
};