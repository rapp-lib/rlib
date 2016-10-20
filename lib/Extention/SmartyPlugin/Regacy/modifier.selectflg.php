<?php

    function smarty_modifier_selectflg () {

        $args =func_get_args();
        $value =array_shift($args);
        $list_name =array_shift($args);
        $params =$args;

        if ($list_name) {

            $list_options =get_list($list_name);
            return $list_options->select($value,$params);

        } else {

            if ($value) {

                return registry('Label.text.flg_on')
                        ? registry('Label.text.flg_on')
                        : "ON";

            } else {

                return registry('Label.text.flg_off')
                        ? registry('Label.text.flg_off')
                        : "OFF";
            }
        }
    }
