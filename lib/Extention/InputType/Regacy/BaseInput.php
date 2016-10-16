<?php
namespace R\Lib\Extention\InputType\Regacy;

/**
 *
 */
abstract class BaseInput
{

    /**
     * [$html description]
     * @var [type]
     */
    protected $html;

    /**
     * [$assign description]
     * @var [type]
     */
    protected $assign;

    /**
     * [__construct description]
     * @param [type] $value  [description]
     * @param [type] $params [description]
     */
    public function __construct ($value, $params)
    {
        $this->html ="";
        $this->assign =array();
    }

    /**
     * [getHtml description]
     * @return [type] [description]
     */
    public function getHtml ()
    {
        return $this->html;
    }

    /**
     * [getAssign description]
     * @return [type] [description]
     */
    public function getAssign ()
    {
        return $this->assign;
    }

    /**
     * [filterAttrs description]
     * @param  [type] $attrs [description]
     * @param  [type] $keys  [description]
     * @return [type]        [description]
     */
    protected function filterAttrs ($attrs, $keys)
    {
        $params =array();

        foreach ($keys as $key) {

            if (isset($attrs[$key])) {

                $params[$key] =$attrs[$key];
                unset($attrs[$key]);
            }
        }

        return array($params,$attrs);
    }
}