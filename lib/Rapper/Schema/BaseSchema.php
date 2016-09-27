<?php

namespace R\Lib\Rapper\Schema;

/**
 *
 */
class BaseSchema
{
    protected $id;
    protected $schema;
    /**
     *
     */
    public function __construct($id, $schema)
    {
        $this->id =$id;
        $this->schema =$schema;
    }
    /**
     *
     */
    public function getId()
    {
        return $this->id;
    }
    /**
     *
     */
    public function getName()
    {
        return $this->schema["name"];
    }
    /**
     *
     */
    public function getLabel()
    {
        return $this->schema["label"];
    }
    /**
     *
     */
    public function getValue($key)
    {
        return ref_array($this->schema,$key);
    }
}