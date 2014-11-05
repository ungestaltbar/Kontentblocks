<?php

namespace Kontentblocks\Frontend;


use Kontentblocks\Utils\JSONBridge;
use Kontentblocks\Utils\Utilities;

/**
 * Class SingleModuleRenderer
 * @package Kontentblocks\Frontend
 */
class SingleModuleRenderer
{


    public function __construct( $Module )
    {
        $this->Module = $Module;
    }

    public function render( $args = array() )
    {
        if (!$this->Module->verify( )) {
            return false;
        }

        $addArgs = $this->setupArgs( $args );

        $this->Module->_addAreaAttributes( $addArgs );
        printf(
            '<%3$s id="%1$s" class="%2$s">',
            $this->Module->getId(),
            "os-edit-container module {$this->Module->getSetting('id')}",
            $addArgs['element']
        );
        echo $this->Module->module();
        echo "</{$addArgs['element']}>";
        JSONBridge::getInstance()->registerModule( $this->Module->toJSON() );
    }

    private function setupArgs( $args )
    {
        $defaults = array(
            'context' => Utilities::getTemplateFile(),
            'subcontext' => 'content',
            'element' => 'div',
            'action' => null,
            'area_template' => 'default'
        );

        return wp_parse_args( $args, $defaults );
    }


}