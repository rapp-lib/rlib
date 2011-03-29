<?php /* Smarty version 2.6.22, created on 2011-03-29 18:26:32
         compiled from path:/element/default_footer.html */ ?>
<?php require_once(SMARTY_CORE_DIR . 'core.load_plugins.php');
smarty_core_load_plugins(array('plugins' => array(array('block', 'a', 'path:/element/default_footer.html', 5, false),)), $this); ?>


</div>
<div class="main_content" align="right">
	<?php $this->_tag_stack[] = array('a', array('href' => "#",'class' => 'button')); $_block_repeat=true;smarty_block_a($this->_tag_stack[count($this->_tag_stack)-1][1], null, $this, $_block_repeat);while ($_block_repeat) { ob_start(); ?>▲ページ上部へ<?php $_block_content = ob_get_contents(); ob_end_clean(); $_block_repeat=false;echo smarty_block_a($this->_tag_stack[count($this->_tag_stack)-1][1], $_block_content, $this, $_block_repeat); }  array_pop($this->_tag_stack); ?>
</div>

</body>
</html>