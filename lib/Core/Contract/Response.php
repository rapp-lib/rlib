<?php
namespace R\Lib\Core\Contract;

interface Response
{
    public function __construct ($output);
    public function render ();
    public function raise ();
}
