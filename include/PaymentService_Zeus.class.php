<?php
/*
    // 設定
    registry("Ecb.payment.zeus",array(
        // POST送信先URL（固定）
        "post_url" =>"https://linkpt.cardservice.co.jp/cgi-bin/secure.cgi",
        // 決済コード（固定）
        "send" =>"mall",
        // IPコード（支給）
        "clientip" =>"**********",
    ));

    // 仮売り上げ作成
    $result =obj("PaymentService_Zeus")->exec(array(
        "cardnumber" =>"8888888888888882",
        "expyy" =>"21",
        "expmm" =>"01",
        "money" =>"1000",
        "username" =>"123",
        "telno" =>"09042092178",
        "email" =>"toyosawa@sharingseed.co.jp",
        "sendid" =>"12345678",
    ));
*/

/**
*
*/
class PaymentService_Zeus {

    /**
     * ZEUS決済仮売上確定
     */
    public static function exec ($params=array()) {

        $config =registry("PaymentService.zeus");

        // Zeusクイックチャージ（前回利用したカード情報での決済）
        if ($params["quick"]) {

            $params["cardnumber"] ="8888888888888882";
            $params["expyy"] ="00";
            $params["expmm"] ="00";
            $params["telno"] ="0000000000";
        }

        $response =obj("HTTPRequestHandler")->request($config["post_url"], array(
            "adapter" =>"curl",
            "post" =>array(
                "clientip" =>$config["clientip"],
                "send" =>$config["send"],
                "cardnumber" =>$params["cardnumber"],
                "expyy" =>$params["expyy"],
                "expmm" =>$params["expmm"],
                "money" =>$params["money"],
                "username" =>$params["username"],
                "telno" =>preg_replace('![^\d]!', "", $params["telno"]),
                "email" =>$params["email"],
                "sendid" =>$params["sendid"],
            ),
        ));

        report("Zeus payment service exec",array(
            "response" =>$response
        ));

        return $response["body"] == "Success_order";
    }
}
