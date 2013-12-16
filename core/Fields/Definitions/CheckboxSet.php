<?php

namespace Kontentblocks\Fields\Definitions;

use Kontentblocks\Fields\Field;

Class CheckboxSet extends Field
{

    protected $defaults = array(
        'renderHidden' => true
    );

    public function form()
    {
        $options = $this->getArg( 'options', array() );
        $data    = $this->getValue();

        $this->label();

        foreach ( $options as $item ) {

            if ( !isset( $item[ 'key' ] ) OR !isset( $item[ 'label' ] ) OR !isset( $item[ 'value' ] ) ) {
                throw new Exception( 'Provide valid checkbox items. Check your code. Either a key, value or label is missing' );
            }

            $checked = ($item[ 'value' ] === $data[ $item[ 'key' ] ]) ? 'checked="checked"' : '';
            echo "<div class='kb-checkboxset-item'><label><input type='checkbox' id='{$this->get_field_id()}' name='{$this->get_field_name( true, $item[ 'key' ], false )}' value='{$item[ 'value' ]}'  {$checked} /> {$item[ 'label' ]}</label></div>";
        }

        $this->description();

    }

    public function save( $fielddata, $old )
    {
        $collect = array();
        $options = $this->getArg( 'options' );

        foreach ( $options as $k => $v ) {

            if ( !isset( $fielddata[ $v[ 'key' ] ] ) ) {
                $fielddata[ $v[ 'key' ] ] = FALSE;
            }
        }

        if ( is_array( $fielddata ) && !empty( $fielddata ) ) {
            foreach ( $fielddata as $k => $v ) {

                $filtered = filter_var( $v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );

                if ( $filtered === NULL ) {
                    $collect[ $k ] = $v;
                }
                else {
                    $collect[ $k ] = $filtered;
                }
            }
        }
        return $collect;

    }

    public function filter( $fielddata )
    {
        $collect = array();
        if ( !empty( $fielddata ) ) {
            foreach ( $fielddata as $k => $v ) {
                $filtered = filter_var( $v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE );

                if ( $filtered !== NULL ) {
                    $collect[ $k ] = $filtered;
                }
                else {
                    $collect[ $k ] = $v;
                }
            }
        }
        return $collect;

    }

}

kb_register_fieldtype( 'checkboxset', 'Kontentblocks\Fields\Definitions\CheckboxSet' );
