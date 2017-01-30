<?php
    $controller_label = $controller->getAttr("label");

    $role_required = $controller->getAttr("auth");
    $role_login = $controller->getAttr("accessor");
    $role_accessor = $controller->getAttr("accessor");

    $table = $controller->getAttr("table");
    $__table_instance = 'table("'.$table.'")';

?><?="<!?php\n"?>
namespace R\App\Controller;

/**
 * @controller
 */
class <?=$controller->getClassName()?> extends Controller_App
{
    /**
     * 認証設定
     */
    protected static $access_as = <?=$role_accessor ? '"'.$role_accessor.'"' : 'null'?>;
    protected static $priv_required = <?=$role_required ? "true" : "false"?>;
<? if ($c["type"] == "master"): ?>
    /**
     * @page
     * @title <?=$controller_label?> TOP
     */
    public function act_index ()
    {
<? if ($c["usage"] == "form"): ?>
        return redirect("page:.entry_form");
<? else: ?>
        return redirect("page:.view_list");
<? endif; ?>
    }
<? if ($c["usage"] != "form"): ?>
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
<? endif /* $c["usage"] != "form" */ ?>
<? if ($c["usage"] != "form"): /* ------------------- act_view_* ------------------ */ ?>
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
<? endif; /* $c["usage"] != "form" */ ?>
<? if ($c["usage"] != "view"): ?>
    /**
     * 入力フォーム
     */
    protected static $form_entry = array(
        "auto_restore" => true,
        "form_page" => ".entry_form",
<? if ( ! $t["nodef"]): ?>
        "table" => "<?=$t["name"]?>",
<? endif; /* $t["nodef"]*/ ?>
        "fields" => array(
<? if ( ! $t["nodef"]): ?>
            "<?=$t['pkey']?>",
<? endif; /* $t["nodef"]*/ ?>
<? foreach ($this->filter_fields($t["fields"],"save") as $tc): ?>
            "<?=$tc['short_name']?>"<?=$tc['field_def']?>,
<? endforeach; ?>
        ),
        "rules" => array(
        ),
    );
<? endif /* $c["usage"] != "view" */ ?>
<? if ($c["usage"] != "view"): /* ------------------- act_entry_* ------------------ */ ?>
    /**
     * @page
     * @title <?=$controller_label?> 入力フォーム
     */
    public function act_entry_form ()
    {
        if ($this->forms["entry"]->receive()) {
            if ($this->forms["entry"]->isValid()) {
                $this->forms["entry"]->save();
                return redirect("page:.entry_confirm");
            }
        } elseif ($id = $this->request["id"]) {
            $this->forms["entry"]->init($id);
        } elseif ( ! $this->request["back"]) {
            $this->forms["entry"]->clear();
        }
    }
    /**
     * @page
     * @title <?=$controller_label?> 確認
     */
    public function act_entry_confirm ()
    {
<? if ($c["usage"] != "form"): ?>
        return redirect("page:.entry_exec");
<? endif; ?>
    }
    /**
     * @page
     * @title <?=$controller_label?> 完了
     */
    public function act_entry_exec ()
    {
        if ( ! $this->forms["entry"]->isEmpty()) {
            if ( ! $this->forms["entry"]->isValid()) {
                return redirect("page:.entry_form", array("back"=>"1"));
            }
<? if ($t["nodef"]): ?>
            // メールの送信
            util("Mail")->factory()
                ->import("sample.php")
                ->assign("form", $this->forms["entry"])
                ->send();
<? else: /* $t["nodef"] */ ?>
            $this->forms["entry"]->getRecord()->save();
<? endif; /* $t["nodef"] */ ?>
            $this->forms["entry"]->clear();
        }
<? if ($c["usage"] != "form"): ?>
        return redirect("page:.view_list", array("back"=>"1"));
<? endif; ?>
    }
<? endif; /* $c["usage"] != "view" */ ?>
<? if($c["usage"] == ""): /* ------------------- act_delete_* ------------------ */ ?>
    /**
     * @page
     * @title <?=$controller_label?> 削除
     */
    public function act_delete ()
    {
        if ($id = $this->request["id"]) {
            <?=$__table_instance?>->deleteById($id);
        }
        return redirect("page:.view_list", array("back"=>"1"));
    }
<? endif; /* $c["usage"] == "" */ ?>
<? if($c["use_csv"]): /* ------------------- csv_setting ------------------ */ ?>
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
<? endif; /* $c["use_csv"] */ ?>
<? if($c["usage"] != "form" && $c["use_csv"]): /* ------------------- act_view_csv ------------------ */ ?>
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
<? endif; /* $c["usage"] != "form" && $c["use_csv"] */ ?>
<? if($c["usage"] != "view" && $c["use_csv"]): ?>
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
<? endif /* $c["usage"] != "view" && $c["use_csv"] */ ?>
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
<? endif; /* $c["usage"] != "view" && $c["use_csv"] */ ?>
<? elseif ($c["type"] == "login"): /* $c["type"] == "master" */ ?>
    /**
     * ログインフォーム
     */
    protected static $form_login = array(
        "form_page" => ".index",
        "fields" => array(
            "login_id",
            "login_pass",
            "redirect",
        ),
        "rules" => array(
        ),
    );
    /**
     * @page
     * @title <?=$controller_label?> TOP
     */
    public function act_index ()
    {
        return redirect("page:.login");
    }
    /**
     * @page
     * @title <?=$controller_label?> ログインフォーム
     */
    public function act_login ()
    {
        if ($this->forms["login"]->receive()) {
            if ($this->forms["login"]->isValid()) {
                // ログイン処理
                if (app()->auth->login("<?=$c["access_as"]?>", $this->forms["login"])) {
                    // ログイン成功時の転送処理
                    if ($redirect = $this->forms["login"]["redirect"]) {
                        return redirect("url:".$redirect);
                    } else {
                        return redirect("page:<?=builder()->getSchema()->getController($c["name"])->getRole()->getIndexController()->getName()?>.index");
                    }
                } else {
                    $this->vars["login_error"] = true;
                }
            }
        // 転送先の設定
        } elseif ($redirect = $this->request["redirect"]) {
            $this->forms["login"]["redirect"] = sanitize_decode($redirect);
        }
    }
    /**
     * @page
     * @title <?=$controller_label?> ログアウト
     */
    public function act_logout ()
    {
        // ログアウト処理
        app()->auth->logout("<?=$c["access_as"]?>");
        // ログアウト後の転送処理
        return redirect("page:.login");
    }
<? elseif ($c["type"] == "index"): /* $c["type"] == "login" */ ?>
    /**
     * @page
     * @title <?=$controller_label?> INDEX<?="\n"?>
     */
    public function act_index ()
    {
    }
    /**
     * @page
     * @title <?=$controller_label?> STATIC<?="\n"?>
     */
    public function act_static ()
    {
    }
<? endif; /* $c["type"] == "index" */ ?>}

