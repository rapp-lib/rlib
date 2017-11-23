    /**
     * CSVアップロードフォーム
     */
    protected static $form_entry_csv = array(
        "csrf_check" => true,
        "fields" => array(
            "csv_file" => array("storage" => "tmp"),
        ),
        "rules" => array(
            "csv_file",
            array("csv_file", 'csv_form', 'form'=>'<?=$controller->getName()?>.csv'),
        ),
    );
<?=$pageset->getPageByType("import")->getMethodDecSource()?>
    {
        $this->forms["entry_csv"]->restore();
        if ($this->forms["entry_csv"]->receive($this->input)) {
            if ($this->forms["entry_csv"]->isValid()) {
                $this->forms["entry_csv"]->save();
                return $this->redirect("id://<?=$pageset->getPageByType("complete")->getLocalPage()?>");
            }
        } elseif ( ! $this->input["back"]) {
            $this->forms["entry_csv"]->clear();
        }
    }
<?=$pageset->getPageByType("complete")->getMethodDecSource()?>
    {
        $this->forms["entry_csv"]->restore();
        if ( ! $this->forms["entry_csv"]->isEmpty()) {
            if ( ! $this->forms["entry_csv"]->isValid()) {
                return $this->redirect("id://<?=$pageset->getPageByType("import")->getLocalPage()?>", array("back"=>"1"));
            }
            // CSVファイルを開く
            $csv_file = app()->file->getFileByUri($this->forms["entry_csv"]["csv_file"])->getSource();
            $csv = $this->forms["csv"]->openCsvFile($csv_file, "r");
            // DBへの登録処理
            app()->db()->begin();
            while ($form = $csv->readForm()) $form->getRecord()->save();
            app()->db()->commit();
            $this->forms["entry_csv"]->clear();
        }
        return $this->redirect("id://<?=$pageset->getBackPage()->getFullPage($pageset->getPageByType("complete"))?>", array("back"=>"1"));
    }
