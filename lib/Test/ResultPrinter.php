<?php
namespace R\Lib\Test;
use PHPUnit_TextUI_ResultPrinter;
use PHPUnit_Util_TestDox_ResultPrinter_HTML;

class ResultPrinter extends PHPUnit_TextUI_ResultPrinter
{
    private $buffer = "";
    public function __construct($out=null)
    {
        if ($out) parent::__construct($out);
    }
    public function write($buffer)
    {
        $this->buffer .= $buffer;
    }
    public function read()
    {
        return $this->buffer;
        if ( ! $this->out) return null;
        fflush($this->out);
        return fread($this->out, 1024*1024);
    }
}
