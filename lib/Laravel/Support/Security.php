<?php
namespace R\Lib\Laravel\Support;

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
        return \Hash::make($string);
    }
    /**
     * 乱数のハッシュ値の作成
     */
    public function getRandHash ()
    {
        error("deprecated");
        // return $this->hash(mt_rand());
    }
    /**
     * CSRFトークンの取得
     */
    public function getCsrfToken ()
    {
        return csrf_token();
    }
    /**
     * CSRFトークンパラメータ名の取得
     */
    public function getCsrfTokenName ()
    {
        return "_token";
    }
    /**
     * パスワード用ハッシュ値の検証
     */
    public function passwordHash ($strPassword)
    {
        return \Hash::make($strPassword);
    }
    /**
     * パスワード用ハッシュ値の検証
     */
    public function passwordVerify ($strPassword, $strHash)
    {
        return \Hash::check($strPassword, $strHash);
    }
}
