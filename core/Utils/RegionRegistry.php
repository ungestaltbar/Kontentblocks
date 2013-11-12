<?php

namespace Kontentblocks\Utils;

use Kontentblocks\Admin\Post\PostEnvironment;

/**
 * The RegionRegistry is a single interaction container to access area definitions throughout the plugin
 * 
 */
class RegionRegistry
{

    protected $rawAreas  = array();
    protected $templates = array();
    static $instance;

    /**
     * Singleton Pattern
     * Get the Instance of the Area Directory
     * original instantiated on plugin startup
     * @return object | Area directory instance
     */
    public static function getInstance()
    {
        if ( null == self::$instance ) {
            self::$instance = new self;
            self::$instance->init();
        }

        return self::$instance;

    }

    /**
     * Init Function
     * Gets areas which were added by the user through the admin interface
     * Adds those areas to the directory
     * 
     * @return void
     */
    public function init()
    {
        $storedAreas = get_option( 'kb_registered_areas' );
        if ( !empty( $storedAreas ) ) {
            foreach ( $storedAreas as $area ) {
                $this->addRegion( $area, false );
            }
        }

    }

    /**
     * Adds an area to the directory
     * Merges default arguments with provided arguments
     * Manual indicates if the area has been registered by code (true) or
     * was added though the admin interface (false)
     * 
     * @param array $args
     * @param bool $manual
     * @return $void
     */
    public function addRegion( $args, $manual = true )
    {
        if ( !empty( $args[ 'id' ] ) ) {
            $args[ 'id' ] = sanitize_title( $args[ 'id' ] );
        }

        // merge defaults with provided args
        $area = wp_parse_args( $args, $this->_getDefaults( $manual ) );

        if ( empty( $this->rawAreas[ $area[ 'id' ] ] ) ) {
            $this->rawAreas[ $area[ 'id' ] ] = $area;
        }
        else {
            $this->rawAreas[ $area[ 'id' ] ] = wp_parse_args( $this->rawAreas[ $area[ 'id' ] ], $area );
        }

    }

    /**
     * Save an area to global areas
     * @param string $id identifier of area
     * @param array $args area arguments
     * @return bool | update successful true | false
     */
    public function saveArea( $id, $args )
    {
        $storedAreas = get_option( 'kb_registered_areas' );

        $storedAreas[ $id ] = $args;
        return update_option( 'kb_registered_areas', $storedAreas );

    }

    /**
     * Returns an area from the directory by id
     * @param string $id
     * @return mixed null if area is not set | area array args if area is set
     */
    public function getArea( $id )
    {
        if ( isset( $this->rawAreas[ $id ] ) ) {
            return $this->rawAreas[ $id ];
        }
        else {
            return null;
        }

    }

    /**
     * Returns only globally registered areas
     * i.e. all areas where dynamic equals true
     * @return array
     */
    public function getGlobalAreas()
    {
        $collection = array();
        foreach ( $this->rawAreas as $area ) {
            if ( $area[ 'dynamic' ] === true ) {
                $collection[] = $area;
            }
        }
        return $collection;

    }

    /**
     * Returns all registered area templates
     * @return array of template definitions
     */
    public function getTemplates()
    {
        return $this->templates;

    }

    /**
     * Registers an area template and adds it to the area templates array
     * @param array $args
     */
    public function addTemplate( $args )
    {
        if ( !empty( $args[ 'id' ] ) ) {
            $this->templates[ $args[ 'id' ] ] = $args;
        }

    }

    /**
     * Modules can connect themselves to an area by specifing the connect parameter
     * This method handles the connection by adding the module classname to the
     * assigned modules array of the area
     * 
     * A Module can be added to all registered areas by setting connect to 'any'
     * TODO: In future versions it should be possible to add modules only to areas by context
     * @param string $classname
     * @param array $args module args
     */
    public function connect( $classname, $args )
    {
        if ( !empty( $args[ 'settings' ][ 'connect' ] ) && $args[ 'settings' ][ 'connect' ] === 'any' ) {

            foreach ( $this->rawAreas as $area_id => $area ) {
                if ( $area[ 'assignedModules' ] === NULL || !in_array( $classname, $area[ 'assignedModules' ] ) ) {
                    $this->rawAreas[ $area_id ][ 'assignedModules' ][] = $classname;
                }
            }
        }
        else if ( !empty( $args[ 'settings' ][ 'connect' ] ) and is_array( $args[ 'settings' ][ 'connect' ] ) ) {
            $update = false;


            foreach ( $args[ 'settings' ][ 'connect' ] as $area_id ) {
                if ( empty( $this->rawAreas[ $area_id ] ) ) {
                    continue;
                }

                $area = $this->rawAreas[ $area_id ];

                if ( !in_array( $classname, $area[ 'assignedModules' ] ) ) {
                    $area[ 'assignedModules' ][] = $classname;
                }
                $this->rawAreas[ $area_id ] = $area;
                $update                     = true;
            }
        }

    }

    /**
     * Filters registered areas by post settings
     * This needs an instance of the PostEnvironment Class to provide
     * all necessary informations for the filter
     * Areas can be limited to post types and/or page templates
     * @param \Kontentblocks\Admin\PostEnvironment $postData
     * @return boolean
     */
    public function filterForPost( PostEnvironment $postData )
    {

        $pageTemplate = $postData->get( 'pageTemplate' );
        $postType     = $postData->get( 'postType' );

        // bail out if this is a redirect template
        if ( false !== strpos( $pageTemplate, 'redirect' ) ) {
            return false;
        }

        //declare var
        $areas = array();

        // loop through areas and find all which are attached to this post type and/or page template
        foreach ( $this->rawAreas as $area ) {

            if ( empty( $area[ 'context' ] ) ) {
                $area[ 'context' ] = 'side';
            }

            if ( (!empty( $area[ 'pageTemplates' ] ) ) && (!empty( $area[ 'postTypes' ] )) ) {
                if ( in_array( $pageTemplate, $area[ 'pageTemplates' ] ) && in_array( $postType, $area[ 'postTypes' ] ) ) {
                    $areas[] = $area;
                }
            }
            elseif ( !empty( $area[ 'pageTemplates' ] ) ) {
                if ( in_array( $pageTemplate, $area[ 'pageTemplates' ] ) ) {
                    $areas[] = $area;
                }
            }
            elseif ( !empty( $area[ 'postTypes' ] ) ) {
                if ( in_array( $postType, $area[ 'postTypes' ] ) ) {
                    $areas[] = $area;
                }
            }
        }

        $sareas = self::orderBy( $areas, 'order' );

        return $sareas;

    }

    /**
     * Private helper method to order the areas array by a specified field
     * i.e. order
     * 
     * @param array $areas
     * @param string $field
     * @return array
     */
    private function orderBy( $areas, $field ) 
    {
        $code = "return strnatcmp(\$a['$field'], \$b['$field']);";
        usort( $areas, create_function( '$a,$b', $code ) );
        return $areas;

    }

    /**
     * Normalize each area by passing it through this method
     * 
     * @param bool $manual
     * @return array
     */
    private function _getDefaults( $manual = true )
    {
        return array(
            'id' => '', // unique id of area
            'name' => '', // public shown name
            'description' => '', // public description
            'beforeArea' => '<div id="%s" class="kb_area %s">', //default wrapper markup
            'afterArea' => '</div>',
            'postTypes' => array(), // array of post types where this area is available to
            'pageTemplates' => array(), // array of page template names where this area is available to
            'assignedModules' => array(), // array of classnames
            'layouts' => array(), // array of area template ids
            'dynamic' => false, // whether this is an dynamic area
            'manual' => $manual, // true if set by code
            'limit' => '0', // how many blocks are allowed
            'order' => 0, // order index for sorting
            'dev_mode' => false, // deprecated
            'context' => 'normal' // where on the edit screen
        );

    }

}
