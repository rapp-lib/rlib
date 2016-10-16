<?php

/*
    // Sample:

    // テンプレートを読み込んでPDF生成
    $pdf =new PDFHandler(array(
        "orientation" =>"P", // 用紙の向き(P:縦 / L:横)
        "size_unit" =>"mm", // 用紙サイズの単位
        "size" =>array(595.28, 841.89), // 用紙サイズ(横,縦)
        "page_margin_top" =>0, // ページ上余白
        "page_margin_left" =>0, // ページ左余白
        "orientation" =>"P", // 用紙の向き(P:縦 / L:横)
        "template" =>"tmpl_A5L.pdf", // テンプレートPDFファイル
    ));

    // テキスト
    $pdf->draw_text(array(
        "text" =>"こんにちは", // 描画するテキスト
        "font" =>"MS-Gothic", // フォント
                // (MS-Gothic / MS-PGothic / MS-UIGothic / MS-Mincho / MS-PMincho)
        "size" =>9, // フォントサイズ
        "bold" =>false, // 太字
        "italick" =>false, // 斜体
        "underline" =>false, // 下線
        "x" =>0, // X座標
        "y" =>0, // Y座標
        "w" =>0, // セル幅
        "h" =>5, // 1行の高さ
        "border" =>0, // 境界線(0:無効 / 1:有効)
        "align" =>"L", // 横揃え(L:左 / C:中央 / R:右)
        "r" =>0, // 描画色 赤(0-255)
        "g" =>0, // 描画色 青(0-255)
        "b" =>0, // 描画色 緑(0-255)
        "break" =>true, // 折り返し、改行の有効
    ));

    // 画像描画
    $pdf->draw_image(array(
        "image" =>"./sample.gif", // 描画する画像ファイル名
        "type" =>null, // 画像形式（:拡張子から推測 / JPEG / PNG / GIF）
        "x" =>50, // X座標
        "y" =>50, // Y座標
        "w" =>50, // 幅（0:原寸）
        "h" =>20, // 高さ（0:原寸）
    ));

    // PDF出力（ファイル名指定可能）
    $pdf->output();
*/

    // composerから読み込む
    //require_once(dirname(__FILE__).'/PDFHandler/mbfpdf.php');

//-------------------------------------
// PDFファイル生成機能
class PDFHandler {

    //-------------------------------------
    // MBFPDFオブジェクト
    public $pdf =null;

    protected $page_number =1;
    protected $page_count =0;
    protected $font_loaded =array();

    //-------------------------------------
    // 初期化
    public function __construct ($options=array()) {

        $default_options =array(
            "orientation" =>"P", // 用紙の向き(P:縦 / L:横)
            "size_unit" =>"mm", // 用紙サイズの単位
            "size" =>array(595.28, 841.89), // 用紙サイズ(横,縦)
            "page_margin_top" =>0, // ページ上余白
            "page_margin_left" =>0, // ページ左余白
            "orientation" =>"P", // 用紙の向き(P:縦 / L:横)
            "template" =>null, // テンプレートPDFファイル
        );

        foreach ($default_options as $k => $v) {

            if ( ! isset($options[$k])) {

                $options[$k] =$v;
            }
        }

        $this->pdf =new mbfpdf(
                $options["orientation"],
                $options["size_unit"],
                $options["size"]);
        $this->pdf->SetMargins(
                $options["page_margin_top"],
                $options["page_margin_left"]);
        $this->pdf->SetAutoPageBreak(false);

        if ($options["template"]) {

            $this->pdf->setSourceFile($options["template"]);
            $this->pdf->Open();
            $this->page_count =$this->pdf->parsers[$options["template"]]->getPageCount();
        }

        $this->next_page();
    }

    //-------------------------------------
    // PDFファイルの出力
    public function output ($filename=null, $dest=null) {

        return $this->pdf->output($filename,$dest);
    }

    //-------------------------------------
    // 次のページへ移動
    public function next_page () {

        $this->pdf->AddPage();

        if ($this->page_count > 0 && $this->page_number <= $this->page_count) {

            $index =$this->pdf->ImportPage($this->page_number);
            $this->pdf->useTemplate($index,null,null,0,0,true);
        }

        $this->page_number++;
    }

    //-------------------------------------
    // テキストの挿入
    public function draw_text ($options=array()) {

        $default_options =array(

            "text" =>null, // 描画するテキスト

            "font" =>"MS-Gothic", // フォント
                    // (MS-Gothic / MS-PGothic / MS-UIGothic / MS-Mincho / MS-PMincho)

            "size" =>9, // フォントサイズ
            "bold" =>false, // 太字
            "italick" =>false, // 斜体
            "underline" =>false, // 下線

            "x" =>0, // X座標
            "y" =>0, // Y座標
            "w" =>0, // セル幅
            "h" =>5, // 1行の高さ
            "border" =>0, // 境界線(0:無効 / 1:有効)
            "align" =>"L", // 横揃え(L:左 / C:中央 / R:右)

            "r" =>0, // 描画色 赤(0-255)
            "g" =>0, // 描画色 青(0-255)
            "b" =>0, // 描画色 緑(0-255)

            "break" =>true, // 折り返し、改行の有効
        );

        foreach ($default_options as $k => $v) {

            if ( ! isset($options[$k])) {

                $options[$k] =$v;
            }
        }

        if ($options["text"] === null) {

            return false;
        }

        // フォントの読み込み
        if ( ! $this->font_loaded[$options["font"]]) {

            $this->pdf->AddMBFont($options["font"], 'UNIJIS');
            $this->font_loaded[$options["font"]] =true;
        }

        $this->pdf->SetFont(
                $options["font"],
                (($options["bold"] ? "B" : "").
                ($options["italick"] ? "I" : "").
                ($options["underline"] ? "U" : "")),
                $options["size"]);
        $this->pdf->SetTextColor(
                $options["r"],
                $options["g"],
                $options["b"]);
        $this->pdf->SetXY(
                $options["x"],
                $options["y"]);

        // 改行有効
        if ($options["break"]) {

            $result =$this->pdf->MultiCell(
                    $options["w"],
                    $options["h"],
                    mb_convert_encoding($options["text"],"UTF-16","UTF-8"),
                    $options["border"],
                    $options["align"]);

        // 改行無効
        } else {

            $result =$this->pdf->Cell(
                    $options["w"],
                    $options["h"],
                    mb_convert_encoding($options["text"],"UTF-16","UTF-8"),
                    $options["border"],
                    0,
                    $options["align"]);
        }

        return $result;
    }

    //-------------------------------------
    // 画像の挿入
    public function draw_image ($options=array()) {

        $default_options =array(

            "image" =>null, // 描画する画像ファイル名
            "type" =>null, // 画像形式（:拡張子から推測 / JPEG / PNG / GIF）

            "x" =>0, // X座標
            "y" =>0, // Y座標
            "w" =>0, // 幅（0:原寸）
            "h" =>0, // 高さ（0:原寸）
        );

        foreach ($default_options as $k => $v) {

            if ( ! isset($options[$k])) {

                $options[$k] =$v;
            }
        }

        if ($options["image"] === null) {

            return false;
        }

        $result =$this->pdf->Image(
                $options["image"],
                $options["x"],
                $options["y"],
                $options["w"],
                $options["h"],
                $options["type"]);

        return $result;
    }
}