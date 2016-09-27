
    //-------------------------------------
    // Action: CSVアップロード確認
    public function act_entry_csv_confirm () {

        $this->context("c",1,true);
        $this->c->input($_REQUEST["c"]);

        $csv_filename =obj("UserFileManager")->get_uploaded_file(
                $this->c->input("Import.csv_file"), "private");

        if ( ! $csv_filename) {

            redirect("page:.entry_csv_form");
        }

        redirect('page:.entry_csv_exec');
    }