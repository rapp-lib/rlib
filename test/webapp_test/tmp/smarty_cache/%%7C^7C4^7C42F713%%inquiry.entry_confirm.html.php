<?php /* Smarty version 2.6.22, created on 2011-03-25 18:14:56
         compiled from /var/www/vhosts/dev.sharingseed.info/jnavi/webapp.git/html/inquiry/inquiry.entry_confirm.html */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('block', 'a', '/var/www/vhosts/dev.sharingseed.info/jnavi/webapp.git/html/inquiry/inquiry.entry_confirm.html', 21, false),)), $this); ?>
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "path:/element/default_header.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?>  

<h1 class="page_label">問い合わせ：入力確認</h1>

<!-- show_module -->
<table class="detail">
<tr>
	<td class="label"><nobr>メールアドレス</nobr></td>
	<td class="cell"><?php echo $this->_tpl_vars['t']['Inquiry']['mail']; ?>
</td>
</tr>
<tr>
	<td class="label"><nobr>件名</nobr></td>
	<td class="cell"><?php echo $this->_tpl_vars['t']['Inquiry']['subject']; ?>
</td>
</tr>
<tr>
	<td class="label"><nobr>お問い合わせ内容</nobr></td>
	<td class="cell"><?php echo $this->_tpl_vars['t']['Inquiry']['message']; ?>
</td>
</tr>
<tr>
	<td class="controll_cell" colspan="2">
		<?php $this->_tag_stack[] = array('a', array('_page' => ".entry_exec",'class' => 'button')); $_block_repeat=true;smarty_block_a($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>登録<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_a($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
		<?php $this->_tag_stack[] = array('a', array('_page' => ".entry_form",'class' => 'button')); $_block_repeat=true;smarty_block_a($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>戻る<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_a($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
	</td>
</tr>
</table>
<!-- /show_module -->

<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "path:/element/default_footer.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?> 