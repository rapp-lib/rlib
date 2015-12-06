<?php 

    function ecb ($name="default") {

        $providers =& ref_globals("loaded_ecb");

        if ( ! $providers[$name]) {

            $config =registry("ECB.connection.".$name);
            $providers[$name] =$config["make_ecb"]();
        }

        return $providers[$name];
    }
    
    function ecb_test () {
        
        registry("ECB.connection.default",array(
            "make_ecb" =>function() {
                $ecb =new EcbProvider_Tpro_Basic;
            	$ecb->obj("api")->set_config(array(
            		"tpro_id" =>83,
            		"tpro_api_url" =>"https://demo.ar-system.co.jp/TproXml",
            	));
                return $ecb;
            },
        ));
        ecb()->call("test.all");
    }
    