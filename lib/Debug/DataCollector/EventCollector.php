<?php
namespace R\Lib\Debug\DataCollector;

use DebugBar\DataCollector\TimeDataCollector;
use Illuminate\Events\Dispatcher;
use Barryvdh\Debugbar\DataCollector\Util\ValueExporter;

class EventCollector extends TimeDataCollector
{
    protected $events = null;
    protected $exporter;

    public function __construct($requestStartTime = null)
    {
        parent::__construct($requestStartTime);
        $this->exporter = new ValueExporter();
    }
    public function subscribe(Dispatcher $events)
    {
        $this->events = $events;
        //$events->listen('*', array($this, 'onWildcardEvent'));
        $events->listen('*', array($this, 'onReportableEvent'));
    }

    public function onWildcardEvent()
    {
        $args = func_get_args();
        $name = $this->getCurrentEvent($args);
        $time = microtime(true);
        $this->addMeasure($name, $time, $time, $this->prepareParams($args) );
    }
    public function onReportableEvent()
    {
        $args = func_get_args();
        $name = $this->getCurrentEvent($args);
        if ($name==="http.dispatch_request") {
            list($request) = $args;
            report_info("Dispatch Request", array(
                "uri" => $request->getUri(),
                "input" => $request->getInputValues(),
            ), "App");
        } elseif ($name==="http.invoke_action") {
            list($uri, $vars, $forms) = $args;
            report_info("Invoke Action", array(
                "page_id" => $uri->getPageId(),
                "page_path" => $uri->getPagePath(),
                "vars" => $vars,
                "forms" => $forms,
            ), "App");
        } elseif ($name==="http.create_response") {
            list($type, $data, $params) = $args;
            if ($type=="html") $data = "...";
            report_info("Create Response", array(
                "type" => $type,
                "data" => $data,
                "params" => $params,
            ), "App");
        } elseif ($name==="http.emit_response") {
            list($response) = $args;
            report_info("Emit Response", array(
                "response" => $response,
            ), "App");
        } elseif ($name==="table.executed") {
            list($statement, $result_res, $start_ms) = $args;
            $elapsed_ms = round((microtime(true) - $start_ms)*1000, 2);
            if (app()->config["app.debug"]) {
                if ($result_res) {
                    list($warn, $info) = $this->analyzeExceutedStatement($statement, $elapsed_ms);
                }
                report_info('SQL Exec : '.$statement, array(
                    "Query"=>$statement->getQuery(),
                    "Info"=>$info,
                ), "SQL");
                if ($warn) {
                    report_warning("SQL Warn : ".implode(' , ',$warn), array(
                        "Statement"=>"".$statement,
                    ), "SQL");
                }
            }
        } elseif ($name==="table.fetch_end") {
            list($table, $statement, $result) = $args;
            report_info("Fetch End ".$table->getAppTableName()."[".count($result)."] : ".$statement, array(
                "result"=>$result,
            ), "T_Fetch");
        } elseif ($name==="table.merge_alias") {
            list($table, $statement, $result, $src_col_name, $alias_col_name, $dest_values) = $args;
            report_info("Merge Alias ".$table->getAppTableName().".".$alias_col_name."[".count($dest_values)."] : ".$statement, array(
                "alias_col_name"=>$alias_col_name,
                "src_col_name"=>$src_col_name,
                "dest_values"=>$dest_values,
            ), "T_Alias");
        } elseif ($name==="table.hook") {
            list($table, $statement, $result, $method_name, $method_args) = $args;
            if ($method_name != "on_getBlankCol_alias") {
                $method_args_short = array();
                foreach ($method_args as $arg) {
                    if (is_string($arg)) $method_args_short[] = '"'.$arg.'"';
                    elseif (is_object($arg)) $method_args_short[] = get_class($arg);
                    else $method_args_short[] = gettype($arg);
                }
                report_info("Hook ".$table->getAppTableName().".".$method_name."(".implode(' , ', $method_args_short).") : ".$statement, array(
                    "method_name"=>$method_name,
                    "method_args"=>$method_args,
                ), "T_Hook");
            }
        } elseif ($name==='mailer.sending') {
            list($message) = $args;
            report_info("Mail sending", $this->parseMailMessage($message), "Mail");
        } elseif ($name==="app.handle_exception") {
            list($exception) = $args;
            app("debugbar")->addException($exception);
        }
    }

    protected function getCurrentEvent($args)
    {
        if(method_exists($this->events, 'firing')){
            $event = $this->events->firing();
        }else{
            $event = end($args);
        }
        return $event;
    }

    protected function prepareParams($params)
    {
        $data = array();
        // foreach ($params as $key => $value) {
        //     $data[$key] = htmlentities($this->exporter->exportValue($value), ENT_QUOTES, 'UTF-8', false);
        // }
        return $data;
    }

    public function collect()
    {
        // $data = parent::collect();
        // $data['nb_measures'] = count($data['measures']);
        return $data;
    }

    public function getName()
    {
        return 'event';
    }

    public function getWidgets()
    {
        return array(
            // "Events" => array(
            //     "icon" => "tasks",
            //     "widget" => "PhpDebugBar.Widgets.TimelineWidget",
            //     "map" => "event",
            //     "default" => "{}"
            // ),
            // 'events:badge' => array(
            //     'map' => 'event.nb_measures',
            //     'default' => 0
            // )
        );
    }

    /**
     * 送信メールのMessage情報の解析
     *
     * @param SwSwift_Message $message
     * @return array
     */
    private function parseMailMessage($message)
    {
        // 宛先の加工
        $to = $message->getTo();
        if (is_array($to)) {
            $tos = array();
            foreach ($to as $k => $v) $tos[] = (empty($v) ? '' : "$v ") . "<$k>";
            $to =  implode(', ', $tos);
        }
        $headers = $message->getHeaders();
        $bodies = array();
        if ($body = $message->getBody()) {
            $content_type = $message->getContentType();
            $body = (preg_match('!^text/!', $content_type)) ? explode("\n", $body) : count($body)."bytes";
            $bodies[] = array(
                "content_type"=>$content_type,
                "data"=>$body,
            );
        }
        foreach ((array)$message->getChildren() as $child) {
            if ($body = $child->getBody()) {
                $content_type = $child->getContentType();
                $body = (preg_match('!^text/!', $content_type)) ? explode("\n", $body) : count($body)."bytes";
                $bodies[] = array(
                    "content_type"=>$content_type,
                    "data"=>$body,
                );
            }
        }
        return array(
            'to' => $to,
            'subject' => $message->getSubject(),
            'body' => $bodies,
            'header' => explode("\n", "".$headers),
        );
    }
    /**
     * SQL発行結果の解析
     */
    private function analyzeExceutedStatement($statement, $elapsed_ms)
    {
        try {
            $db = $statement->getQuery()->getDef()->getConnection();
            if ($statement->getQuery()->getType()==="select") {
                if ($db->getConfig("driver")==="pdo_mysql") {
                    $explain = $db->fetch($db->exec("EXPLAIN ".$statement));
                    list($warn, $info) = $this->analyzeMysqlExplain($explain);
                }
            }
        } catch (\PDOException $e) {
            $warn[] = "EXPLAIN Failed : ".$e->getMessage();
        }
        if ($elapsed_ms && $elapsed_ms>1000) {
            $warn[] = "Slow SQL tooks ".$elapsed_ms."ms";
        }
        return array($warn, $info);
    }
    /**
     * MySQL Explainの解析
     */
    private function analyzeMysqlExplain($explain)
    {
        $warn = $info = array();
        foreach ($explain as $t) {
            // 1行EXPLAINの構築
            $info["short_explain"]["#".$t["id"]] = $t["table"]." ".$t["type"]."/".$t["select_type"]." ".$t["Extra"];
            // テーブル規模の決定
            $target_scale = "midium";
            $table_name = app("table.def_resolver")->getTableNameByDefTableName($t["table"]);
            if ($table_name) {
                $target_scale = app()->tables[$table_name]->getDefAttr("target_scale");
            }
            if (is_numeric($target_scale)) {
                $target_scale = "xlarge";
                $scales = array("small"=>100, "midium"=>10000, "large"=>100000);
                foreach ($scales as $k=>$v) if ($target_scale<=$v) $target_scale = $k;
            }
            // EXPLAINからパラメータを抽出する
            $t["Extra"] = array_map("trim",explode(';',$t["Extra"]));
            $is_seq_scan = $t["type"] == "ALL" || $t["type"] == "index";
            $is_no_possible_keys = ! $t["possible_keys"] && ! $t["key"];
            $is_dep_sq = $t["select_type"] == "DEPENDENT SUBQUERY";
            $is_seq_join = in_array("Using join buffer", $t["Extra"]);
            $is_using_where = in_array("Using where", $t["Extra"]);
            // テーブル規模対パラメータから警告を構成する
            $msg = "";
            if ($target_scale=="small") {
            } elseif ($target_scale=="midium") {
                if ($is_using_where && $is_seq_scan) {
                    if ($is_no_possible_keys) {
                        $msg = "INDEXが設定されていないWHERE句";
                    }
                }
            } else {
                if ($is_seq_scan && $is_using_where) {
                    if ($is_dep_sq) $msg = "INDEXが適用されない相関サブクエリ";
                    elseif ($is_seq_join) $msg = "INDEXが適用されないJOIN";
                    else $msg = "全件走査になるWHERE句";
                }
            }
            if ($msg) $warn[] = $msg." on ".$t["table"];
        }
        return array($warn, $info);
    }
}
