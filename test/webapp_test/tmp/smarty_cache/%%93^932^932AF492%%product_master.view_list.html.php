<?php /* Smarty version 2.6.22, created on 2011-03-25 18:04:21
         compiled from /var/www/vhosts/dev.sharingseed.info/jnavi/webapp.git/html/product_master/product_master.view_list.html */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('block', 'form', '/var/www/vhosts/dev.sharingseed.info/jnavi/webapp.git/html/product_master/product_master.view_list.html', 6, false),array('block', 'a', '/var/www/vhosts/dev.sharingseed.info/jnavi/webapp.git/html/product_master/product_master.view_list.html', 19, false),array('function', 'input', '/var/www/vhosts/dev.sharingseed.info/jnavi/webapp.git/html/product_master/product_master.view_list.html', 10, false),array('modifier', 'select', '/var/www/vhosts/dev.sharingseed.info/jnavi/webapp.git/html/product_master/product_master.view_list.html', 51, false),)), $this); ?>
<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "path:/element/default_header.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?> 

<h1 class="page_label">製品管理：一覧</h1>

<!-- search_module -->
<?php $this->_tag_stack[] = array('form', array('_page' => ".",'_reset' => '1')); $_block_repeat=true;smarty_block_form($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>
<table class="detail">
<tr>
	<td class="label"><nobr>製品名</nobr></td>
	<td class="cell"><?php echo smarty_function_input(array('name' => "c[Product.name]",'type' => 'text'), $this);?>
</td>
</tr>
<tr>
	<td class="label"><nobr>値段</nobr></td>
	<td class="cell"><?php echo smarty_function_input(array('name' => "c[Product.price]",'type' => 'select','options' => 'product_price','zerooption' => "--全て--"), $this);?>
</td>
</tr>
<tr>
	<td class="controll_cell" colspan="2">
		<?php echo smarty_function_input(array('type' => 'submit','value' => "検索",'class' => 'button'), $this);?>

		<?php $this->_tag_stack[] = array('a', array('_page' => ".",'_reset' => '1','class' => 'button')); $_block_repeat=true;smarty_block_a($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>リセット<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_a($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
	</td>
</tr>
</table>
<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_form($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
<!-- /search_module -->

<!-- controll_module -->
<div class="controll">
	<?php $this->_tag_stack[] = array('a', array('_page' => ".entry_form",'class' => 'button')); $_block_repeat=true;smarty_block_a($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>新規登録<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_a($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
</div>
<!-- /controll_module -->

<!-- list_module -->
<table class="list">
<tr>
	<td class="label">
		<nobr><?php $this->_tag_stack[] = array('a', array('_page' => ".",'_query' => "c[sort]=Product.name",'class' => 'sort')); $_block_repeat=true;smarty_block_a($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>製品名<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_a($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?></nobr>
	</td>
	<td class="label">
		<nobr><?php $this->_tag_stack[] = array('a', array('_page' => ".",'_query' => "c[sort]=Product.price",'class' => 'sort')); $_block_repeat=true;smarty_block_a($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>値段<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_a($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?></nobr>
	</td>
	<td class="label">
		&nbsp;
	</td>
</tr>
<?php $_from = $this->_tpl_vars['ts']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['t']):
?>
	<tr>
		<td class="cell">
			<?php echo $this->_tpl_vars['t']['Product']['name']; ?>

		</td>
		<td class="cell">
			<?php echo ((is_array($_tmp=$this->_tpl_vars['t']['Product']['price'])) ? $this->_run_mod_handler('select', true, $_tmp, 'product_price') : smarty_modifier_select($_tmp, 'product_price')); ?>

		</td>
		<td class="cell menu">
			<nobr>
						<?php $this->_tag_stack[] = array('a', array('_page' => ".entry_form",'_id' => $this->_tpl_vars['t']['Product']['id'],'class' => 'button')); $_block_repeat=true;smarty_block_a($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>編集<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_a($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
			<?php $this->_tag_stack[] = array('a', array('_page' => ".delete_confirm",'_id' => $this->_tpl_vars['t']['Product']['id'],'class' => 'button')); $_block_repeat=true;smarty_block_a($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>削除<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_a($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
			</nobr>
		</td>
	</tr>
<?php endforeach; endif; unset($_from); ?>
</table>
<!-- /list_module -->

<!-- pager_module -->
<div class="pager">
<span class="inactive"><?php echo $this->_tpl_vars['p']['current']; ?>
 / <?php echo $this->_tpl_vars['p']['lastpage']; ?>
ページを表示中&nbsp;</span>
<?php if ($this->_tpl_vars['p']['prev'] !== null): ?>
	<?php $this->_tag_stack[] = array('a', array('_page' => ".",'_query' => "c[offset]=".($this->_tpl_vars['p']['prev']))); $_block_repeat=true;smarty_block_a($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>前へ<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_a($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
<?php else: ?>
	<span class="inactive">前へ</span>
<?php endif; ?>
<?php $_from = $this->_tpl_vars['p']['pages']; if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['index'] => $this->_tpl_vars['page_offset']):
?>
	<?php if ($this->_tpl_vars['index'] == $this->_tpl_vars['p']['current']): ?> 
		<?php $this->_tag_stack[] = array('a', array('_page' => ".",'_query' => "c[offset]=".($this->_tpl_vars['page_offset']),'class' => 'current')); $_block_repeat=true;smarty_block_a($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?><?php echo $this->_tpl_vars['index']; ?>
<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_a($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
	<?php else: ?>
		<?php $this->_tag_stack[] = array('a', array('_page' => ".",'_query' => "c[offset]=".($this->_tpl_vars['page_offset']))); $_block_repeat=true;smarty_block_a($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?><?php echo $this->_tpl_vars['index']; ?>
<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_a($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
	<?php endif; ?>
<?php endforeach; endif; unset($_from); ?>
<?php if ($this->_tpl_vars['p']['next'] !== null): ?>
	<?php $this->_tag_stack[] = array('a', array('_page' => ".",'_query' => "c[offset]=".($this->_tpl_vars['p']['next']))); $_block_repeat=true;smarty_block_a($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>次へ<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_a($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
<?php else: ?>
	<span class="inactive">次へ</span>
<?php endif; ?>
</div>
<!-- /pager_module -->

<?php $_smarty_tpl_vars = $this->_tpl_vars;
$this->_smarty_include(array('smarty_include_tpl_file' => "path:/element/default_footer.html", 'smarty_include_vars' => array()));
$this->_tpl_vars = $_smarty_tpl_vars;
unset($_smarty_tpl_vars);
 ?> 