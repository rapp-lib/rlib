<?php
namespace R\Lib\I18n;

/**
 * 国際対応
 */
class I18nDriver
{
    protected $locale = "ja";
    public function getLocale ()
    {
        return $this->locale;
    }
    public function setLocale ($locale)
    {
        $this->locale = $locale;
    }

// -- ローカライズリソース

    public function getLocalizedClass ($class)
    {
        if (preg_match('!^(\\\?R\\\App\\\)(.+)$!', $class, $match)) {
            $localized_class = $match[1].'Locale\\'.str_camelize($this->locale).'\\'.$match[2];
            if (class_exists($localized_class)) return $localized_class;
        }
        return $class;
    }
    public function getLocalizedFile ($file)
    {
        $prefix_ptn = preg_quote(constant("R_APP_ROOT_DIR"),'!');
        if (preg_match('!^('.$prefix_ptn.')(.+)$!', $file, $match)) {
            $localized_file = $match[1].'/locale/'.$this->locale.'/'.$match[2];
            if (file_exists($localized_file)) return $localized_file;
        }
        return $file;
    }

// -- メッセージ

    protected $messages = array();
    /**
     * メッセージの取得
     */
    public function getMessage ($key, $values=array(), $locale=null)
    {
        $locale = $locale ?: $this->locale;
        if ( ! isset($this->messages[$locale])) {
            $locale_dir = app()->config("i18n.locale_dir") ?: constant("R_APP_ROOT_DIR")."/resources/locale"; 
            $data_file = $locale_dir."/".$locale."/message.php";
            $this->messages[$locale] = file_exists($data_file) ? include($data_file) : array();
        }
        $message = $this->messages[$locale][$key];
        if ( ! isset($message)) $message = $key;
        if (is_string($message)) {
            foreach ($values as $k=>$v) $message = str_replace(':'.$k, $v, $message);
        } elseif (is_callable($message)) {
            $message = call_user_func($message, $values);
        }
        return $message;
    }

// -- Enum値

    /**
     * Enumクラスの取得
     */
    public function getEnumClass ($class)
    {
        if (preg_match('!^(?:\\\?R\\\App\\\Enum\\\)(.+)$!', $class, $_)) {
            $alt_class_file = constant("R_APP_ROOT_DIR").'/resources/locale/'.$this->getLocale()."/Enum/".$_[1].".php";
            $alt_class = 'R\Locale\\'.str_camelize($this->getLocale()).'\Enum\\'.$_[1];
            if (class_exists($alt_class)) {
                return $alt_class;
            }
            if (file_exists($alt_class_file)) {
                require_once $alt_class_file;
                return $alt_class;
            }
        }
        return null;
    }
    protected $enum_values = array();
    /**
     * Enum値の取得
     * @deprecated
     */
    public function getEnumValue ($key, $value, $locale=null)
    {
        $locale = $locale ?: $this->locale;
        if ( ! isset($this->enum_values[$locale])) {
            $data_file = constant("R_APP_ROOT_DIR")."/locale/".$locale."/enum.php";
            $this->enum_values[$locale] = file_exists($data_file) ? include($data_file) : array();
        }
        if (isset($this->enum_values[$locale][$key])) $value = $this->enum_values[$locale][$key];
        elseif (is_string($value)) $value = $this->getMessage($value);
        return $value;
    }
}
