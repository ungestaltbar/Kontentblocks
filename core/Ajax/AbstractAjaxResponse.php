<?php

namespace Kontentblocks\Ajax;


abstract class AbstractAjaxResponse implements \JsonSerializable
{

    protected $message = '';

    protected $data = array();

    /**
     * @param string $message
     * @param array $data
     */
    public function __construct( $message = '', $data = array() )
    {
        $this->message = $message;
        $this->data = $data;
        wp_send_json($this);
    }

    public function getMessage()
    {
        return $this->message;
    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * Should return a boolean value
     * @return bool
     */
    abstract public function getStatus();

    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     */
    abstract function jsonSerialize();
}