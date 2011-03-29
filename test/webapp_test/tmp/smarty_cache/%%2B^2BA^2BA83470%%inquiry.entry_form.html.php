<?php /* Smarty version 2.6.22, created on 2011-03-25 18:14:48
         compiled from /var/www/vhosts/dev.sharingseed.info/jnavi/webapp.git/html/inquiry/inquiry.entry_form.html */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('block', 'form', '/var/www/vhosts/dev.sharingseed.info/jnavi/webapp.git/html/inquiry/inquiry.entry_form.html', 16, false),array('function', 'input', '/var/www/vhosts/dev.sharingseed.info/jnavi/webapp.git/html/inquiry/inquiry.entry_form.html', 20, false),)), $this); ?>
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "path:/element/default_header.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?> 

<h1 class="page_label">問い合わせ：入力</h1>

<!-- errors_module -->
<?php if ($this->_tpl_vars['c']->errors()): ?>
	<div class="errors">
	<?php $_from = $this->_tpl_vars['c']->errors(); if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['error_message']):
?>
		<div class="errmsg"><?php echo $this->_tpl_vars['error_message']; ?>
</div>
	<?php endforeach; endif; unset($_from); ?>
	</div>
<?php endif; ?>
<!-- /errors_module -->

<!-- entry_module -->
<?php $this->_tag_stack[] = array('form', array('_page' => ".entry_confirm",'enctype' => "form/multipart")); $_block_repeat=true;smarty_block_form($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>
<table class="detail">
<tr>
	<td class="label"><nobr>メールアドレス</nobr></td>
	<td class="cell"><?php echo smarty_function_input(array('name' => "c[Inquiry.mail]",'type' => 'text'), $this);?>
</td>
</tr>
<tr>
	<td class="label"><nobr>件名</nobr></td>
	<td class="cell"><?php echo smarty_function_input(array('name' => "c[Inquiry.subject]",'type' => 'text'), $this);?>
</td>
</tr>
<tr>
	<td class="label"><nobr>お問い合わせ内容</nobr></td>
	<td class="cell"><?php echo smarty_function_input(array('name' => "c[Inquiry.message]",'type' => 'text'), $this);?>
</td>
</tr>
<tr>
	<td class="controll_cell" colspan="2">
		<?php echo smarty_function_input(array('type' => 'submit','value' => "確認",'class' => 'button'), $this);?>

	</td>
</tr>
</table>
<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_form($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
<!-- /entry_module -->

<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "path:/element/default_footer.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?> 