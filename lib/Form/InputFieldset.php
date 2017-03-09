<?php
namespace R\Lib\Form;

/**
 *
 */
class InputFieldset
{
    private $form;
    private $attrs;
    /**
     *
     */
    public function __construct ($form, $values, $attrs)
    {
        $this->form = $form;
        $this->attrs = $attrs;
    }
}
