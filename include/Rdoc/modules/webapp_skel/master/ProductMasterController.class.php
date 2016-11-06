<?php require __DIR__."/../_include/controller.php"; ?>
<?=$__controller_header?>
<? if ($c["usage"] != "form"): ?>
    /**
     * 検索フォーム
     */
    protected static $form_search = array(
        "search_page" => ".view_list",
<? if ( ! $t["virtual"]): ?>
        "search_table" => "<?=$t["name"]?>",
<? endif; /* $t["virtual"]*/ ?>
        "fields" => array(
            "freeword" => array("search"=>"word", "target_col"=>array(<? foreach ($this->filter_fields($t["fields"],"search") as $tc): ?>"<?=$tc['short_name']?>",<? endforeach; ?>)),
            "p" => array("search"=>"page", "volume"=>20),
            "order" => array("search"=>"sort", "default"=>"<?=$t['pkey']?>@ASC"),
        ),
    );

<? endif /* $c["usage"] != "form" */ ?>
<? if ($c["usage"] != "view"): ?>
    /**
     * 入力フォーム
     */
    protected static $form_entry = array(
        "auto_restore" => true,
        "form_page" => ".entry_form",
<? if ( ! $t["virtual"]): ?>
        "table" => "<?=$t["name"]?>",
<? endif; /* $t["virtual"]*/ ?>
        "fields" => array(
            "<?=$t['pkey']?>",
<? foreach ($this->filter_fields($t["fields"],"save") as $tc): ?>
            "<?=$tc['short_name']?>"<?=$tc['field_def']?>,
<? endforeach; ?>
        ),
        "rules" => array(
        ),
    );

<? endif /* $c["usage"] != "view" */ ?>
<? if($c["usage"] != "view" && $c["use_csv"]): ?>
    /**
     * CSVアップロードフォーム
     */
    protected static $form_entry_csv = array(
        "auto_restore" => true,
        "fields" => array(
            "csv_file",
        ),
        "rules" => array(
            "csv_file",
        ),
    );

<? endif /* $c["usage"] != "view" && $c["use_csv"] */ ?>
<? if($c["use_csv"]): /* ------------------- csv_setting ------------------ */ ?>
    /**
     * CSV設定
     */
    protected $csv_setting = array(
        "file_charset" =>"SJIS-WIN",
        "data_charset" =>"UTF-8",
        "rows" =>array(
            "<?=$t['pkey']?>" =>"#ID",
<? foreach ($this->filter_fields($t["fields"],"save") as $tc): ?>
            "<?=$tc['short_name']?>" =>"<?=$tc['label']?>",
<? endforeach; ?>
        ),
        "filters" =>array(
            array("filter" =>"sanitize"),
<? foreach ($this->filter_fields($t["fields"],"save") as $tc): ?>
<? if ($tc['enum']): ?>
            array("target" =>"<?=$tc['short_name']?>",
                    "filter" =>"list_select",
<? if ($tc['type'] == "checklist"): ?>
                    "delim" =>"/",
<? endif; /* $tc['type'] == "checklist" */ ?>
                    "enum" =>"<?=$tc['enum']?>"),
<? endif; /* $tc['enum'] */ ?>
<? if ($tc['type'] == "date"): ?>
            array("target" =>"<?=$tc['short_name']?>",
                    "filter" =>"date"),
<? endif; /* $tc['type'] == "date" */ ?>
<? endforeach; ?>
            array("filter" =>"validate",
                    "required" =>array(),
                    "rules" =>array()),
        ),
        "ignore_empty_line" =>true,
    );

<? endif; /* $c["use_csv"] */ ?>
    /**
     * @page
     * @title <?=$c["label"]?> TOP
     */
    public function act_index ()
    {
<? if ($c["usage"] == "form"): ?>
        redirect("page:.entry_form");
<? else: ?>
        redirect("page:.view_list");
<? endif; ?>
    }

<? if ($c["usage"] != "form"): /* ------------------- act_view_* ------------------ */ ?>
    /**
     * @page
     * @title <?=$c["label"]?> 一覧表示
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

<? endif; /* $c["usage"] != "form" */ ?>
<? if ($c["usage"] != "view"): /* ------------------- act_entry_* ------------------ */ ?>
    /**
     * @page
     * @title <?=$c["label"]?> 入力フォーム
     */
    public function act_entry_form ()
    {
        if ($this->forms["entry"]->receive()) {
            if ($this->forms["entry"]->isValid()) {
                $this->forms["entry"]->save();
                redirect("page:.entry_confirm");
            }
        } elseif ($id = $this->request["id"]) {
            $this->forms["entry"]->init($id);
        } elseif ( ! $this->request["back"]) {
            $this->forms["entry"]->clear();
        }
    }

    /**
     * @page
     * @title <?=$c["label"]?> 確認
     */
    public function act_entry_confirm ()
    {
<? if ($c["usage"] != "form"): ?>
        redirect("page:.entry_exec");
<? endif; ?>
    }

    /**
     * @page
     * @title <?=$c["label"]?> 完了
     */
    public function act_entry_exec ()
    {
        if ( ! $this->forms["entry"]->isEmpty()) {
            if ( ! $this->forms["entry"]->isValid()) {
                redirect("page:.entry_form", array("back"=>"1"));
            }
<? if ($t["virtual"]): ?>
            // メールの送信
            $this->send_mail(array(
                "template" => "<?=$c["name"]?>",
                "vars" => $this->forms["entry"],
            ));
<? else: /* $t["virtual"] */ ?>
            $this->forms["entry"]->getRecord()->save();
<? endif; /* $t["virtual"] */ ?>
            $this->forms["entry"]->clear();
        }
<? if ($c["usage"] != "form"): ?>
        redirect("page:.view_list", array("back"=>"1"));
<? endif; ?>
    }

<? endif; /* $c["usage"] != "view" */ ?>
<? if($c["usage"] == ""): /* ------------------- act_delete_* ------------------ */ ?>
    /**
     * @page
     * @title <?=$c["label"]?> 削除
     */
    public function act_delete ()
    {
        if ($id = $this->request["id"]) {
            table("Product")->deleteById($id);
        }
        redirect("page:.view_list", array("back"=>"1"));
    }

<? endif; /* $c["usage"] == "" */ ?>
<? if($c["usage"] != "form" && $c["use_csv"]): /* ------------------- act_view_csv ------------------ */ ?>
    /**
     * @page
     * @title <?=$c["label"]?> CSVダウンロード
     */
    public function act_view_csv ()
    {
        set_time_limit(120);
        error_reporting(E_ERROR);
        // 検索結果の取得
        $this->forms["search"]->restore();
        $res =$this->forms["search"]
            ->search()
            ->removePagenation()
            ->selectNoFetch();
        // CSVファイルの書き込み
        $csv_filename =registry("Path.tmp_dir")
            ."/csv_output/<?=$t["name"]?>-".date("Ymd-His")."-"
            .sprintf("%04d",rand(0,9999)).".csv";
        $csv =new CSVHandler($csv_filename,"w",$this->csv_setting);
        while ($t = $res->fetch()) {
            $csv->write_line($t);
        }
        // データ出力
        clean_output_shutdown(array(
            "download" =>basename($csv_filename),
            "file" =>$csv_filename,
        ));
    }

<? endif; /* $c["usage"] != "form" && $c["use_csv"] */ ?>
<? if($c["usage"] != "view" && $c["use_csv"]): /* ------------------- act_csv_entry ------------------ */ ?>
    /**
     * @page
     * @title CSVインポートフォーム
     */
    public function act_entry_csv_form ()
    {
        if ($this->forms["entry_csv"]->receive()) {
            if ($this->forms["entry_csv"]->isValid()) {
                $this->forms["entry_csv"]->save();
                redirect("page:.entry_csv_confirm");
            }
        } elseif ( ! $this->request["back"]) {
            $this->forms["entry"]->clear();
        }
    }

    /**
     * @page
     * @title CSVインポート確認
     */
    public function act_entry_csv_confirm ()
    {
        redirect('page:.entry_csv_exec');
    }

    /**
     * @page
     * @title CSVインポート完了
     */
    public function act_entry_csv_exec ()
    {
        if ( ! $this->forms["entry_csv"]->isEmpty()) {
            if ( ! $this->forms["entry_csv"]->isValid()) {
                redirect("page:.entry_csv_form", array("back"=>"1"));
            }
            // CSVファイルを開く
            $csv_filename =obj("UserFileManager")
                ->get_uploaded_file($this->forms["entry_csv"]["csv_file"], "private");
            $csv =new CSVHandler($csv_filename,"r",$this->csv_setting);
            // DBへの登録処理
            <?=$__table_instance?>->transactionBegin();
            while ($t=$csv->read_line()) {
                <?=$__table_instance?>->save($t);
            }
            <?=$__table_instance?>->transactionCommit();
            $this->forms["entry_csv"]->clear();
        }
        redirect("page:.view_list", array("back"=>"1"));
    }
<? endif; /* $c["usage"] != "view" && $c["use_csv"] */ ?>
}