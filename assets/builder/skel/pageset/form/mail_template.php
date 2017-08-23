<#?php $mail->load("common.php"); ?>
<#?php $mail->to($mail->vars["admin_to"]); ?>
<#?php $mail->subject("<?=$mail->getController()->getlabel()?> 完了通知メール"); ?>
下記の通り入力を受け付けました

<?php foreach ($mail->getController()->getInputCols() as $col): ?>
<?=$col->getLabel()?> : <?=$col->getMailSource()?><?="\n"?>
<?php endforeach; ?>
