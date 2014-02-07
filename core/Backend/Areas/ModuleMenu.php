<?php

namespace Kontentblocks\Backend\Areas;

use Kontentblocks\Modules\ModuleRegistry;
use Kontentblocks\Utils\JSONBridge;

/*
 * Kontentblocks: Areas: Menu Class
 * @package Kontentblocks
 * @subpackage Areas
 */

class ModuleMenu
{
    /*
     * Blocks available through menu
     */

    public $blocks = array();


    /*
     * Blocks sorted by their category
     */
    public $categories = array();


    /*
     * Whitelist for available categories
     */
    public $cats = array();

    /*
     * Area id passed to constructor for unique container ids
     */
    private $id = '';

    /*
     * Area context passed to constructor
     */
    private $context = '';

    /*
     * Localization String
     */
    public $l18n = array();

    /*
     * Constructor
     */

    function __construct( $area )
    {
        // All Modules which are accessible by this area
        $this->modules = ModuleRegistry::getInstance()->getValidModulesForArea( $area, $area->get( 'environment' ) );
        if ( empty( $this->modules ) or !is_array( $this->modules ) or !isset( $area->id ) or !isset( $area->context ) ) {
            return false;
        }
        //assign id
        $this->id = $area->id;

        //assign context
        //@todo think about how global modules should handle this
        $this->context = $area->context;

        //setup available cats
        $this->_setupCats();

        $this->_prepareCategories();

        // sort blocks to categories
        $this->_modulesToCategories();

        $this->l18n = array(
            // l18n
            'add_block' => __( 'add module', 'kontentblocks' ),
            'add' => __( 'add', 'kontentblocks' ),
            'add_template' => __( 'add template', 'kontentblocks' ),
            'no_blocks' => __( 'Sorry, no Blocks available for this Area', 'kontentblocks' ),
            'modules' => __( 'Add new module', 'kontentblocks' )
        );


        add_action( 'admin_footer', array( $this, 'menuFooter' ), 10, 1 );

    }

    /* Menu Footer
     *
     * Print modal menu contents to the admin footer
     * Makes sure that the modal is outside of wp-wrap and positions as expected
     */

    public function menuFooter()
    {
        // prepare a class for the menu <ul> to avoid two columns if not necessary
        $menu_class = ( count( $this->modules ) <= 4 ) ? 'one-column-menu' : 'two-column-menu';

        $out = "<div id='{$this->id}-nav' class='reveal-modal modules-menu-overlay {$menu_class}'>";
        $out.= "<div class='modal-inner cf'>";

        $out .= "<div class='area-blocks-menu-tabs'>";
        $out .= $this->_getNavTabs();
        $out .= $this->_getTabsContent();
        $out .= "</div>";

        $out .= "</div>"; // end inner
        $out .= "</div>"; // end moddal container
        echo $out;

    }

    /*
     * Admin menu link, opens the modal
     */

    public function menuLink()
    {

        if ( current_user_can( 'create_kontentblocks' ) ) {
            if ( !empty( $this->modules ) ) {
                $out = " <div class='add-modules cantsort'>

					</div>";
                return $out;
            }
            return false;
        }

    }

    /*
     * Tab Navigation for categories markup
     */

    private function _getNavTabs()
    {
        $out = '';

        $out .= "<ul id='blocks-menu-{$this->id}-nav' class='block-menu-tabs'>";

        foreach ( $this->categories as $cat => $items ) {
            if ( empty( $items ) )
                continue;

            $out .= "<li> <a href='#{$this->id}-{$cat}-tab'>{$this->cats[ $cat ]}</a></li>";
        }

        $out .= "</ul>";

        return $out;

    }

    /*
     * Markup for tabs content
     */

    private function _getTabsContent()
    {

        if ( current_user_can( 'create_kontentblocks' ) ) {
            $out = '';

            foreach ( $this->categories as $cat => $items ) {
                if ( empty( $items ) ) {
                    continue;
                }
                $out.= "<div id='{$this->id}-{$cat}-tab'>";
                $out.= "<ul  class='blocks-menu'>";


                foreach ( $items as $item ) {
                    $out.= $this->_getItem( $item );
                }

                $out.= "</ul>";
                $out.= "</div>";
            }
            return $out;
        }

    }

    /*
     * Markup for menu normal items
     */

    private function _getItem( $item )
    {


        if ( isset( $item[ 'settings' ][ 'hidden' ] ) && $item[ 'settings' ][ 'hidden' ] == true )
            return null;


        $instance_id = (isset( $item[ 'template_reference' ] )) ? "data-template_reference='{$item[ 'reference_id' ]}' data-template=true" : null;
        $master      = (isset( $item[ 'settings' ][ 'master' ] )) ? "data-master=true" : null;


        $img        = (!empty( $item[ 'settings' ][ 'icon' ] )) ? $item[ 'settings' ][ 'icon' ] : '';
        $blockclass = $item[ 'settings' ][ 'class' ];

        $out = "	<li class='block-nav-item' data-type='{$blockclass}' {$instance_id} {$master} data-context='{$this->context}' >
						<div class='block-icon'><img src='{$img}' ></div>
						<div class='block-info'><h3>{$item[ 'settings' ][ 'public_name' ]}</h3>
							<p class='description'>{$item[ 'settings' ][ 'description' ]}</p>
						</div>
						<span class='action'>{$this->l18n[ 'add' ]}</span>
					</li>";
        return $out;

    }

    /*
     * Sort blocks to categories
     * If category is not set, assign the first from the whitelist
     */

    private function _modulesToCategories()
    {

        foreach ( $this->modules as $module ) {
            // check for categories
            $cat                        = (!empty( $module[ 'settings' ][ 'category' ] ) ) ? $this->_getValidCategory( $module[ 'settings' ][ 'category' ] ) : 'standard';
            $this->categories[ $cat ][] = $module;
        }
        // add templates
        $saved_block_templates = get_option( 'kb_block_templates' );

        if ( !empty( $saved_block_templates ) ) {
            foreach ( $saved_block_templates as $tpl ) {

                $this->categories[ 'templates' ][ $tpl[ 'instance_id' ] ] = new $tpl[ 'class' ];

                if ( !empty( $tpl[ 'instance_id' ] ) ) {
                    $this->categories[ 'templates' ][ $tpl[ 'instance_id' ] ]->instance_id               = $tpl[ 'instance_id' ];
                    $this->categories[ 'templates' ][ $tpl[ 'instance_id' ] ]->settings[ 'public_name' ] = $tpl[ 'name' ];
                }

                if ( !empty( $tpl[ 'master' ] ) ) {
                    $this->categories[ 'templates' ][ $tpl[ 'instance_id' ] ]->master = true;
                }
            }
        }
    }

    /*
     * Validate category against whitelist
     * If it fails, assign the first category of the whitelist
     */

    private function _getValidCategory( $cat )
    {
        foreach ( $this->cats as $c => $name ) {
            if ( $c == $cat ) {

                return $cat;
            }
        }
        return (isset( $this->cats[ 0 ] )) ? $this->cats[ 0 ] : false;

    }

    /*
     * Filterable array of allowed cats
     * uses @filter kb_menu_cats
     * @return void
     */

    private function _setupCats()
    {
        // defaults
        $cats = array(
            'standard' => __( 'Standard', 'kontentblocks' ),
        );

        $cats = apply_filters( 'kb_menu_cats', $cats );


        $cats[ 'media' ]   = __( 'Media', 'kontentblocks' );
        $cats[ 'special' ] = __( 'Spezial', 'kontentblocks' );

        $cats[ 'core' ]      = __( 'System', 'kontentblocks' );
        $cats[ 'template' ] = __( 'Templates', 'kontentblocks' );

        $this->cats = $cats;
        JSONBridge::getInstance()->registerData('ModuleCategories', null, $cats);

    }

    /*
     * Create initial array to preserve the right order
     */

    public function _prepareCategories()
    {
        foreach ( $this->cats as $cat => $name ) {
            $this->categories[ $cat ] = array();
        }

    }

}