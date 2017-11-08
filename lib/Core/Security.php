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
        return md5($string);
    }
    /**
     * CSRFトークンの取得
     */
    public function getCsrfToken ()
    {
        return self::hash(app()->session->getId());
    }
    /**
     * CSRFトークンパラメータ名の取得
     */
    public function getCsrfTokenName ()
    {
        return "_csrf_token";
    }
}
