<?php

namespace Kontentblocks\Fields\Definitions;

use Kontentblocks\Fields\Field;

/**
 * Custom callback for field content
 * Additional args are:
 *
 */
Class Callback extends Field
{

    // Defaults
    protected $defaults = array(
        'returnObj' => false
    );

    /**
     * Form
     */
    public function form()
    {
        if (!$this->getArg('callback')){
            echo "<p>No Callback specified</p>";
        }

        call_user_func_array($this->getArg('callback'), $this->getArg('args', array()));

    }


}

// register
kb_register_fieldtype( 'callback', 'Kontentblocks\Fields\Definitions\Callback' );