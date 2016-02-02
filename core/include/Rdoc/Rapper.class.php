<?php 

/**
 * 自動生成エンジン
 */
class Rapper extends Rapper_Base {
    
    /**
     * 初期化
     */
    public function __construct ($root_mod, $config=array()) {
        
        $this->require_mod($root_mod);
        $this->config =$r->apply_filters("config.init",(array)$config);
        $this->schema =$r->apply_filters("schema.init",array());
        $schema_index_list =$r->apply_filters("schema_index.init",array());
        
        foreach ($schema_index_list as $schema_index) {
            
            $schema = & $this->schema($schema_index);
            
            $schema =$r->apply_filters("schema.init.".$schema_index,$schema);
            
            foreach ($schema as & $schema_item) {
                
                $schema_item =$r->apply_filters("schema.".$schema_index,$schema_item);
            }
        }
        
        $this->deployee =$r->apply_filters("deployee.init",$this->deployee);
        
        foreach ($this->deployee as & $deployee) {
            
            $deployee =$r->apply_filters("deployee",$deployee);
        }
    }
    
    /**
     * 展開の実行
     */
    public function deploy ($deployee) {
        
        $r->apply_filters("deploy",$deployee);
	}
}