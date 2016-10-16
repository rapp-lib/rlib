<?php

class Schema
{
    public $attrs;

    public function __construct ($attrs)
    {
        $this->attrs = $attrs;
    }

    public function __get ($name)
    {
        return $this->attr[$name];
    }
}

class Schema_Controller extends Schema
{
    public function __header ()
    {
        print "<!?php\n";
?>

<?php
    }
}