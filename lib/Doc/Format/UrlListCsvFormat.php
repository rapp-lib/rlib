<?php
namespace R\Lib\Doc\Format;
use R\Lib\Doc\Content\DirTree;

class UrlListCsvFormat extends Format_Base
{
    public function getContents()
    {
        $contents = array();
        foreach ($this->getSchema()->getWebroots() as $webroot) {
            $contents[$webroot->getName().".url_list.csv"] = $content = new DirTree();
            $content->setHeader(array("title"));
            foreach ($webroot->getRoutes() as $route) {
                $content->addPath($route->getUri()->withoutAuthority(), array(
                    "title" => ($html=$route->getHtml()) ? $html->getTitle() : "",
                ));
            }
        }
        return $contents;
    }
}
