<?php 

class ModuleProvider {
    
    protected $call_handler =null;
    protected $common_args =array();
    protected $modules =array();
    
    public function __construct ($params=array()) {
        
        $this->call_handler =$params["call_handler"];
        $this->common_args =$params["common_args"];
    }
    
    public function add ($module_name, $options=array()) {
        
        if (is_array($module_name)) {
            
            foreach ($module_name as $k=>$v) {
                
                $this->add($k,$v);
            }
            
            return;
        }
        
        $this->modules[$module_name] =$options;
    }
    
    public function get ($module_name) {
        
        return $module_name
                ? $this->modules[$module_name]
                : $this->modules;
    }
    
    public function call () {
        
        $args =func_get_args();
        $module_name =array_shift($args);
        
        foreach ($this->common_args as $common_arg) {
            
            array_unshift($args,$common_arg);
        }
        
        $result =call_user_func($this->call_handler,$module_name,$args,$this->modules[$module_name]);
        
        return $result;
    }
}