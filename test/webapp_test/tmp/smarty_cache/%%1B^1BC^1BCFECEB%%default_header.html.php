<?php /* Smarty version 2.6.22, created on 2011-03-29 18:26:32
         compiled from path:/element/default_header.html */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('modifier', 'path_to_url', 'path:/element/default_header.html', 5, false),array('modifier', 'registry', 'path:/element/default_header.html', 17, false),array('block', 'a', 'path:/element/default_header.html', 11, false),)), $this); ?>
<html>
<head>
	<title></title>
	<meta http-equiv="Content-Type" content="text/html; charset=Shift_JIS">
	<link rel="stylesheet" href="<?php echo ((is_array($_tmp='/css/default_style.css')) ? $this->_run_mod_handler('path_to_url', true, $_tmp) : path_to_url($_tmp)); ?>
"/>
	<script language="javascript" src="<?php echo ((is_array($_tmp='/js/jquery-1.5.1.min.js')) ? $this->_run_mod_handler('path_to_url', true, $_tmp) : path_to_url($_tmp)); ?>
"></script>
</head>
<body>

<div class="main_content">
	<?php $this->_tag_stack[] = array('a', array('_page' => "index.index",'class' => 'button')); $_block_repeat=true;smarty_block_a($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>トップ<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_a($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
	<?php $_from = ((is_array($_tmp="Schema.controller")) ? $this->_run_mod_handler('registry', true, $_tmp) : registry($_tmp)); if (!is_array($_from) && !is_object($_from)) { settype($_from, 'array'); }if (count($_from)):
    foreach ($_from as $this->_tpl_vars['controller_name'] => $this->_tpl_vars['controller']):
?>
		<?php $this->_tag_stack[] = array('a', array('_page' => ($this->_tpl_vars['controller_name']).".index",'class' => 'button')); $_block_repeat=true;smarty_block_a($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?><?php echo $this->_tpl_vars['controller']['label']; ?>
<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_a($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
	<?php endforeach; endif; unset($_from); ?>
	
</div>
<div class="main_content">