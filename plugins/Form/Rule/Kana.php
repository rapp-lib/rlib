<?php

namespace R\Lib\Form\Rule;

/**
 *
 */
class Kana extends BaseRule {

    /**
     * override
     */
    protected $message ="全角カナのみで入力してください";

    /**
     * override
     */
    public function check ($value) {

        return  ! strlen($value) || preg_match(
                "!^(ア|イ|ウ|エ|オ|カ|キ|ク|ケ|コ|サ|シ|ス".
                "|セ|ソ|タ|チ|ツ|テ|ト|ナ|ニ|ヌ|ネ|ノ|ハ|ヒ".
                "|フ|ヘ|ホ|マ|ミ|ム|メ|モ|ヤ|ユ|ヨ|ラ|リ|ル".
                "|レ|ロ|ワ|ヲ|ン|ァ|ィ|ゥ|ェ|ォ|ッ|ャ|ュ|ョ".
                "|ー|ガ|ギ|グ|ゲ|ゴ|ザ|ジ|ズ|ゼ|ゾ|ダ|ヂ|ヅ".
                "|デ|ド|バ|ビ|ブ|ベ|ボ|パ|ピ|プ|ペ|ポ|ヴ| |　".
                "|・)*$!u",$value);
    }
}