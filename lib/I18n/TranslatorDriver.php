<?php
use Zend\I18n\Translator\Translator;
$translator = new Translator();
$translator->addTranslationFile($type, $filename, $textDomain, $locale);
$translator->addTranslationFilePattern($type, $pattern, $textDomain);
$translator->translate($message, $textDomain, $locale);

//lang/ja/validate_error.php
//'fallback_locale' => 'ja',
app()->i18n->setLocale("ja");
app()->i18n->getLocale();
app()->i18n->message($name, (array)$vars);
app()->i18n->format($type, $value); //number/date/currency
__($name, (array)$vars);
"validate_error.require";
//"validate.errmsg.required" => ":itemは必須です"
//R\App\Enum\MemberEnum
//R\App\Lang\Jp\Enum\MemberEnum
