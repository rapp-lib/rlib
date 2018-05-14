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
        // root_dirの決定
        $root_dir = constant("R_DEV_ROOT_DIR")."/tests";
        // test_bootstrap.phpの読み込み
        if (is_file($root_dir."/test_bootstrap.php")) {
            require_once $root_dir."/test_bootstrap.php";
        }
        // target_dirの決定
        $target_dir = $root_dir."/".(preg_match('!^[_\w]+$!', $params["dir"]) ?: "sandbox");
        // test_config.xmlの読み込み
        if (is_file($root_dir."/test_config.xml")) {
            $params["configuration"] = $root_dir."/test_config.xml";
        }
        // インスタンス作成
        $runner = new TestRunner();
        $printer = new ResultPrinter();
        $runner->setPrinter($printer);
        $suite = $runner->getTest($target_dir, "", ".php");
        // help表示
        if ( ! $params || $params["help"]) {
            $printer->write(" * available params:\n");
            $printer->write("   - dir = examples ... switch dir, default=sandbox\n");
            $printer->write("   - test = TestClass::testMethod ... spec test by name\n");
            $printer->write("   - group = test_group ... spec tests by group\n");
            $printer->write("   - all ... without spec\n");
            $printer->write("   - help ... show this message\n");
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
            if ($params["test"]) $params["filter"] = "^".$params["test"]."$";
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
