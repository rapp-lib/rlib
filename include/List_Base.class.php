<?php

/**
 *
 */
class List_Base {

    protected $name;
    protected $config;
    protected $controller;

    //-------------------------------------
    // コンストラクタ
    public function get_instance ($name, $controller=null) {

        $cache =& ref_globals("list_option_class");

        if ( ! $cache[$name]) {

            $class_name =str_camelize($name)."List";
            $config =registry("List.".$name);

            $cache[$name] =class_exists($class_name)
                    ? new $class_name
                    : new self;

            $cache[$name]->init($name,$config,$controller);
        }

        return $cache[$name];
    }

    //-------------------------------------
    // 初期化
    public function init ($name, $config, $controller=null) {

        $this->name =$name;
        $this->config =$config;
        $this->controller =$controller;
    }

    //-------------------------------------
    // オプション取得
    public function options ($params=array()) {

        if ( ! is_array($this->config["options"])) {

            report_error("List-options is not defined.",array(
                "name" =>$this->name,
                "config" =>$this->config,
            ));
        }

        return (array)$this->config["options"];
    }

    //-------------------------------------
    // オプション選択
    public function select ($key=null, $params=array()) {

        $options =$this->options($params);

        // Serializeされた文字列であれば配列に変換する
        if (is_string($key) && $key_unserialized =unserialize($key)) {

            $key =$key_unserialized;
        }

        // KEY=>KEY形式の配列での複数選択
        if (is_array($key)) {

            $selected =array();

            foreach ($key as $k => $v) {

                if ($v) {

                    $selected[$k] =$options[$k];
                }
            }

            return $selected;

        // 単一選択
        } else {

            return $options[$key];
        }
    }

    //-------------------------------------
    // オプションからキーの選択
    public function select_reverse ($value=null, $params=array()) {

        $options =$this->options($params);
        $keys =array_flip($options);

        // Serializeされた文字列であれば配列に変換する
        if (is_string($value) && $unserialized =unserialize($value)) {

            $value =$unserialized;
        }

        // 単一選択
        if (is_string($value)) {

            return $keys[$value];
        }

        // KEY=>0/1形式の配列での複数選択
        if (is_array($value)) {

            $selected =array();

            foreach ($value as $k => $v) {

                if ($v) {

                    $selected[$k] =$keys[$k];
                }
            }

            return $selected;
        }

        return null;
    }

    //-------------------------------------
    // 親要素との対応取得
    public function parents ($param=array()) {

        if ( ! is_array($this->config["parents"])) {

            report_error("List-parents is not defined.",array(
                "name" =>$this->name,
                "config" =>$this->config,
            ));
        }

        return (array)$this->config["options"];
    }
}
