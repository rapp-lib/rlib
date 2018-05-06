<?php
    $sheet->setHeader(array("url"=>"URL", "title"=>"タイトル"));
    foreach ($schema->getWebroots() as $webroot) {
        $webroot->getName();
        foreach ($webroot->getRoutes() as $route) {
            $sheet->addLine(array(
                "uri" => $route->getUri(),
                "title" => ($html=$route->getHtml()) ? $html->getTitle() : "",
            ));
        }
    }
