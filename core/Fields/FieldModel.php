<?php

namespace Kontentblocks\Fields;


use Kontentblocks\Common\Data\EntityModel;

/**
 * Class FieldModel
 * @package Kontentblocks\Fields
 */
class FieldModel extends EntityModel
{
    /**
     * @var Field
     */
    protected $field;

    /**
     * @param array $data
     * @param Field $field
     */
    public function __construct( $data = array(), Field $field )
    {
        $this->field = $field;
        $this->_originalData = $data;

        $this->set( $data );
        $this->_initialized = true;

    }

    /**
     *
     * @return mixed
     * @since 0.1.0
     */
    public function export()
    {
        return $this->jsonSerialize();
    }

    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @return array
     * @since 0.1.0
     */
    public function jsonSerialize()
    {
        if (!is_null($this->singleValue)){
            return $this->singleValue;
        }

        $vars = get_object_vars( $this );
        unset( $vars['field'] );
        unset( $vars['_locked'] );
        unset( $vars['_initialized'] );
        unset( $vars['_originalData'] );
        unset($vars['singleValue']);
        return $vars;
    }

    public function sync()
    {
        // TODO: Implement sync() method.
    }
}