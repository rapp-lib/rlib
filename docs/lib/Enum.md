R\Lib\Enum
=====================================

Classes
-------------------------------------
    EnumFactory
    Enum_Base

Enumクラスのサンプル
-------------------------------------
~~~
class MemberEnum extends Enum_Base
{
    // (例) $enum = enum("Member.pref"); $enum[13]; => "東京都"
    protected static $values_pref = array(
        1 => "北海道",
        // ...
        13 => "東京都",
    );
    // (例) $enum = enum("Member.city",13); $enum[130001]; => "千代田区"
    protected static $layered_values_city = array(
        1 => array(
            10001 => "札幌市",
            // ...
        ),
        // ...
        13 => array(
            130001 => "千代田区",
            130002 => "港区",
            // ...
        ),
    );
    // (例) $enum = enum("Member.type"); $enum[1]; => "Aランク会員"
    // values_*、layered_values_*ともにプロパティよりもメソッド定義が優先される
    protected static function values_type ($offset)
    {
        return table("MemberType")->selectHash("type");
    }
    // (例) $enum = enum("Member.name"); $enum[1]; => "山田 太郎"
    // ※ただし、values_メソッド/プロパティの定義がなければforeachでは値が設定されない
    protected static function value_name ($offset)
    {
        $record = table("Member")->selectById($offset);
        return $record["name"];
    }
}
~~~