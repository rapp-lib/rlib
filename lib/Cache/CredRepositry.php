<?php
class CredRepositry
{
    public function __construct ()
    {
    }
    /**
     * Credentialの発行
     */
    public function create ($cred_data, $expire=null)
    {
        $data = array("cred_data" => $cred_data, "options" => $options);
        $cred = app()->security->hash(serialize($data));
        $this->getStorage()->setItem($cred, $data);
        report_info("[CredStorage] 登録しました", array(
            "cred" => $cred,
            "data" => $data,
        ));
        return $cred;
    }
    /**
     * Credentialの解決
     */
    public function read ($cred, $remove=false)
    {
        $data = $cred ? $this->getStorage()->getItem($cred) : null;
        if ( ! $data) {
            report_warning("[CredStorage] 登録がありません", array(
                "cred" => $cred,
                "cache_storage" => $this,
            ));
            return null;
        }
        if ($data["options"]["expire"] && $data["options"]["expire"] < time()) {
            report_warning("[CredStorage] 有効期限が切れています", array(
                "cred" => $cred,
                "expire" => date("Y/m/d/ H:i", $data["options"]["expire"]),
                "data" => $data,
            ));
            return null;
        }
        report_info("[CredStorage] 取得しました", array(
            "cred" => $cred,
            "cred_data" => $cred_data,
        ));
        return $data["cred_data"];
    }
    /**
     * Credentialの削除
     */
    public function drop ($cred)
    {
        report_info("[CredStorage] 削除しました", array(
            "cred" => $cred,
        ));
        $this->getStorage()->removeItem($cred);
    }

    private $storage = null;
    protected function getStorage ()
    {
        if ( ! $this->storage) $this->storage = app()->cache("cred");
        return $this->storage;
    }
}
