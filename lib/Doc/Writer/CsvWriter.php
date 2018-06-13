<?php
namespace R\Lib\Doc\Writer;

class CsvWriter extends Writer_Base
{
    public function write ($filename)
    {
        $this->touchFile($filename);
        csv_open($filename, "w", array(
            "ignore_empty_line" => true,
        ))->writeLines($this->content->exportCsv());
    }
    public function getDownloadResponse ($filename)
    {
        $csv = csv_open("php://temp", "w", array(
            "ignore_empty_line" => true,
        ));
        $csv->writeLines($this->content->exportCsv());
        return app()->http->response("stream", $csv->getHandle(), array("headers"=>array(
            'content-type' => 'application/octet-stream',
            'content-disposition' => 'attachment; filename='.basename($filename)
        )));
    }
    public function getPreviewHtml ()
    {
        $lines = $this->content->exportCsv();
        $html = '<table rules="all" border="1" style="font-size:small;">';
        foreach ($lines as $line) {
            $html .= '<tr>';
            foreach ($line as $cell) {
                $html .= '<td>'.(strlen($cell) ? htmlspecialchars($cell) : "&nbsp;").'</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</table>';
        return $html;
    }
}
