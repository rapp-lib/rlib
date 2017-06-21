<?php
namespace R\Lib\Core\Response;

use R\Lib\Core\Contract\Response;
use R\Lib\FileStorage\StoredFile;

class HttpResponse implements Response
{
    protected $output;
    public function __construct ($output)
    {
        $this->output = $output;
    }
    public function getOutput ()
    {
        return $this->output;
    }
    public function __report ()
    {
        $output = $this->output;
        if (isset($output["data"])) {
            $output["data"] = "...";
        }
        return array(
            "type" => $output["type"],
            "output" => $output,
            "vars" => $this->vars,
        );
    }
}