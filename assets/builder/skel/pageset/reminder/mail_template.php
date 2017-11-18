<#?php $mail->load("inc/<?=$mail->getAttr("type")?>_header.php"); ?>
<#?php $mail->to($mail->vars["t"]["<?=$mail->getAttr("mail_col_name")?>"]); ?>
<#?php $mail->subject("<?=$mail->getController()->getlabel()?> URL通知メール"); ?>
以下のURLより手続きを完了してください。

<#?=$mail->vars["uri"]?><#?="\n"?>

<#?php if ($mail->vars["ttl"]): ?>
※有効期限 <#?=date("Y/m/d H:i", time() + $mail->vars["ttl"])?> まで<#?="\n"?>
<#?php endif; ?>

<#?php $mail->load("inc/<?=$mail->getAttr("type")?>_footer.php"); ?>
