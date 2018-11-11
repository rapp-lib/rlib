<?php
namespace R\Lib\Table\Feature\Provider;
use R\Lib\Table\Feature\BaseFeatureProvider;

class HookPoint extends BaseFeatureProvider
{
    protected $hook_points = array(
        "before_render"=>array(),
        "after_exec"=>array(),
        "after_fetch_each"=>array(),
        "after_fetch_all"=>array(),
        "blank_col"=>array(),
        "chain"=>array("require_feature"=>true),
        "result"=>array("require_feature"=>true),
        "record"=>array("require_feature"=>true),
        "form"=>array("require_feature"=>true),
        "search"=>array("require_feature"=>true),
        "alias"=>array(),
        "affect_alias"=>array(),
    );

    // -- SQL組み立て実行前後処理

    // -- before_render

    public function filter_before_render($feature_def, $args)
    {
        if ($args[0] instanceof \R\Lib\Table\Query\Result) {
            $query = $args[0]->getStatement()->getQuery();
        } elseif ($args[0] instanceof \R\Lib\Table\Query\Payload) {
            $query = $args[0];
        } else {
            report_error("無効な引数", array("args"=>$args));
        }
        $query_type = $query->getType();
        if ($feature_def["query_type"] === $query_type) return true;
        if ($feature_def["query_type"] === "write"
            && in_array($query_type, array("update", "insert", "delete"))) {
            return true;
        }
        if ($feature_def["query_type"] === "read"
            && in_array($query_type, array("update", "select", "delete"))) {
            return true;
        }
        return false;
    }
    public function handler_before_render($feature_defs, $args)
    {
        if ($args[0] instanceof \R\Lib\Table\Query\Result) {
            $def = $args[0]->getStatement()->getQuery()->getDef();
        } elseif ($args[0] instanceof \R\Lib\Table\Query\Payload) {
            $def = $args[0]->getDef();
        } else {
            report_error("無効な引数", array("args"=>$args));
        }
        // 基本処理呼び出し
        $return = null;
        foreach ($feature_defs as $feature_def) {
            // Col別呼び出しパターン
            if ($col_attr = $feature_def["by_col_attr"]) {
                $col_attr_value = $feature_def["by_col_attr_value"] ?: true;
                $col_names = $def->getColNamesByAttr($col_attr, $col_attr_value);
                foreach ($col_names as $col_name) {
                    $args_copy = $args;
                    $args_copy[] = $col_name;
                    $return = $this->defaultHookPointCallbackEach($feature_def, $args_copy);
                }
            } else {
                $return = $this->defaultHookPointCallbackEach($feature_def, $args);
            }
        }
        return $return;
    }

    // -- after_exec

    public function filter_after_exec($feature_def, $args)
    {
        return $this->filter_before_render($feature_def, $args);
    }
    public function handler_after_exec($feature_defs, $args)
    {
        return $this->handler_before_render($feature_defs, $args);
    }

    // -- Call処理

    // -- chain

    public function handler_chain($feature_defs, $args)
    {
        $return = null;
        // 戻り値をQueryBuilder、第1引数をQueryPayloadに揃える
        if ($args[0] instanceof \R\Lib\Table\Def\TableDef) {
            $args[0] = $query = $args[0]->makeQuery();
            $return = $builder = $query->makeBuilder();
        } elseif ($args[0] instanceof \R\Lib\Table\Query\Builder) {
            $return = $builder = $args[0];
            $args[0] = $query = $builder->getQuery();
        } elseif ($args[0] instanceof \R\Lib\Table\Query\Payload) {
            $args[0] = $query = $args[0];
            $return = $builder = $args[0]->makeBuilder();
        } else {
            report_error("不正なChainの呼び出し", array(
                "args"=>$args,
            ));
        }
        // 基本処理呼び出し
        $return_tmp = $this->defaultHookPointCallback($feature_defs, $args);
        // chain_endの指定があればQueryBuilderではなく、戻り値をそのまま返す
        $feature_def = head($feature_defs);
        if ($feature_def["end"]) $return = $return_tmp;
        return $return;
    }

    // -- result : nop

    // -- record : nop

    // -- form : nop

    // -- 検索条件付与

    // -- search : nop

    // -- 値読み込みフック・Alias処理

    // -- blank_col : nop

    // -- alias : nop

    // -- affect_alias : nop
}
