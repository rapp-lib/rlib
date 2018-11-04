<?php
namespace R\Lib\Table\Plugin;

class StdAuthProvider extends BasePluginProvider
{
// -- 基本的な認証処理の定義

    /**
     * ログイン処理の実装
     */
    public function authByLoginIdPw ($login_id, $login_pw)
    {
        $login_id_col_name = $this->getColNameByAttr("login_id");
        $login_pw_col_name = $this->getColNameByAttr("login_pw");
        if ( ! $login_id_col_name || ! $login_pw_col_name) {
            report_error("login_id,login_pwカラムがありません",array(
                "table" => $this,
            ));
        }
        $this->query->where($this->getQueryTableName().".".$login_id_col_name, (string)$login_id);
        if (static::$cols[$login_pw_col_name]["hash_pw"]) {
            $this->with($this->getQueryTableName().".".$login_pw_col_name, $login_pw_col_name."_hash");
        }
        $record = $this->selectOne();
        if (static::$cols[$login_pw_col_name]["hash_pw"]) {
            if ( ! app()->security->passwordVerify($login_pw, $record[$login_pw_col_name."_hash"])) return null;
         } else {
             if ($login_pw != $record[$login_pw_col_name]) return false;
         }
         return $record;
    }
    /**
     * @hook chain
     * @deprecated authByLoginIdPw passwordVerifyへの整合のため
     * ログインID/PWを条件に指定する
     */
    public function chain_findByLoginIdPw ($query, $login_id, $login_pw)
    {
        $login_id_col_name = $this->getColNameByAttr("login_id");
        $login_pw_col_name = $this->getColNameByAttr("login_pw");
        if ( ! $login_id_col_name || ! $login_pw_col_name) {
            report_error("login_id,login_pwカラムがありません",array(
                "table" => $this,
            ));
        }
        if (static::$cols[$login_pw_col_name]["hash_pw"]) {
            $login_pw = app()->security->passwordHash($login_pw);
        }
        $query->where($this->getQueryTableName().".".$login_id_col_name, (string)$login_id);
        $query->where($this->getQueryTableName().".".$login_pw_col_name, (string)$login_pw);
    }
    /**
     * @hook chain
     * 現在のRoleのTableに対して所有関係があることを条件として指定する
     */
    public function chain_findMine ($query)
    {
        $role = app()->user->getCurrentRole();
        $result = app()->user->onFindMine($role, $this);
        if ( ! $result) $result = self::defaultOnFindMine($role, $this);
        if ( ! $result) {
            $this->findNothing();
        }
    }
    /**
     * 現在のRoleのTableに対して所有関係があることを前提にsaveを実行する
     */
    public function saveMine ()
    {
        $role = app()->user->getCurrentRole();
        $result = app()->user->onSaveMine($role, $this);
        if ( ! $result) $result = self::defaultOnSaveMine($role, $this);
        if ( ! $result) {
            report_error("無効なsaveMine", array("role_tabel"=>$role, "table"=>$this));
        }
        return $this->save();
    }
    protected static function defaultOnFindMine ($role, $table)
    {
        $user_id = app()->user->id($role);
        // ログイン中でなければ何も取得しない
        if ( ! $user_id) return false;
        $role_table_name = app()->user->getAuthTable($role);
        if ( ! $role_table_name) return false;
        // 自己参照の特定
        if ($table->getAppTableName() == $role_table_name) {
            // ログイン中のID = 主キーを条件に追加する
            $table->query->where($table->getQueryTableName().".".$table->getIdColName(), $user_id);
        // 関係先を経由して条件を指定
        } elseif ($table->findByRoute($role_table_name, $user_id)) {
            //
        } else {
            report_warning("無効なfindMine, 所有関係を示す経路がありません",
                array("role_tabel"=>$role_table_name, "table"=>$table));
            return false;
        }
        return true;
    }
    protected static function defaultOnSaveMine ($role, $table)
    {
        $user_id = app()->user->id($role);
        $role_table_name = app()->user->getAuthTable($role);
        $id_col_name = $table->getIdColName();
        $fkey_col_name = $table->getColNameByAttr("fkey_for", $role_table_name);
        if ( ! $role_table_name) return false;
        if ( ! $user_id) {
            report_warning("非ログイン中のsaveMineの呼び出しは不正です", array("table"=>$table));
            return false;
        }
        // Roleのテーブル自身である場合は、主キーを指定
        if ($role_table_name == $table->getAppTableName()) {
            $table->query->setValue($id_col_name, $user_id);
        // 関係がある場合
        } elseif (app("table.resolver")->getFkeyRoute($table->getAppTableName(), $role_table_name)) {
            // 直接関係があればValueを上書き
            if ($fkey_col_name) $table->query->setValue($fkey_col_name, $user_id);
            // Updateが発行される場合、関係先を探索して条件に追加
            if ($table->query->getValue($id_col_name)) {
                $table->findByRoute($role_table_name, $user_id);
            // Insertであり、直接関係がない場合エラー
            } elseif ( ! $fkey_col_name) {
                report_warning("無効なsaveMine, 直接関係がなければ新規作成を行う条件の指定は出来ません",
                    array("role_tabel"=>$role_table_name, "table"=>$table));
                return false;
            }
        } else {
            report_warning("無効なsaveMine, 所有関係を示す経路がありません",
                array("role_tabel"=>$role_table_name, "table"=>$table));
            return false;
        }
        return true;
    }
}
