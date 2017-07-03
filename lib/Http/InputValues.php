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
        $values = array_merge(
            (array)$server_request->getQueryParams(),
            (array)$server_request->getParsedBody(),
            (array)$server_request->getUri()->getEmbedParams()
        );
        self::sanitizeRecursive($values);
        $this->exchangeArray($values);
    }
    private static function sanitizeRecursive ( & $arr)
    {
        foreach ($arr as $key => $val) {
            if (is_array($val)) {
                self::sanitizeRecursive($arr[$key]);
            } else {
                $arr[$key] = htmlspecialchars($val, ENT_QUOTES);
            }
        }
    }
}
