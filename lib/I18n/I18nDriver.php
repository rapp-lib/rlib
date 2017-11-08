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
        $prefix_ptn = preg_quote('\?R\App\\','!');
        if (preg_match('!^('.$prefix_ptn.')(.+)$!', $class, $match)) {
            $localized_class = $match[1].'Locale\\'.str_camelize($this->locale).'\\'.$match[2];
            if (class_exists($localized_class)) return $localized_class;
        }
        return $class;
    }
    public function getLocalizedFile ($file)
    {
        $prefix_ptn = preg_quote(constant("R_APP_ROOT_DIR"),'/!');
        if (preg_match('!^('.$prefix_ptn.')(.+)$!', $file, $match)) {
            $localized_file = $match[1].'locale/'.$this->locale.'/'.$match[2];
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
            $data_file = constant("R_APP_ROOT_DIR")."/locale/".$locale."/message.php";
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
}
