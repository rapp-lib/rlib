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
    public function mockApp()
    {
        return include(constant("R_APP_ROOT_DIR")."/config/app.php");
    }
    public function mockServe()
    {
        $response = $app->http->serve("www", function($request){
        });
    }
}
