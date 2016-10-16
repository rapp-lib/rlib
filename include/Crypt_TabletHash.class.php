<?php

//-------------------------------------
// ASCII情報の暗号化
class Crypt {

    //-------------------------------------
    // 数値の難読化
    public function encrypt_decimal (
            $target,
            $key=123,
            $crypt_body_length=10,
            $checksum_length=2) {

        $crypt =new Crypt_TabletHash(
                $crypt_body_length,
                $checksum_length,
                "0123456789");
        $result =$crypt->encrypt(sprintf('%09d',$target),$key);

        return $result;
    }
}

//-------------------------------------
// タブレットハッシュ暗号（距離隠蔽/固定長/低暗号性能）
class Crypt_TabletHash {

    protected $tablet_hash;
    protected $crypt_body_length;
    protected $checksum_length;

    //-------------------------------------
    // コンストラクタ
    public function __construct (
            $crypt_body_length=10,
            $checksum_length=2,
            $tablet_string="0123456789") {

        $this->tablet_hash =new TabletHash($tablet_string);
        $this->crypt_body_length =$crypt_body_length;
        $this->checksum_length =$checksum_length;
    }

    //-------------------------------------
    // 暗号化
    public function encrypt (
            $text,
            $key) {

        $text_length =strlen($text);

        if ($text_length > $this->crypt_body_length || $text_length == 0) {

            $text_length =$this->crypt_body_length;
        }

        $text_sequence =$this->tablet_hash->text_to_sequence(
                $text,$this->crypt_body_length);
        $text_length_sequence =$this->tablet_hash->number_to_sequence(
                $text_length-1,$this->crypt_body_length);

        $checksum_sequence =$this->tablet_hash->checksum(
                $text_sequence,$this->checksum_length);
        $key_sequence =$this->tablet_hash->checksum($key,1);
        $k =$this->tablet_hash->hash($checksum_sequence[0],$key_sequence[0]);

        $result_sequence =array();

        for ($i=0; $i<$this->crypt_body_length; $i++) {

            $n =$text_sequence[$i];
            $result_sequence[] =$k =$this->tablet_hash->hash($n,$k,$i);
        }

        return $this->tablet_hash->sequence_to_text($result_sequence)
                .$this->tablet_hash->sequence_to_text($checksum_sequence)
                .$this->tablet_hash->sequence_to_text($text_length_sequence);
    }

    //-------------------------------------
    // 復号化
    public function decrypt (
            $text,
            $key) {

        $text_part =substr(
                $text, 0, $this->crypt_body_length);
        $checksum_part =substr(
                $text, $this->crypt_body_length, $this->checksum_length);
        $text_length_part =substr(
                $text, $this->crypt_body_length+$this->checksum_length);

        $text_sequence =$this->tablet_hash->text_to_sequence($text_part);
        $text_length =1+$this->tablet_hash->sequence_to_number(
                $this->tablet_hash->text_to_sequence($text_length_part));

        $checksum_sequence =$this->tablet_hash->text_to_sequence($checksum_part);
        $key_sequence =$this->tablet_hash->checksum($key,1);
        $k =$this->tablet_hash->hash($checksum_sequence[0],$key_sequence[0]);

        $result_sequence =array();

        for ($i=0; $i<$this->crypt_body_length; $i++) {

            $n =$text_sequence[$i];
            $result_sequence[] =$this->tablet_hash->hash_invert($n,$k,$i);
            $k =$n;
        }

        $result_checksum_sequence
                =$this->tablet_hash->checksum($result_sequence,$this->checksum_length);

        return $result_checksum_sequence == $checksum_sequence
                ? $this->tablet_hash->sequence_to_text($result_sequence,$text_length)
                : null;
    }
}

//-------------------------------------
// タブレットハッシュ機能
class TabletHash {

    protected $tablet;
    protected $tablet_flip;
    protected $tablet_size;

    protected $prime;
    protected $prime_list =array(2,3,5,7,11,13,17,19,23,29,31,37,41
            ,43,47,53,59,61,67,71,73,79,83,89,97,101,103,107,109,113,127
            ,131,137,139,149,151,157,163,167,173,179,181,191,193,197,199);

    //-------------------------------------
    // コンストラクタ
    public function __construct ($tablet_string="0123456789") {

        $this->tablet =array_unique(str_split($tablet_string));
        $this->tablet_flip =array_flip($this->tablet);
        $this->tablet_size =count($this->tablet);

        foreach ($this->prime_list as $prime) {

            if ($prime >= $this->tablet_size) {

                break;
            }

            $this->prime =$prime;
        }
    }

    //-------------------------------------
    // ハッシュ関数
    public function hash ($n, $k=0, $m=0) {

        return ($n + $k + ($this->prime * ($m+1))) % $this->tablet_size;
    }

    //-------------------------------------
    // ハッシュ逆関数
    public function hash_invert ($n, $k=0, $m=0) {

        $d =($k + ($this->prime * ($m+1))) % $this->tablet_size;

        return $n < $d
                ? ($n - $d + $this->tablet_size) % $this->tablet_size
                : ($n - $d) % $this->tablet_size;
    }

    //-------------------------------------
    // チェックサムを生成
    public function checksum ($sequence, $length=1) {

        $k =0;
        $kt =array();

        for ($i=0; $i<count($sequence); $i++) {

            $kt[] =$k =$this->hash(
                    (int)$sequence[$i],
                    $k,
                    $i);
        }

        for ($i=0; $i<$length; $i++) {

            $result[] =$this->hash(
                    (int)$sequence[$i],
                    array_pop($kt),
                    $i);
        }

        return $result;
    }

    //-------------------------------------
    // 文字列を数列に変換
    public function text_to_sequence ($text, $size=null) {

        $rset =array_reverse(str_split($text));
        $size =is_null($size)
                ? count($rset)
                : $size;
        $sequence =array();

        for ($i=0; $i<$size; $i++) {

            $sequence[$i] =isset($rset[$i]) && isset($this->tablet_flip[$rset[$i]])
                    ? $this->tablet_flip[$rset[$i]]
                    : 0;
        }

        return $sequence;
    }

    //-------------------------------------
    // 数列を文字列に変換
    public function sequence_to_text ($sequence, $size=null) {

        $rset =$sequence;
        $size =is_null($size)
                ? count($rset)
                : $size;
        $text ="";

        for ($i=0; $i<$size; $i++) {

            $text .=isset($rset[$i]) && isset($this->tablet[$rset[$i]])
                    ? $this->tablet[$rset[$i]]
                    : $this->tablet[0];
        }

        return strrev($text);
    }

    //-------------------------------------
    // 数値を数列に変換
    public function number_to_sequence ($number, $max) {

        $sequence =array();

        $size =ceil($max/$this->tablet_size);
        $m =$number;

        for ($i=0; $i<$size; $i++) {

            $sequence[$i] =$m%$this->tablet_size;
            $m =floor($m/$this->tablet_size);
        }

        return $sequence;
    }

    //-------------------------------------
    // 数列を数値に変換
    public function sequence_to_number ($sequence) {

        $number =0;
        $factor =1;

        foreach (array_reverse($sequence) as $n) {

            $number +=$n*$factor;
            $factor *=$this->tablet_size;
        }

        return $number;
    }
}