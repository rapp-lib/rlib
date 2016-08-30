
// エラーメッセージを表示する
window.showErrors =function (o) 
{
	var errors =o.errors;
	var $form =o.$form;

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

