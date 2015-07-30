<?php

namespace Kontentblocks\Ajax\Actions\Frontend;

use Kontentblocks\Ajax\AjaxActionInterface;
use Kontentblocks\Ajax\AjaxSuccessResponse;
use Kontentblocks\Common\Data\ValueStorageInterface;
use Kontentblocks\Utils\ImageResize;

/**
 * Class FieldGetImage
 * Gets an resized version of the provided image attachment id
 * @author Kai Jacobsen
 * @package Kontentblocks\Ajax\Frontend
 */
class FieldGetImage implements AjaxActionInterface
{
    static $nonce = 'kb-read';

    /**
     * @param ValueStorageInterface $request
     * @return AjaxSuccessResponse
     */
    public static function run( ValueStorageInterface $request )
    {
        $args = $request->get( 'args' );
        $width = ( !isset( $args['width'] ) ) ? 150 : $args['width'];
        $height = ( !isset( $args['height'] ) ) ? null : $args['height'];
        $upscale = filter_var( $args['upscale'], FILTER_VALIDATE_BOOLEAN );
        $attachmentid = $request->getFiltered( 'id', FILTER_SANITIZE_NUMBER_INT );


        return new AjaxSuccessResponse(
            'Image resized', array(
                'src' => ImageResize::getInstance()->process(
                    $attachmentid,
                    $width,
                    $height,
                    $args['crop'],
                    true,
                    $upscale
                )
            )
        );
    }
}
