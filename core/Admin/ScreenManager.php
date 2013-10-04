<?php

namespace Kontentblocks\Admin;

use Kontentblocks\Admin\PostDataContainer,
    Kontentblocks\Admin\ScreenContext;

class ScreenManager
{

    
    protected $rawAreas;
    protected $postAreas;
    protected $contextLayout;
    protected $contexts;

    public function __construct( PostDataContainer $postData )
    {

        if ( empty( $postData->get( 'areas' ) ) ) {
            return false;
        }

        $this->postData = $postData;
        $this->contextLayout = $this->_getDefaultContextLayout();
        $this->rawAreas      = $postData->get( 'areas' );
        $this->contexts      = $this->areasSortedByContext( $this->rawAreas );
        $this->hasSidebar    = $this->evaluateLayout();

    }

    /*
     * Main Render function
     */

    public function render()
    {
        foreach ( $this->contextLayout as $contextId => $args ) {

                $context = new ScreenContext( $args, $this );
                $context->render();
        }

    }

    /**
     * Sort raw Area definitions to array
     * @return array
     */
    public function areasSortedByContext()
    {
        if ( !$this->rawAreas ) {
            throw new Exception('No Areas specified for context');
        }

        foreach ( $this->rawAreas as $area ) {
            $contextfy[ $area[ 'context' ] ][$area['id']] = $area;
        }

        return $contextfy;

    }
    
    
    public function getContextAreas($id)
    {
        if (isset($this->contexts[$id])){
            return $this->contexts[$id];
        } else {
            return array();
        }
    }
    
    

    /*
     * Default Context Layout
     * 
     * @return array default context layout
     * @filter kb_default_context_layout
     */

    public function _getDefaultContextLayout()
    {
        $defaults = array(
            'top' => array(
                'id' => 'top',
                'title' => __( 'Page header', 'kontentblocks' ),
                'description' => __( 'Full width area at the top of this page', 'kontentblocks' )
            ),
            'normal' => array(
                'id' => 'normal',
                'title' => __( 'Content', 'kontentblocks' ),
                'description' => __( 'Main content column of this page', 'kontentblocks' )
            ),
            'side' => array(
                'id' => 'side',
                'title' => __( 'Page Sidebar', 'kontentblocks' ),
                'description' => __( 'Sidebar of this page', 'kontentblocks' )
            ),
            'bottom' => array(
                'id' => 'bottom',
                'title' => __( 'Footer', 'kontentblocks' ),
                'description' => __( 'Full width area at the bottom of this page', 'kontentblocks' )
            )
        );

        // plugins may change this
        return apply_filters( 'kb_default_context_layout', $defaults );

    }

    public function evaluateLayout()
    {
        return (!empty( $this->contexts[ 'side' ] )) ? true : false;

    }

    public function getPostAreas(){
        return $this->postAreas;
    }
}
