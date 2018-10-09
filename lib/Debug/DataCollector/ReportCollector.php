<?php
namespace R\Lib\Debug\DataCollector;
use DebugBar\DataCollector\MessagesCollector;
use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\Renderable;
use DebugBar\DataCollector\TimeDataCollector;

class ReportCollector extends MessagesCollector implements Renderable
{
    /**
     * 表示または記録するためのデータを用意する
     * 終端処理内で呼び出されるのでエラーを起こさないように注意
     */
    public function collect()
    {
        try {
            $data = array();
            $result = app("debug.logging_handler")->getRecordsByCategory();
            foreach ($result as $category=>$records) {
                $data[$category]["data"] = app("debug.logging_handler")->renderArray($records);
                $data[$category]["count"] = count($records);
            }
        } catch (\Exception $e) {
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
        foreach (app("debug.logging_handler")->getCategories() as $category) {
            $widgets[$category] = array(
                // 'icon' => $category,
                'widget' => 'PhpDebugBar.Widgets.ReportListWidget',
                'map' => 'report.'.$category.".data",
                'default' => '{}',
            );
            $widgets[$category.":badge"] = array(
                'map' => 'report.'.$category.".count",
                "default" => "null"
            );
        }
        return $widgets;
    }
}
