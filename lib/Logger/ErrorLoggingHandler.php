<?php
namespace R\Lib\Logger;
use R\Lib\Core\Exception\ResponseException;
use Monolog\Logger;
use Monolog\Handler\AbstractProcessingHandler;

class ErrorLoggingHandler extends AbstractProcessingHandler
{
    protected function write(array $record)
    {
        if (isset($record["context"]["exception"]) && $record["context"]["exception"] instanceof ResponseException) {
            $response = $e->getResponse();
            $response->render();
            return;
        }
        if (app() && app()->hasProvider("response")) {
            $response = app()->response->error($record["response_message"], $record["response_code"]);
            if ($record["level"] === Logger::CRITICAL) {
                $response->render();
                return;
            } else {
                $response->raise();
            }
        }
        trigger_error("ErrorLoggingHandler caught an error with no response.");
    }
}
