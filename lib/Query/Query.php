<?php
namespace R\Lib\Query;

/**
 *
 */
class Query
{
    public $type = null;
    public $table = null;
}

/*
    $this->table("Member");
        use R\Lib\Query\TableAccess;
        return new TableAccess(array(
            "model" => $model_name,
            "access_as" => $this->auth());

    $t = $this->table("Member")
        ->select_by_id($id);
    list($ts,$p) = $this->table("Member")
        ->search($this->list_setting,$input)
        ->select_pagenated();
 */
class MemberModel extends Model
{
    protected $table = "Member";
    protected $role = "";

    public function id ($id)
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
    public function select_forAdmin ()
    {
        $this->query->removeWhere("valid_flg");
        $this->query->where(array("del_flg"=>1));
        $this->query->table($this->entity());
        return $this->execSelect($this->query);
    }
    public function select_forStaff ()
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