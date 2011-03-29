<?php /* Smarty version 2.6.22, created on 2011-03-25 11:38:08
         compiled from /var/www/vhosts/dev.sharingseed.info/jnavi/webapp.git/html/member_login/member_login.entry_form.html */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('block', 'form', '/var/www/vhosts/dev.sharingseed.info/jnavi/webapp.git/html/member_login/member_login.entry_form.html', 16, false),array('block', 'a', '/var/www/vhosts/dev.sharingseed.info/jnavi/webapp.git/html/member_login/member_login.entry_form.html', 28, false),array('function', 'input', '/var/www/vhosts/dev.sharingseed.info/jnavi/webapp.git/html/member_login/member_login.entry_form.html', 20, false),)), $this); ?>
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "path:/element/default_header.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?> 

<h1 class="page_label">会員ログイン</h1>

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
<?php $this->_tag_stack[] = array('form', array('_page' => ".entry_confirm")); $_block_repeat=true;smarty_block_form($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>
<table class="detail">
<tr>
	<td class="label"><nobr>ログインID</nobr></td>
	<td class="cell"><?php echo smarty_function_input(array('name' => "c[login_id]",'type' => 'text'), $this);?>
</td>
</tr>
<tr>
	<td class="label"><nobr>パスワード</nobr></td>
	<td class="cell"><?php echo smarty_function_input(array('name' => "c[login_pass]",'type' => 'password'), $this);?>
</td>
</tr>
<td class="controll_cell" colspan="2">
	<?php echo smarty_function_input(array('type' => 'submit','value' => "ログイン",'class' => 'button'), $this);?>

	<?php $this->_tag_stack[] = array('a', array('_path' => "/index.html",'class' => 'button')); $_block_repeat=true;smarty_block_a($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>戻る<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_a($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
</td>
</table>
<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_form($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
<!-- /entry_module -->

<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "path:/element/default_footer.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?> 