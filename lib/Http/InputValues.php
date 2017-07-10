<?php
namespace R\Lib\Http;

class InputValues extends \ArrayObject
{
    const ATTRIBUTE_INDEX = "input_values";
    /**
     * ServerRequestから入力値を取り出して初期化
     */
    public function __construct ($server_request)
    {
        // Valuesの構築
        $values = array();
        self::mergeRecursive($values, (array)$server_request->getQueryParams());
        self::mergeRecursive($values, (array)$server_request->getParsedBody());
        self::mergeRecursive($values, (array)$server_request->getUri()->getEmbedParams());
        self::mergeRecursive($values, (array)$server_request->getUploadedFiles());
        self::sanitizeRecursive($values);
        $this->exchangeArray($values);
    }
    private static function sanitizeRecursive ( & $arr)
    {
        foreach ($arr as $key => $val) {
            if (is_array($val)) {
                self::sanitizeRecursive($arr[$key]);
            } elseif ( ! is_object($val)) {
                $arr[$key] = htmlspecialchars($val, ENT_QUOTES);
            }
        }
    }
    private static function mergeRecursive ( & $arr, $values)
    {
        foreach ($values as $k=>$v) {
            if (is_array($v)) {
                self::mergeRecursive($arr[$k], $v);
            } elseif ($v instanceof \Psr\Http\Message\UploadedFileInterface) {
                if ($v->getError() === UPLOAD_ERR_OK) {
                    $arr[$k] = $v;
                }
            } elseif (isset($v)) {
                $arr[$k] = $v;
            }
        }
    }
}
