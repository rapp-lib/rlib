<?php
namespace R\Lib\Controller;

class AdminProductMasterController
{
    //TODO: type=での形式変換はLRA/UserFile系の機能の集約←Request実装後
    protected static $form_search = array(
        "autoload" => array(
            "pages" => array(".view_list", ".view_csv"),
            // 管理画面系の戻るリンクの動作のためにSessionを有効にすることも可能
            "session" => false,
            // pagesへのリンクにURL経由で全Valuesを渡すようにする指定
            "values_over_request" => true,
        ),
        // findBySearchFormができるようになる
        "table" => "Product",
        // 使用可能な項目、必要に応じて形式変換、検索変換の指定
        "fields" => array(
            "freeword" => array("search"=>"word", "col"=>array("name","mail")),
            "category" => array("search"=>"eq"),
            "open_date_start" => array("search"=>"date_range_start", "type"=>"split_date"),
            "open_date_end" => array("search"=>"date_range_end", "type"=>"split_date"),
            "p" => array("search"=>"page", "col"=>null),
            "order" => array("search"=>"sort", "col"=>null),
        ),
    );
    protected static $form_entry = array(
        // 指定のページで自動的に読み込む
        "autoload" => array(
            "pages" => array(".entry_form", ".entry_confirm", ".entry_exec"),
            "session" => true,
            // pagesへのリンクにSessionサブキーを渡すようにする指定
            "session_over_request" => true,
        ),
        // findById/saveができるようになる
        "table" => "Product",
        // 使用可能な項目
        // Requestから値を取り込むとき、Inputに値を渡す時に変換をかける
        "fields" => array(
            "name",
            "mail",
            "tel" => array("type"=>"split_text", "delim"=>"-"),
            "mail_confirm" => array("col"=>null),
            "open_date" => array("type"=>"split_date"),
            "main_img_file" => array("type"=>"file"),
            "sub_img_files" => array("type"=>"multiple", "fields" => array(
                "title",
                "img_file" => array("type"=>"file"),
            )),
            "category_ids",
        ),
        // 入力チェックの記述
        // 変換済みの値に対して入力チェック
        "rules" => array(
            "name",
            "mail",
            array("mail", "mail_format"),
            "mail_confirm",
            array("mail_confirm", "confirm", "field"=>"mail"),
            array("sub_img_files", "type"=>"count", "min"=>1, "max"=>10),
            "sub_img_files/*/title",
        ),
        // CSV入出力用の設定
        // "csv" => array(
        //     "file_charset" =>"SJIS-WIN",
        //     "data_charset" =>"UTF-8",
        //     "ignore_empty_line" =>true,
        // ),
    );
    protected static $form_entry_csv = array(
        "autoload" => array(
            "pages" => array(".entry_csv_form", ".entry_csv_confirm", ".entry_csv_exec"),
            "session" => true,
            "session_on_request" => true,
        ),
        "table" => "Product",
        "fields" => array(
            "csv_file" => array("type"=>"file"),
        ),
        "rules" => array(
            "csv_file",
        ),
    );
    protected $csv_setting = array(
        "rows" =>array(
            "id" =>"#ID",
            "name" =>"名称",
            "img" =>"写真",
            "category" =>"カテゴリ",
            "open_date" =>"公開日時",
        ),
        "filters" =>array(
            array("filter" =>"sanitize"),
            array("target" =>"category",
                    "filter" =>"list_select",
                    "list" =>"product_category"),
            array("target" =>"open_date",
                    "filter" =>"date"),
            array("filter" =>"validate",
                    "required" =>array(),
                    "rules" =>array()),
        ),
    );

    /**
     * @page
     * @title 製品管理 一覧表示
     */
    public function actViewList ()
    {
        // ※自動処理
        // if ($this->forms["search"]->received()) {
        //     $this->forms["search"]->clear();
        //     $this->forms["search"]->setValues($this->request);
        // }

        // $this->vars["ts"] = table("Product")
        //     ->findBySearchForm($this->list_setting, $this->c->input())
        //     ->select();

        $this->vars["ts"] = $this->forms["search"]->findBySearchForm()->select();
        $this->vars["p"] = $this->vars["ts"]->getPager();
    }

    /**
     * @page
     * @title 製品管理 入力フォーム
     */
    public function actEntryForm ()
    {
        if ($this->forms["entry"]->received()) {
            if ($this->forms["entry"]->hasValidValues()) {
                redirect("page:.entry_exec");
            }
        } elseif ($id = $this->request["id"]) {
            $this->forms["entry"]->initById($id);
            // ID設定＝初期化、Tableからのデータ読み込みも行う
            // $form = form()->create($this->form_entry)
            //     ->setValues($values)
            //     ->setId($values["id"])
            //     ->save();
            // 複製の操作
            // $this->forms["entry"]->changeId(null);
        }
    }

    /**
     * @page
     * @title 製品管理 確認
     */
    public function act_entry_confirm ()
    {
    }

    /**
     * @page
     * @title 製品管理 完了
     */
    public function act_entry_exec ()
    {
        if ($this->forms["entry"]->hasValidValues()) {
            $this->forms["entry"]->setValuesByEntryForm()->save();
            $this->forms["entry"]->clear();
        }
        redirect("page:.index");
    }

    /**
     * @page
     * @title 製品管理 削除
     */
    public function act_delete ()
    {
        if ($id = $this->request["id"]) {
            table("Product")->deleteById($id);
        }
        redirect("page:.index");
    }

    /**
     * @page
     * @title 製品管理 CSVダウンロード
     */
    public function act_view_csv ()
    {
        set_time_limit(0);
        registry("Report.error_reporting",E_USER_ERROR|E_ERROR);
        //report()->setLogErrorOnly(true);

        $res =$this->forms["search"]->findBySearchForm()->selectNoFetch();

        // CSVファイルの書き込み準備
        $csv_filename =registry("Path.tmp_dir")
            ."/csv_output/Product-".date("Ymd-His")."-"
            .sprintf("%04d",rand(0,9999)).".csv";
        $csv =new CSVHandler($csv_filename,"w",$this->csv_setting);

        while ($t =$res->fetch()) {
            $csv->write_line($t);
        }

        // データ出力
        clean_output_shutdown(array(
            "download" =>basename($csv_filename),
            "file" =>$csv_filename,
        ));
    }

    /**
     * @page
     * @title CSVインポートフォーム
     */
    public function act_entry_csv_form ()
    {
        if ($this->forms["entry_csv"]->received()) {
            if ($this->forms["entry_csv"]->hasValidValues()) {
                redirect("page:.entry_csv_exec");
            }
        }
    }

    /**
     * @page
     * @title CSVインポート確認
     */
    public function act_entry_csv_confirm ()
    {
    }

    /**
     * @page
     * @title CSVインポート完了
     */
    public function act_entry_csv_exec ()
    {
        if ($this->forms["entry_csv"]->hasValidValues()) {
            $csv_filename =obj("UserFileManager")
                ->get_uploaded_file($this->forms["entry_csv"]["csv_file"], "private");
            $csv =new CSVHandler($csv_filename,"r",$this->csv_setting);
            // DBへの登録処理
            table("Product")->transactionBegin();
            while (($t=$csv->read_line()) !== null) {
                table("Product")->save($t);
            }
            table("Product")->transactionCommit();
        }
        redirect("page:.view_list");
    }
}

/**
 *
 */
class Controller_Base
{
    protected $request;
    protected $response;
    protected $forms;

    public function __construct ()
    {
        $this->request = $_REQUEST;
        $this->response = & $this->vars;
        $this->forms = form()->getAutoloadForms(get_class($this));
    }
}
