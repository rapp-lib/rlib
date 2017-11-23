<?php
namespace R\Lib\Session;

class FlashMessageQueue
{
    public function __call ($func, $args)
    {
        $type = $func;
        $message = array_shift($args);
        $options = array_shift($args) ?: array();
        if (preg_match('!^(.+)Now$!', $type, $_)) {
            $type = $_[1];
            $options["now"] = true;
        }
        $options["type"] = $type;
        return $this->add($message, $options);
    }

    /**
     * リクエスト内表示用のStack
     */
    protected $now_messages = array();

    public function add ($message, $options=array())
    {
        $options["type"] = $options["type"] ?: "notice";
        $data = array("message"=>$message, "options"=>$options);;
        if ($options["now"]) {
            $this->now_messages[] = $data;
        } else {
            $stack = & app()->session("FlashMessageQueue")->stack;
            if ( ! $stack) $stack = array();
            $stack[] = $data;
        }
    }
    public function get ($options=array())
    {
        $flash = array();
        $stack = & app()->session("FlashMessageQueue")->stack;
        foreach ((array)$stack as $i=>$data) {
            $flash[] = $data;
            unset($stack[$i]);
        }
        foreach ((array)$this->now_messages as $i=>$data) {
            $flash[] = $data;
            unset($this->now_messages[$i]);
        }
        return $flash;
    }
}
