    /**
     * CSV設定
     */
    protected $csv_setting = array(
        "file_charset" => "SJIS-WIN",
        "data_charset" => "UTF-8",
        "rows" => array(
            "<?=$t['pkey']?>" => "#ID",
<? foreach ($this->filter_fields($t["fields"],"save") as $tc): ?>
            "<?=$tc['short_name']?>" => "<?=$tc['label']?>",
<? endforeach; ?>
        ),
        "filters" => array(
            array("filter" => "sanitize"),
<? foreach ($this->filter_fields($t["fields"],"save") as $tc): ?>
<? if ($tc['enum']): ?>
            array("target" => "<?=$tc['short_name']?>",
                    "filter" => "list_select",
<? if ($tc['type'] == "checklist"): ?>
                    "delim" => "/",
<? endif; /* $tc['type'] == "checklist" */ ?>
                    "enum" => "<?=$tc['enum']?>"),
<? endif; /* $tc['enum'] */ ?>
<? if ($tc['type'] == "date"): ?>
            array("target" => "<?=$tc['short_name']?>",
                    "filter" => "date"),
<? endif; /* $tc['type'] == "date" */ ?>
<? endforeach; ?>
        ),
        "ignore_empty_line" => true,
    );
    /**
     * @page
     * @title <?=$controller_label?> CSVダウンロード
     */
    public function act_view_csv ()
    {
        // 検索結果の取得
        $this->forms["search"]->restore();
        $res = $this->forms["search"]
            ->search()
            ->removePagenation()
            ->selectNoFetch();
        // CSVファイルの書き込み
        $csv_file = file_storage()->create("tmp");
        $csv = util("CSVHandler",array($csv_file->getFile(),"w",$this->csv_setting));
        while ($t = $res->fetch()) {
            $csv->write_line($t);
        }
        // データ出力
        response()->output(array(
            "download" => "export-".date("Ymd-his").".csv",
            "stored_file" => $csv_file,
        ));
    }
    /**
     * CSVアップロードフォーム
     */
    protected static $form_entry_csv = array(
        "auto_restore" => true,
        "fields" => array(
            "csv_file" => array("file_upload_to" => "tmp"),
        ),
        "rules" => array(
            "csv_file",
        ),
    );
    /**
     * @page
     * @title CSVインポートフォーム
     */
    public function act_entry_csv_form ()
    {
        if ($this->forms["entry_csv"]->receive()) {
            if ($this->forms["entry_csv"]->isValid()) {
                $this->forms["entry_csv"]->save();
                return redirect("page:.entry_csv_confirm");
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
        return redirect('page:.entry_csv_exec');
    }
    /**
     * @page
     * @title CSVインポート完了
     */
    public function act_entry_csv_exec ()
    {
        if ( ! $this->forms["entry_csv"]->isEmpty()) {
            if ( ! $this->forms["entry_csv"]->isValid()) {
                return redirect("page:.entry_csv_form", array("back"=>"1"));
            }
            // CSVファイルを開く
            $csv_file = file_storage()->get($this->forms["entry_csv"]["csv_file"]);
            $csv = util("CSVHandler", array($csv_file->getFile(),"r",$this->csv_setting));
            // DBへの登録処理
            <?=$__table_instance?>->transactionBegin();
            while ($t=$csv->read_line()) {
                <?=$__table_instance?>->save($t);
            }
            <?=$__table_instance?>->transactionCommit();
            $this->forms["entry_csv"]->clear();
        }
        return redirect("page:.view_list", array("back"=>"1"));
    }