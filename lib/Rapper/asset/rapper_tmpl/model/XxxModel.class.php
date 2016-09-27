<@?php

/**
 * Model: <?=$t["label"]?>
 */
class <?=str_camelize($t["name"])?>Model extends Model_App
{
    /**
     * ID指定で1件取得
     */
    public function get_by_id ($id)
    {
        $query =array(
            "table" =>"<?=$t["name"]?>",
            "conditions" =>array(
                "<?=$t['pkey']?>" =>$id,
<? if ($t['del_flg']): ?>
                "<?=$t['del_flg']?>" =>"0",
<? endif; ?>
            ),
        );
        $t =$this->select_one($query);

        return $t;
    }

    /**
     * 検索フォームの結果取得
     */
    public function get_by_search_form ($list_setting, $input, $is_forcsv=false)
    {
        // 条件を指定して要素を取得
        $query =$this->get_list_query($list_setting, $input);
        $query =$this->merge_query($query, array(
            "table" =>"<?=$t["name"]?>",
            "conditions" =>array(
<? if ($t['del_flg']): ?>
                "<?=$t['del_flg']?>" =>"0",
<? endif; ?>
            ),
        ));

        // CSV向けに取得
        if ($is_forcsv) {

            unset($query["offset"]);
            unset($query["limit"]);
            return $this->select_nofetch($query);
        }

        $ts =$this->select($query);
        $p =$this->select_pager($query);

        return array($ts,$p);
    }

    /**
     * フォームからのデータ更新/新規登録
     */
    public function save ($fields, $id=null) {

        // IDの指定があれば更新
        if ($id) {

<? if ($t['update_date']): ?>
            $fields["<?=$t['update_date']?>"] =date('Y/m/d H:i:s');

<? endif; ?>
            $query =array(
                "fields" =>$fields,
                "table" =>"<?=$t["name"]?>",
                "conditions" =>array(
                    "<?=$t['pkey']?>" =>$id,
<? if ($t['del_flg']): ?>
                    "<?=$t['del_flg']?>" =>"0",
<? endif; ?>
                ),
            );
            $this->update($query,$id);

        // IDの指定がなければ新規登録
        } else {

<? if ($t['reg_date']): ?>
            $fields["<?=$t['reg_date']?>"] =date('Y/m/d H:i:s');

<? endif; ?>
<? if ($t['update_date']): ?>
            $fields["<?=$t['update_date']?>"] =date('Y/m/d H:i:s');

<? endif; ?>
<? if ($t['del_flg']): ?>
            $fields["<?=$t['del_flg']?>"] ="0";

<? endif; ?>
            $query =array(
                "fields" =>$fields,
                "table" =>"<?=$t["name"]?>",
            );
            $id =$this->insert($query);
        }

        return $id;
    }

    /**
     * 削除
     */
    public function drop ($id) {

<? if ($t['del_flg']): ?>
        // 要素の削除フラグをon
        $query =array(
            "table" =>"<?=$t['name']?>",
            "fields" =>array(
                "<?=$t['del_flg']?>" =>"1",
<? if ($t['update_date']): ?>
                "<?=$t['update_date']?>" =>date('Y/m/d H:i:s'),
<? endif; ?>
            ),
            "conditions" =>array(
                "<?=$t['pkey']?>" =>$id,
                "<?=$t['del_flg']?>" =>"0",
            ),
        );
        $this->update($query,$id);
<? else: ?>
        // 要素の削除
        $query =array(
            "table" =>"<?=$t["name"]?>",
            "conditions" =>array(
                "<?=$t['pkey']?>" =>$id,
            ),
        );
        $this->delete($query,$id);
<? endif; ?>
    }
}
