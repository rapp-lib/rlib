<?php
namespace R\Lib\Test;
use PHPUnit_TextUI_TestRunner;
use PHPUnit_Framework_Exception;

class TestDriver
{
    /**
     * DebugDriverからのIntercept起動を行う
     */
    public function runIntercept()
    {
        $result = $this->run($_REQUEST);
        print '<div style="background-color:#000; color:white; padding:20px; margin:0px;">';
        print nl2br(str_replace(' ','&nbsp;',htmlspecialchars($result["output"])));
        print "</div>";
        exit;
    }
    /**
     * テスト実行
     */
    public function run($params)
    {
        // test_bootstrap.phpの読み込み
        $tests_dir = constant("R_APP_ROOT_DIR")."/devel/tests";
        require_once $tests_dir."/test_bootstrap.php";
        // root_dirの決定
        if ( ! $params["dir"]) $params["dir"] = "sandbox";
        if ($params["dir"]==="examples") $root_dir = constant("R_DEV_ROOT_DIR")."/examples";
        elseif ($params["dir"]==="tests") $root_dir = $tests_dir;
        elseif ($params["dir"]==="sandbox")  $root_dir = constant("R_APP_ROOT_DIR")."/devel/sandbox";
        // インスタンス作成
        $runner = new TestRunner();
        $printer = new ResultPrinter();
        $runner->setPrinter($printer);
        $suite = $runner->getTest($root_dir, "", ".php");
        // help表示
        if ( ! $params["test"] && ! $params["group"]) {
            $printer->write(" * available params:\n");
            $printer->write("   - dir = sandbox | examples | tests ... switch dir\n");
            $printer->write("   - test = TestClass::testMethod | all ... spec test by name\n");
            $printer->write("   - group = test_group ... spec tests by group\n");
            $printer->write("\n");
            $printer->write(" * available tests:\n");
            foreach ($suite as $test_case) {
                foreach ($test_case->tests() as $test) {
                    $printer->write("   - ".$test_case->getName()."::".$test->getName()."\n");
                }
            }
            $printer->write("\n");
            $printer->write(" * available groups:\n");
            foreach ($suite->getGroups() as $group) {
                $printer->write("   - @group ".$group."\n");
            }
            $printer->write("\n");
        // テスト実行
        } else {
            // Filterの登録
            if ($params["test"] && $params["test"]!="all") $params["filter"] = "^".$params["test"]."$";
            elseif ($params["group"]) $params["groups"][] = $params["group"];
            // 基本設定の上書き
            if ( ! defined("PHPUNIT_TESTSUITE")) define("PHPUNIT_TESTSUITE",true);
            $params["backupGlobals"] = false;
            try {
                $result = $runner->doRun($suite, $params);
            } catch (PHPUnit_Framework_Exception $e) {
                $printer->write($e->getMessage()."\n");
            }
        }
        return array(
            "result"=>isset($result) && $result->wasSuccessful(),
            "output"=>$printer->read(),
        );
    }
}
