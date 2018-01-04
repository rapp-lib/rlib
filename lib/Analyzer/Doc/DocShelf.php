<?php
namespace R\Lib\Analyzer\Doc;

class DocShelf
{
    public function test()
    {
        $sheet = new Sheet(constant("R_APP_ROOT_DIR")."/tmp/docs/basic/test.csv", array(
            "test1"=>"テスト1",
            "test2"=>"テスト2",
        ));
        $sheet->addLine(array("test1"=>"あ\n\nああ","test2"=>"いいい"));
        $sheet->addLine(array("test1"=>"ううう","test2"=>"えええ"));
        $sheet->save();
    }
}

class Sheet
{
    protected $filename;
    protected $header;
    protected $lines = array();
    public function __construct($filename, $header)
    {
        $this->filename = $filename;
        $this->header = $header;
    }
    public function addLine($line)
    {
        $this->lines[] = $line;
    }
    public function save()
    {
        $csv = csv_open($this->filename.".mid_out.txt", "w", $opt=array(
            "delim" => "\n",
            "return_code" => "\n\n",
            "file_charset" => "UTF-8",
            "rows" => $this->header,
        ));
        $csv->writeLines($this->lines);

        $csv = csv_open($this->filename.".txt", "r", $opt);
        $lines = $csv->readLines();
        report($lines);

        $csv = csv_open($this->filename.".csv", "w", array(
            "rows" => $this->header,
        ));
        $csv->writeLines($lines);
    }
}
