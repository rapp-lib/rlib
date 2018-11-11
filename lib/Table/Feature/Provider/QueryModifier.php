<?php
namespace R\Lib\Table\Feature\Provider;
use R\Lib\Table\Feature\BaseFeatureProvider;

class QueryModifier extends BaseFeatureProvider
{
// -- on_* 基本的な定義

    /**
     * ハッシュされたパスワードを関連づける
     */
    public function on_afterSelect_colHashPw($result, $col_name)
    {
        foreach ($result as $record) unset($record[$col_name]);
    }
    public function on_write_colHashPw($query, $col_name)
    {
        $value = $query->getValue($col_name);
        if (strlen($value)) $query->setValue($col_name, app()->security->passwordHash($value));
        else $query->removeValue($col_name);
    }

    /**
     * notnullでdefault値があるカラムにNULLを入れた際に値を補完する
     */
    public function on_write_colDefault_nullComplete($query, $col_name)
    {
        $value = $query->getValue($col_name);
        if ($value===null && $query->getDef()->getColAttr($col_name, "notnull")) {
            $query->setValue($col_name, $query->getDef()->getColAttr($col_name, "default"));
        }
    }

    public function on_update_toDelete_999($query)
    {
        if ($query->getDelete()) {
            $query->setDelete(false);
            $query->setType("delete");
        }
    }
    /**
     * 削除フラグを関連づける
     */
    public function on_read_colDelFlg_600($query, $col_name)
    {
        if ($query->getDelete() !== "hard") {
            $query->where($query->getTableName().".".$col_name, 0);
        }
    }
    public function on_update_colDelFlg($query, $col_name)
    {
        if ($query->getDelete() && $query->getDelete() !== "hard") {
            $query->removeDelete();
            $query->setValue($col_name, 1);
        }
    }

    /**
     * 登録日を関連づける
     */
    public function on_insert_colRegDate($query, $col_name)
    {
        $query->setValue($col_name, date("Y/m/d H:i:s"));
    }
    /**
     * 更新日を関連づける
     */
    public function on_write_colUpdateDate($query, $col_name)
    {
        $query->setValue($col_name, date("Y/m/d H:i:s"));
    }
    /**
     * ランダム文字列からIDを生成
     */
    public function on_insert_colGeneratorRandString_100($query, $col_name)
    {
        if ($query->getValue($col_name) !== null) return false;
        $length = $query->getDef()->getColAttr($col_name, "length") ?: 16;
        $chars = str_split('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');
        $value = "";
        for ($i=0; $i<$length; $i++) $value .= $chars[array_rand($chars)];
        $query->setValue($col_name, $value);
    }
    /**
     * @hook on_write
     * id_initの値からIDを生成
     */
    public function pre_on_write_colGenerator_100_idInit($query, $col_name)
    {
        return $query->getDef()->getColAttr($col_name, "generator")==="id_init";
    }
    public function on_write_colGenerator_100_idInit($query, $col_name)
    {
        $id_init_col_name = $query->getDef()->getColAttr($col_name, "id_init_col") ?: "id_init";
        $value = $query->getValue($id_init_col_name);
        $query->removeValue($id_init_col_name);
        if ($query->getType() !== "insert") return false;
        elseif ($query->getValue($col_name) !== null) return false;
        else $query->setValue($col_name, $value);
    }

    // -- on_* ストレージ型変換 write+200/read-200

    /**
     * JSON形式で保存するカラムの処理
     */
    public function on_write_colJsonFormat_700($query, $col_name)
    {
        if (is_array($value = $query->getValue($col_name))) {
            $query->setValue($col_name, json_encode($value, true));
        }
    }
    public function on_afterSelect_colJsonFormat_300($result, $col_name)
    {
        foreach ($result as $record) {
            if (strlen($value = $record[$col_name])) {
                $record[$col_name] = (array)json_decode($record[$col_name]);
            }
        }
    }

    /**
     * GEOMETRY型の入出力変換
     */
    public function on_write_colGeometryType_700($query, $col_name)
    {
        if ($value = $query->getValue($col_name)) {
            if (preg_match('!\d+(\.\d+)?\s*,\s*\d+(\.\d+)?!', $value, $match)) {
                $query->removeValue($col_name);
                $query->setValue($col_name."=", 'POINT('.$match[0].')');
            } else {
                $query->removeValue($col_name);
            }
        }
    }
    public function on_afterSelect_colGeometryType_300($result, $col_name)
    {
        foreach ($result as $record) {
            if ($record[$col_name]) {
                $unpacked = unpack('Lpadding/corder/Lgtype/dlatitude/dlongitude', $record[$col_name]);
                $record[$col_name] = $unpacked["latitude"]." , ".$unpacked["longitude"];
            }
        }
    }
}
