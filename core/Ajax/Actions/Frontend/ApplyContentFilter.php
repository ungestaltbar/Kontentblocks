<?php

namespace Kontentblocks\Ajax\Actions\Frontend;

use Kontentblocks\Ajax\AjaxSuccessResponse;
use Kontentblocks\Common\Data\ValueStorageInterface;

/**
 * Class ApplyContentFilter
 * @package Kontentblocks\Ajax\Frontend
 */
class ApplyContentFilter
{
    static $nonce = 'kb-read';

    public static function run( ValueStorageInterface $Request )
    {
        global $post;
        $content = $Request->get( 'content' );
        $postId = $Request->getFiltered( 'postId', FILTER_SANITIZE_NUMBER_INT );
        $post = get_post( $postId );
        setup_postdata( $post );
        $html = apply_filters( 'the_content', $content );
        return new AjaxSuccessResponse(
            'Content filter apllied', array(
                'content' => $html
            )
        );
    }
}
