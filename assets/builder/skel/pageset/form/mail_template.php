<#?php $mail->load("inc/<?=$mail->getAttr("type")?>_header.php"); ?>
<?php if ($mail->getAttr("type")==="admin"): ?>
<#?php $mail->to($mail->vars["admin_to"]); ?>
<?php else: ?>
<#?php $mail->to($mail->vars["t"]["<?=$mail->getAttr("mail_col_name")?>"]); ?>
<?php endif; ?>
<#?php $mail->subject("<?=$mail->getController()->getlabel()?> 完了通知メール"); ?>
下記の通り入力を受け付けました

<?php foreach ($mail->getController()->getMailCols() as $col): ?>
<?=$col->getLabel()?> : <?=$col->getMailSource()?><?="\n"?>
<?php endforeach; ?>

<#?php $mail->load("inc/<?=$mail->getAttr("type")?>_footer.php"); ?>
