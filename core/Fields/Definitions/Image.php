<?php

namespace Kontentblocks\Fields\Definitions;

use Kontentblocks\Fields\Field;
use Kontentblocks\Fields\FieldFormController;
use Kontentblocks\Language\I18n;
use Kontentblocks\Templating\FieldView;
use Kontentblocks\Utils\AttachmentHandler;

/**
 * Single image insert/upload.
 * @return array attachment id, title, caption
 *
 */
Class Image extends Field
{

    public static $settings = array(
        'type' => 'image',
        'returnObj' => 'EditableImage'
    );


    public function prepareTemplateData( $data )
    {
        $data['image'] = new AttachmentHandler( $this->getValue('id') );
        return $data;
    }


    /**
     * @param $val
     *
     * @return mixed
     */
    public function prepareFormValue( $val )
    {
        return $val;

    }

}