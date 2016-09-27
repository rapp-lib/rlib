
    /**
    *
    */
    public function act_view_list () {

        $this->context("c",0);

        if ($_REQUEST["reset"]) {

            $this->c->input(false,false);
        }

        $this->c->input($_REQUEST["c"]);

        $input =$this->c->input();

        list($this->vars["ts"] ,$this->vars["p"]) =<?=$model_obj?>
                ->get_by_search_form($this->list_setting,$input);
    }