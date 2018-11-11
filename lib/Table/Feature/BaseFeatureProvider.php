<?php
namespace R\Lib\Table\Feature;

abstract class BaseFeatureProvider implements FeatureProvider
{
    protected $hook_points = array();
    protected $features = array();
    /**
     * @inheritdoc
     */
    public function register($features)
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
            if ( ! $hook_point_def["callback"]) {
                $hook_point_def["callback"] = array($this, "defaultHookPointCallback");
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
        $hooks = implode('|', array("chain", "result", "record", "form", "search", "alias"));
        $mods = implode('|', array("end"));
        foreach (get_class_methods($this) as $method_name) {
            $feature = array();
            // on_afterWrite_colDelFlg_attach
            if (preg_match('!^on_('.$ats.')?('.$types.')(?:_col([^_]+))?(?:_(\d+))?.*$!i', $method_name, $_)) {
                list($name, $at, $type, $by_col_attr, $priority) = $_;
                $hook = ($at==="after") ? "after_exec" : "before_render";
                $feature["query_type"] = snake_case($type);
                if ($by_col_attr) $feature["by_col_attr"] = snake_case($by_col_attr);
                if ($priority) $feature["priority"] = $priority;
            } elseif (preg_match('!^on_([^_]+)(?:_(\d+))?.*$!i', $method_name, $_)) {
                list($name, $hook, $priority) = $_;
                $hook = snake_case($hook);
                if ($priority) $feature["priority"] = $priority;
            } elseif (preg_match('!^('.$hooks.')('.$mods.')?_(.+)$!i', $method_name, $_)) {
                list(, $hook, $mod, $name) = $_;
                $hook = snake_case($hook);
                if ($mod) $feature[snake_case($mod)] = true;
            } else {
                continue;
            }
            // Callback含めて登録されていないパラメータを補完
            $feature["callback"] = array($this, $method_name);
            if (method_exists($this, "pre_".$method_name)) {
                $feature["pre_callback"] = array($this, "pre_".$method_name);
            }
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
    /**
     * 基本Feature呼び出し処理
     */
    public function defaultHookPointCallback($feature_defs, $args)
    {
        foreach ($feature_defs as $feature_def) {
            $return = $this->defaultHookPointCallbackEach($feature_def, $args);
        }
        return $return;
    }
    /**
     * 基本Feature呼び出しの個別処理
     */
    protected function defaultHookPointCallbackEach($feature_def, $args)
    {
        // pre_callback処理
        if ($feature_def["pre_callback"]) {
            $pre_return = call_user_func_array($feature_def["pre_callback"], $args);
            if ($pre_return===false) return null;
            $args[] = $pre_return;
        }
        return call_user_func_array($feature_def["callback"], $args);
    }
}
