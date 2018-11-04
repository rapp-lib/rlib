<?php
namespace R\Lib\Table\Plugin;

class StdFeatureProvider extends BasePluginProvider
{
    // protected $features = array(
    //     "before_render"=>array(
    //         "on_read_attachDelFlg"=>array(
    //             "query_type"=>"read",
    //             "by_col_attr"=>"del_flg",
    //         ),
    //         "on_update_attachDelFlg"=>array(
    //             "query_type"=>"update",
    //             "by_col_attr"=>"del_flg",
    //         ),
    //     ),
    //     "after_exec"=>array(),
    //     "after_fetch"=>array(),
    //     "after_fetch_end"=>array(),

    //     "chain"=>array(
    //         "select"=>array("chain_end"=>true),
    //         "findById"=>array(),
    //     ),
    //     "result"=>array(),
    //     "record"=>array(),
    //     "form"=>array(),
    //     "search"=>array(),
    //     "read_blank_col"=>array(
    //         "retreive_alias"=>array(),
    //     ),
    //     "retreive_alias"=>array(),
    //     "affect_alias"=>array(),
    // );
    // public function on_read_attachDelFlg($query, $col_name)
    // {
    //     $query->where($query->getTableName().".".$col_name, 0);
    // }
    // public function on_update_attachDelFlg($query, $col_name)
    // {
    //     $query->setDelete(false);
    //     $query->setValue($col_name, 1);
    // }
    // public function chain_findById($query, $id)
    // {
    //     $id_col_name = $query->getDef()->getIdColName("id");
    //     $query->where($query->getTableName().".".$id_col_name, $id);
    // }
    // public function chain_select($query)
    // {
    //     $query->setType("select");
    //     return app("table.query_executer")->execFetchAll($query);
    // }
    // public function read_blank_col_retreive_alias($record)
    // {
    // }
}
