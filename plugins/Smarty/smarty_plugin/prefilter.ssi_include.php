<?php

    function smarty_prefilter_ssi_include ($src, $smarty){
        
        // ssi virtual=ではなく、include file=として展開する
        $src =preg_replace_callback('!<\!--#include\s+virtual="([^"]+)"\s*-->!',function ($match) use ($smarty){
            $file =registry("Path.document_root_dir")."/".$match[1];
            return $smarty->left_delimiter.'include file="'.$file.'"'.$smarty->right_delimiter;
        },$src);
        
        $src =preg_replace_callback('!<\!--#include\s+file="([^"]+)"\s*-->!',function ($match) use ($smarty){
            $file =$match[1];
            return $smarty->left_delimiter.'include file="'.$file.'"'.$smarty->right_delimiter;
        },$src);
        
        return $src;
    }
    