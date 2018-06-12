<?php
namespace R\Lib\Debug\DataCollector;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use DebugBar\DataCollector\TimeDataCollector;

class ReportCollector extends MessagesCollector implements Renderable
{
    protected $levels = array("debug", "info", "warning", "error");
    /**
     * 表示または記録するためのデータを用意する
     * 終端処理内で呼び出されるのでエラーを起こさないように注意
     */
    public function collect()
    {
        try {
            $data = array();
            $result = app("debug.logging_handler")->getRecordsByLevels();
            foreach ($this->levels as $level) {
                $records = (array)$result[$level];
                $data[$level]["html"] = app("debug.logging_handler")->renderHtml($records);
                $data[$level]["count"] = count($records);
            }
        } catch (\Exception $e) {var_dump($e);
            $data = null;
        }
        return $data;
    }
    /**
     * @{inheritDoc}
     */
    public function getName()
    {
        return 'report';
    }
    /**
     * @{inheritDoc}
     */
    public function getWidgets()
    {
        $widgets = array();
        foreach ($this->levels as $level) {
            $widgets[$level] = array(
                'icon' => $level,
                'widget' => 'PhpDebugBar.Widgets.FreeHtmlWidget',
                'map' => 'report.'.$level.".html",
                'default' => '{}',
            );
            $widgets[$level.":badge"] = array(
                'map' => 'report.'.$level.".count",
                "default" => "null"
            );
        }
        return $widgets;
    }
}
