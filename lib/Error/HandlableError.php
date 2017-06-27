<?php
namespace R\Lib\Error;

class HandlableError extends \Exception
{
    protected $params;
    protected $error_options;
    protected $backtraces;
    public function __construct ($message, $params=array(), $error_options=array())
    {
        parent::__construct($message);
        $this->params = $params;
        $this->error_options = $error_options;
    }
    public function getParams ()
    {
        return $this->params;
    }
    public function getErrorOptions ()
    {
        return $this->options;
    }
}
