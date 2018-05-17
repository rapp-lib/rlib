<?php
namespace R\Lib\Test;
use PHPUnit_Framework_TestCase;
use PHPUnit_Framework_TestResult;

class Test_Base extends PHPUnit_Framework_TestCase
{
    public function run(PHPUnit_Framework_TestResult $result = null)
    {
        report_info("TEST ".get_class($this)."::".$this->getName());
        return parent::run($result);
    }
    public function outputDir($dir_append="")
    {
        $dir = constant("R_APP_ROOT_DIR")."/tmp/tests/".str_replace('\\','_',get_class($this));
        if ($dir_append) $dir .= "/".$dir_append;
        \R\Lib\Util\File::createDir($dir);
        return $dir;
    }
}
