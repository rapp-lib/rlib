    /**
     * 検索フォーム
     */
    protected static $form_search = array(
        "search_page" => ".view_list",
<? if ( ! $t["nodef"]): ?>
        "search_table" => "<?=$t["name"]?>",
<? endif; /* $t["nodef"]*/ ?>
        "fields" => array(
            "freeword" => array("search"=>"word", "target_col"=>array(<? foreach ($this->filter_fields($t["fields"],"search") as $tc): ?>"<?=$tc['short_name']?>",<? endforeach; ?>)),
            "p" => array("search"=>"page", "volume"=>20),
            "order" => array("search"=>"sort", "default"=>"<?=$t["name"]?>.<?=$t['pkey']?>@ASC"),
        ),
    );
    /**
     * @page
     * @title <?=$controller_label?> 一覧表示
     */
    public function act_view_list ()
    {
        if ($this->forms["search"]->receive()) {
            $this->forms["search"]->save();
        } elseif ($this->request["back"]) {
            $this->forms["search"]->restore();
        }
        $this->vars["ts"] = $this->forms["search"]->search()->select();
    }