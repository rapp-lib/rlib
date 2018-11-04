<?php
namespace R\Lib\Table\Plugin;

abstract class BasePluginProvider implements PluginProvider
{
    protected $hook_points = array();
    protected $features = array();
    /**
     * @inheritdoc
     */
    public function registerPlugin($features)
    {
        $this->registerHookPoints($features);
        $this->retreiveFeatures($features);
        $this->registerFeatures($features);
    }
    /**
     * HookPointの登録
     */
    protected function registerHookPoints($features)
    {
        foreach ($this->hook_points as $hook_point=>$hook_point_def) {
            if (method_exists($this, $method_name="handler_".$hook_point)) {
                $hook_point_def["callback"] = array($this, $method_name);
            }
            if (method_exists($this, $method_name="filter_".$hook_point)) {
                $hook_point_def["filters"][] = array($this, $method_name);
            }
            $features->registerHookPoint($hook_point, $hook_point_def);
        }
    }
    /**
     * Featureの探索
     */
    protected function retreiveFeatures($features)
    {
        $ats = implode('|', array("after", "before"));
        $types = implode('|', array("select", "insert", "update", "delete", "read", "write"));
        $hooks = implode('|', array("chain", "result", "record", "form", "search"));
        $mods = implode('|', array("end"));
        foreach (get_class_methods($this) as $method_name) {
            $feature = array();
            // on_afterWrite_colDelFlg_attach
            if (preg_match('!^on_('.$ats.')?('.$types.')(?:_col([^_]+))?(_.+)?!i', $method_name, $_)) {
                list($name, $at, $type, $by_col_attr, ) = $_;
                $hook = ($at==="after") ? "after_exec" : "before_render";
                $feature["query_type"] = snake_case($type);
                if ($by_col_attr) $feature["by_col_attr"] = snake_case($by_col_attr);
            } elseif (preg_match('!^on_([^_]+)(_.+)?$!i', $method_name, $_)) {
                list($name, $hook, ) = $_;
            } elseif (preg_match('!^('.$hooks.')('.$mods.')?_(.+)$!i', $method_name, $_)) {
                list(, $hook, $mod, $name) = $_;
                if ($mod) $feature[snake_case($mod)] = true;
            } else {
                continue;
            }
            // Callback含めて登録されていないパラメータを補完
            $feature["callback"] = array($this, $method_name);
            $hook_point = snake_case($hook);
            $feature_name = $name;
            foreach ($feature as $k=>$v) {
                if ( ! isset($this->features[$hook_point][$feature_name][$k])) {
                    $this->features[$hook_point][$feature_name][$k] = $v;
                }
            }
        }
    }
    /**
     * Featureの登録
     */
    protected function registerFeatures($features)
    {
        foreach ($this->features as $hook_point=>$feature_defs) {
            foreach ($feature_defs as $feature_name=>$feature_def) {
                $features->registerFeature($hook_point, $feature_name, $feature_def);
            }
        }
    }
}
