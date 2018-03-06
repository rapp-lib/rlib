<?php
namespace R\Lib\Core;

/**
 * セキュリティ関連実装
 */
class Security
{
    /**
     * 一方向ハッシュ値の作成
     */
    public function hash ($string)
    {
        $string = md5($string);
        foreach ((array)app()->config("security.hash_salts") as $salt) $string = md5($string.$salt);
        return $string;
    }
    /**
     * 乱数のハッシュ値の作成
     */
    public function getRandHash ()
    {
        return $this->hash(mt_rand());
    }
    /**
     * CSRFトークンの取得
     */
    public function getCsrfToken ()
    {
        return $this->hash(app()->session->getId());
    }
    /**
     * CSRFトークンパラメータ名の取得
     */
    public function getCsrfTokenName ()
    {
        return "_csrf_token";
    }
    /**
     * パスワード用ハッシュ値の検証
     */
    public function passwordHash ($strPassword, $numAlgo = 1, $arrOptions = array())
    {
        if (app()->config("security.old_password_hash")) return $this->hash($strPassword);
        // php >= 5.5
        if (function_exists('password_hash')) {
            $hash = password_hash($strPassword, $numAlgo, $arrOptions);
        } else {
            if (function_exists("mcrypt_create_iv")) {
                $salt = mcrypt_create_iv(22, MCRYPT_DEV_URANDOM);
            } else {
                $salt = md5(mt_rand());
            }
            $salt = base64_encode($salt);
            $salt = str_replace('+', '.', $salt);
            $hash = crypt($strPassword, '$2y$10$' . $salt . '$');
        }
        return $hash;
    }
    /**
     * パスワード用ハッシュ値の検証
     */
    public function passwordVerify ($strPassword, $strHash)
    {
        if (app()->config("security.old_password_hash")) return $this->hash($strPassword) == $strHash;
        // php >= 5.5
        if (function_exists('password_verify')) {
            $boolReturn = password_verify($strPassword, $strHash);
        } else {
            $strHash2 = crypt($strPassword, $strHash);
            $boolReturn = $strHash == $strHash2;
        }
        return $boolReturn;
    }
}
