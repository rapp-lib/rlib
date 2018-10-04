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
        $events->listen('*', array($this, 'onWildcardEvent'));
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
}
