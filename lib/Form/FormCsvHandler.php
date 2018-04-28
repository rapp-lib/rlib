<?php
namespace R\Lib\Form;

class FormCsvHandler extends \R\Lib\Util\CSVHandler
{
    protected $form_def;

    public function __construct ($csv_file, $mode, $form_def)
    {
        $this->form_def = $form_def;
        $csv_setting = (array)$form_def["csv_setting"];
        if ( ! $csv_setting["rows"]) {
            foreach ((array)$form_def["fields"] as $field_name => $field_def) {
                if ($field_def["csv_row"]===false) continue;
                if (in_array($field_def["type"], array("fields","fieldset"))) continue;
                $label = $field_def["label"] ?: $field_name;
                $csv_setting["rows"][$field_name] = __($label);
            }
        }
        parent::__construct($csv_file, $mode, $csv_setting);
    }

    public function readForm ($options=array())
    {
        $csv_data = parent::readLine($options);
        if (is_null($csv_data)) return null;
        $form = app()->form->create($this->form_def);
        $form->setValues($csv_data);
        return $form;
    }
    public function writeForm ($form, $options=array())
    {
        $csv_data = (array)$form;
        return parent::writeLine($csv_data, $options);
    }

    public function readRecord ($options=array())
    {
        $form = $this->readForm($options);
        return $form ? $form->getRecord() : null;
    }
    public function writeRecord ($record, $options=array())
    {
        $form = app()->form->create($this->form_def);
        $form->setRecord($record);
        return $this->writeForm($form, $options);
    }
}
