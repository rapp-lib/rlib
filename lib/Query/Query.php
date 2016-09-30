<?php
namespace R\Lib\Query;

    function table ($table_name, $access_as=null)
    {
        return $table = new TableChain(array(
            "table_name" => $table_name,
            "access_as" => $access_as));
    }

class TableChain
{
    public $table = null;
    public $access_as = null;
    public function __construct ($table_name, $access_as)
    {
        $class = "R\\App\\Table\\".str_camelize($table_name)."Table";
        $this->table = new $class($this);
        $this->access_as = $access_as;
    }
    public function __call ($method_name, $args=array())
    {
        $this->table->$method_name($args);
        return $this;
    }
}

class Table_Base
{
    protected $chain = null;
    protected $query = null;
    protected $table_name = null;
    protected $id_col_name = "id";
    protected $valid_flg_col_name = "valid_flg";
    protected $del_flg_col_name = "del_flg";
    protected $hook = array(
        "before_build_statement" => array(
            "where_clause" =>array(),
            "read_statement" =>array(),
            "write_statement" =>array(),
        ),
    );
    protected $refs = array(
        "member" => "id",
        "staff" => "incharge_staff_id",
        "Staff" => "incharge_staff_id",
    );
    protected $cols = array(
        "imgs" => array(
            "type"=>"assoc",
            "table"=>"MemberImg",
        ),
        "categories" => array(
            "type"=>"assoc",
            "table"=>"MemberCategoryAssoc",
            "reduce_by"=>"category_id",
        ),
        "detail" => array(
            "type"=>"assoc",
            "table"=>"MemberDetail",
        ),
    );
    public function __construct ($chain)
    {
        $this->chain = $chain;
    }
    public function modifier_by ($method_name, $args)
    {
        if (preg_match('!(_[a-z]+)([A-Z][a-zA-Z0-9]*)!',$method_name,$match)) {
            $modifier_name = $match[1];
            $method_name = str_replace($modifier_name,"",$method_name);
            $this->chain->$method_name($args);
        }
    }
    public function modifier_for ($method_name, $args)
    {
    }
    public function find_by ($value)
    {
        $this->query->where($this->id_col_name, $value);
    }

}
class Table_App extends Table_Base
{
}

class Query
{
    public $type = null;
    public $query = array();
    public function __call ($method_name, $args=array())
    {
        if (preg_match("!^([set|add])(.*?)$!",$method_name,$match)) {
            $op = $match[1];
            $key = str_underscore($match[2]);

            if ($op=="set" && count($args)==0) {
                unset($this->query[$key]);
            } elseif ($op=="set" && count($args)==1) {
                $this->query[$key] = $args[0];
            } elseif ($op=="set" && count($args)==2) {
                $this->query[$key][$args[0]] = $args[1];

            } elseif ($op=="add" && count($args)==1) {
                $this->query[$key][] = $args[0];

            } elseif ($op=="remove" && count($args)==0) {
                unset($this->query[$key]);
            } elseif ($op=="remove" && count($args)==1) {
                unset($this->query[$key][$args[0]]);
            }
        }
    }
    public function statement ($type)
    {
        $this->type = $type;
    }
    public function table ($table, $alias=false)
    {
        if (is_array($table)) {
            $this->query["table"] = $table;
        } else if ($alias !== false) {
            $this->query["table"] = array($table, $alias);
        } else {
            $this->query["table"] = $table;
        }
    }
    public function where ($k,$v=false)
    {
        if (is_array($k) || $v === false) {
            $this->query["conditions"][] = $k;
        } else {
            $this->query["conditions"][$k] = $v;
        }
    }
}

/*
    $this->table("Member");
        use R\Lib\Query\TableAccess;
        return new TableAccess(array(
            "model" => $model_name,
            "access_as" => $this->auth());

    $t = table("Member")->byId($id)->selectOne();
    $t = table("Member")->mine()->selectOne();
    $t = table("Member")->owned("member",$member_id)->selectOne();
    list($ts,$p) = table("Member")->search($this->list_setting, $input)->selectPagenated();
 */
class MemberTable extends Table_App
{
    public function by_Id ($id)
    {
        $this->query->where(array("id"=>$id));
    }
    public function find_byFriendIds ($friend_ids)
    {
        $ids = table("Member")
            ->where(array("friend_id"=>$friend_ids))
            ->selectHash("id");
        $this->query->where(array("id"=>$ids));
    }
    public function search ($list_setting, $input)
    {
        $query = $this->search_query($list_setting, $input);
        $this->query->mergeQuery($query);
    }
    public function select ()
    {
        if ($this->auth()->hasPriv("staff")) {
            $this->query->where(array("valid_flg"=>1));
        }
        if ($this->auth()->hasPriv("admin")) {
            $this->query->where(array("valid_flg"=>1));
        }
        $this->query->where(array("del_flg"=>1));
        $this->query->table($this->entity());
        return $this->execSelect($this->query);
    }
    public function select_fornotAdmin ()
    {
        $this->query->removeWhere("valid_flg");
        $this->query->where(array("del_flg"=>1));
        $this->query->table($this->entity());
        return $this->execSelect($this->query);
    }
    public function select_fornotStaff ()
    {
        $this->query->where(array("staff_id"=>$this->auth()->id()));
        $this->query->where(array("del_flg"=>1));
        $this->query->table($this->entity());
        return $this->execSelect($this->query);
    }
    public function update_forMember ()
    {
        $this->query->where(array("id"=>$this->auth()->id()));
        $this->query->where(array("del_flg"=>1));
        $this->query->table($this->entity());
        return $this->execSelect($this->query);
    }
    public function update_forAdmin ()
    {
        $this->query->where(array("del_flg"=>1));
        $this->query->table($this->entity());
        return $this->execUpdate($this->query);
    }
}