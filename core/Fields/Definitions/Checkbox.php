<?php

namespace Kontentblocks\Fields\Definitions;

use Kontentblocks\Fields\Field;

/**
 * Single checkbox renders to boolean true or false
 * Unchecked will always return false instead of NULL
 */
Class Checkbox extends Field
{

    // field defaults
    public static  $settings = array(
        'type' => 'checkbox',
        'renderHidden' => true,
	    'forceSave' => true,
        'returnObj' => false
    );

    /**
     * Checkbox Form HTML
     */
    public function form()
    {
        $checked = checked( $this->getValue(), true, false );
        $this->label();
        echo "<label><input type='checkbox' id='{$this->getInputFieldId()}' name='{$this->getFieldName()}'  {$checked} /> {$this->getArg( 'text', 'Please label this checkbox' )}</label>";
        $this->description();

    }

    /**
     * Custom save filter
     * Makes sure that a value is saved even for unchecked fields
     * Since this field has 'forceSave' setting active, this method runs
     * even if field key is not present in $_POST
     * @param string $data value of key from $_POST data
     * @param string $oldData value of key from saved data
     * @return boolean
     */
    public function save( $data, $oldData )
    {
	    // if this field is not present in the current $_POST array
	    // check if old data exists and return that if it is valid
	    // else set the value of the checkbox to false instead of null
	    if (is_null($data)){
		    if (isset($oldData[$this->getKey()]) && is_bool($oldData[$this->getKey()])){
			    return filter_var($oldData[$this->getKey()], FILTER_VALIDATE_BOOLEAN);
		    } else {
			    return false;
		    }
	    }

        if ( filter_var( $data, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE ) ) {
            return true;
        }
        else {
            return false;
        }

    }



    /**
     * Custom retrieve filter when called for frontend
     * Makes sure that value is a true boolean
     * @param mixed $var value as saved
     * @return bool
     */
    protected  function prepareFormValue( $val )
    {
        return filter_var( $val, FILTER_VALIDATE_BOOLEAN );
    }

	public function prepareOutputValue($val){
		return filter_var($val, FILTER_VALIDATE_BOOLEAN);
	}

}
