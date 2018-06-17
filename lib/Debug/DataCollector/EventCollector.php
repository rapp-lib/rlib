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
            ), "Http");
        } elseif ($name==="http.invoke_action") {
            list($uri, $vars, $forms) = $args;
            report_info("Invoke Action", array(
                "page_id" => $uri->getPageId(),
                "page_path" => $uri->getPagePath(),
                "vars" => $vars,
                "forms" => $forms,
            ), "Http");
        } elseif ($name==="http.create_response") {
            list($type, $data, $params) = $args;
            if ($type=="html") $data = "...";
            report_info("Create Response", array(
                "type" => $type,
                "data" => $data,
                "params" => $params,
            ), "Http");
        } elseif ($name==="http.emit_response") {
            list($response) = $args;
            report_info("Emit Response", array(
                "response" => $response,
            ), "Http");
        } elseif ($name==="sql.fetch_end") {
            list($table, $statement, $result) = $args;
            report_info("Fetch End ".$table->getAppTableName()."[".count($result)."] :".$statement, array(
                "result"=>$result,
            ), "Fetch");
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
        foreach ($params as $key => $value) {
            $data[$key] = htmlentities($this->exporter->exportValue($value), ENT_QUOTES, 'UTF-8', false);
        }
        return $data;
    }

    public function collect()
    {
        $data = parent::collect();
        $data['nb_measures'] = count($data['measures']);
        return $data;
    }

    public function getName()
    {
        return 'event';
    }

    public function getWidgets()
    {
        return array(
            "Events" => array(
                "icon" => "tasks",
                "widget" => "PhpDebugBar.Widgets.TimelineWidget",
                "map" => "event",
                "default" => "{}"
            ),
            // 'events:badge' => array(
            //     'map' => 'event.nb_measures',
            //     'default' => 0
            // )
        );
    }
}
