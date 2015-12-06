<?php

class EcbError extends Exception {

    public function __construct ($msg, $data) {

        $this->message =$msg;
        report_warning("[EcbError] ".$msg,$data);
        
        // XML解析、通信エラーなどECバックエンド都合のエラー
        // →エラーページを表示する
        
        // 購入導線系（非決済）のエラー
        // →状況をカートまで戻す
        
        // 決済系のエラー
        // →可能な限り復旧させる
    }
}