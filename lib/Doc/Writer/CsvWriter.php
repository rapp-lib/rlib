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
    public function preview ()
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
