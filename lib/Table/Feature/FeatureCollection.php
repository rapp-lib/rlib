<?php
namespace R\Lib\Table\Feature;

class FeatureCollection
{
    protected $feature_defs = array();
    protected $hook_point_defs = array();

// -- 登録

    /**
     * HookPointの登録
     */
    public function registerHookPoint($hook_point, $hook_point_def=array())
    {
        $hook_point_def["hook_point"] = $hook_point;
        if ( ! $hook_point_def["filters"]) $feature_def["filters"] = array();
        if ( ! $hook_point_def["callback"]) {
            $hook_point_def["callback"] = null;
        }
        if ($this->hook_point_defs[$hook_point]) {
            $hook_point_def["parent"] = $this->hook_point_defs[$hook_point];
        }
        $this->hook_point_defs[$hook_point] = $hook_point_def;
    }
    /**
     * Featureの登録
     */
    public function registerFeature($hook_point, $feature_name, $feature_def=array())
    {
        $feature_def["hook_point"] = $hook_point;
        $feature_def["feature_name"] = $feature_name;
        if ( ! $feature_def["priority"]) {
            $feature_def["priority"] = 500;
        }
        if ( ! $feature_def["callback"]) {
            report_error("FeatureにCallbackの指定がありません", array(
                "feature_def"=>$feature_def,
            ));
        }
        if ($this->feature_defs[$hook_point][$feature_name]) {
            $feature_def["parent"] = $this->feature_defs[$hook_point][$feature_name];
        }
        $this->feature_defs[$hook_point][$feature_name] = $feature_def;
    }
    /**
     * Pluginクラスの登録
     */
    public function registerProvider($class)
    {
        if ( ! is_object($class)) $class = app()->make($class);
        $class->register($this);
    }

// -- 呼び出し

    /**
     * Featureの呼び出し
     */
    public function emit($hook_point, $args, $emit_option=array())
    {
        // HookPointの特定
        $hook_point_def = $this->hook_point_defs[$hook_point];
        if ( ! $hook_point_def) {
            report_error("hook_pointが登録されていません", array(
                "hook_point"=>$hook_point,
                "features"=>$this->getStatus(),
            ));
        }

        // 呼び出し対象Featureの決定
        $feature_defs = (array)$this->feature_defs[$hook_point];
        $filters = (array)$hook_point_def["filters"];
        // feature_nameの指定
        if ($feature_name = $emit_option["feature_name"]) {
            $filters[] = function($feature_def)use($feature_name){
                return $feature_def["feature_name"] === $feature_name;
            };
        }
        // 絞り込み
        $feature_defs = array_filter($feature_defs, function($feature_def) use ($filters, $args) {
            foreach ($filters as $filter) {
                if ( ! call_user_func($filter, $feature_def, $args)) return false;
            }
            return true;
        });
        // require_featureの指定があり、Featureが特定できなければエラー
        if ($hook_point_def["require_feature"] && ! $feature_defs) {
            report_error("Featureがありません : ".$hook_point.".".$feature_name, array(
                "hook_point"=>$hook_point,
                "feature_name"=>$feature_name,
                "args"=>$args,
            ));
        }

        // 整列（Priorityの適用）
        uasort($feature_defs, function($a, $b) {
            if ($a["priority"] > $b["priority"]) return -1;
            elseif ($a["priority"] < $b["priority"]) return +1;
            else return 0;
        });

        // Feature呼び出し処理
        return call_user_func($hook_point_def["callback"], $feature_defs, $args);
    }
    /**
     * 名前を指定してFeatureを呼び出す
     */
    public function call($hook_point, $feature_name, $args)
    {
        return $this->emit($hook_point, $args, array("feature_name"=>$feature_name));
    }
    /**
     * Middlewareの適用処理
     */
    public function applyMiddlewares($middlewares, $next)
    {
        // Middlewareの適用
        foreach ($middlewares as $middleware) {
            $next = function($args) use ($middleware, $next) {
                return call_user_func($middleware, $next, $args);
            };
        }
        return $next;
    }

// -- 登録状況の確認

    /**
     * getter
     */
    public function getHookPoints()
    {
        return $this->hook_point_defs;
    }
    /**
     * getter
     */
    public function getStatus()
    {
        $status = array();
        foreach ($this->hook_point_defs as $hook_point=>$hook_point_def) {
            $status[$hook_point] = array();
            foreach ((array)$this->feature_defs[$hook_point] as $feature_name=>$feature_def) {
                unset($feature_def["hook_point"]);
                unset($feature_def["feature_name"]);
                unset($feature_def["callback"]);
                if ($feature_def["priority"]==500) unset($feature_def["priority"]);
                $status[$hook_point][$feature_name] = $feature_def;
            }
        }
        return $status;
    }
}
