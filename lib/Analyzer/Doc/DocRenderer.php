<?php
namespace R\Lib\Analyzer\Doc;

class DocRenderer
{
    private static $formats = array(
        "sheet" => array(
            "txt" => array(
                "delim" => "\n",
                "return_code" => "\n\n",
                "file_charset" => "UTF-8",
            ),
            "csv" => array(),
        ),
    );
    /**
     * SheetのプレビューHTML作成
     */
    public static function sheetPreview ($sheet)
    {
        $html ='<h3>'.$sheet->getDoc()->getName()."/".$sheet->getName().'<h3>';
        $html ='<table style="border:black 1px solid;">';
        foreach ($sheet->getTable() as $i => $line) {
            if ($i===0) $html .='<tr style="background-locor:#cccccc;"><td>&nbsp;</td>';
            else $html .='<tr><td>['.sprintf('%04d',$n+1).']</td>';
            foreach ($line as $v) $html .='<td>'.$v.'</td>';
            $html .='</tr>';
        }
        $html .='</table>';
        return $html;
    }
    /**
     * Sheetの保存
     */
    public static function sheetSave ($sheet, $format)
    {
        $filename = $shelf->getBaseFilename();
        if ( ! is_dir(dirname($filename))) mkdir(dirname($filename), 0777, true);
        $csv = csv_open($filename.".".$format, "w", (array)self::$formats["sheet"][$format]);
        $csv->writeLines($sheet->getTable());
    }
    /**
     * Sheetの読み込み
     */
    public static function sheetLoad ($sheet, $format)
    {
        $filename = $shelf->getBaseFilename();
        if ( ! is_dir(dirname($filename))) return null;
        $csv = csv_open($filename.".".$format, "r", (array)self::$formats["sheet"][$format]);
        $sheet->setTable($csv->readLines());
    }
}
