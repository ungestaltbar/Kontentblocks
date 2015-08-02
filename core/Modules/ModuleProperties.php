<?php

namespace Kontentblocks\Modules;


use AdamBrett\ShellWrapper\Command\Value;
use Kontentblocks\Areas\AreaSettingsModel;
use Kontentblocks\Backend\Storage\ModuleStorage;
use Kontentblocks\Kontentblocks;
use Kontentblocks\Utils\Utilities;

/**
 * Class ModuleProperties
 * @package Kontentblocks\Modules
 */
class ModuleProperties
{
    /**
     * Settings array as defined in each Module
     * @var array
     */
    public $settings;

    /**
     * Active and draft state
     * @var array
     */
    public $state;

    /**
     * @var string
     */
    public $area;

    /**
     * @var string
     */
    public $areaContext;

    /**
     * classname
     * @var string
     */
    public $class;

    /**
     * assigned viewfile
     * @var string
     */
    public $viewfile;

    /**
     * current post id | post context of modules
     * @var int
     */
    public $postId;

    /**
     * @var int deprecated
     */
    public $post_id;

    /**
     * settings overrides
     * @var array
     */
    public $overrides;


    /**
     * unique module id
     * @var string
     */
    public $mid;

    /**
     * @var int of module attached to
     */
    public $parentObjectId;

    /**
     * @var Post object of module attached to
     */
    public $parentObject;


    /**
     * @var bool is globalModule
     */
    public $globalModule;

    /**
     * @param array $properties
     */
    public function __construct( $properties )
    {
        $properties = $this->parseInSettings( $properties );
        $this->setupProperties( $properties );
    }

    /**
     * Add missing args from defaults
     * @param $properties
     *
     * @return array
     */
    private function parseInSettings( $properties )
    {
        /** @var \Kontentblocks\Modules\ModuleRegistry $moduleRegistry */
        $moduleRegistry = Kontentblocks::getService( 'registry.modules' );
        return Utilities::validateBoolRecursive(
            wp_parse_args( $properties, $moduleRegistry->get( $properties['class'] ) )
        );
    }

    private function setupProperties( $properties )
    {
        foreach ($properties as $k => $v) {
            if (method_exists( $this, 'set' . ucfirst( $k ) )) {
                $this->$k = $this->{'set' . ucfirst( $k )}( $v );
            } else {
                $this->$k = $v;
            }
        }
    }

    /*
     * ------------------------------------
     * Public getter
     * ------------------------------------
     */

    public function set( $prop, $value )
    {
        $this->setupProperties( array( $prop => $value ) );
        return $this;
    }

    public function get( $key )
    {
        if (property_exists( $this, $key )) {
            return $this->$key;
        }

        return null;
    }

    /**
     * Get a single module setting
     * @param $var string setting key
     * @return mixed|null
     */
    public function getSetting( $var )
    {
        if (isset( $this->settings[$var] )) {
            return $this->settings[$var];
        } else {
            return null;
        }
    }

    /*
     * ------------------------------------
     * Public setter
     * ------------------------------------
     */

    /**
     * Get a single module setting
     * @param $var string setting key
     * @return mixed|null
     */
    public function getState( $var )
    {
        if (isset( $this->state[$var] )) {
            return $this->state[$var];
        } else {
            return null;
        }
    }

    public function setId( $mid )
    {
        $this->mid = $mid;
    }


    // MAGIC SETTERS

    /**
     * Store properties to index
     * @return mixed
     * @since 0.2.0
     */
    public function sync()
    {
        $storage = new ModuleStorage( $this->parentObjectId );
        apply_filters( 'kb.modify.module.save', $this );
        return $storage->addToIndex( $this->mid, $this->export() );
    }

    /**
     * Export relevant properies as index storeable array
     * @param bool $keepSettings whether to export settings as well
     * @return array
     * @since 0.1.0
     */
    public function export( $keepSettings = false )
    {
        $vars = get_object_vars( $this );
        $vars['area'] = $this->area->id;
        $vars['parentObject'] = null;
        // settings are not persistent
        if (!$keepSettings) {
            unset( $vars['settings'] );
        }

        return $vars;
    }

    public function __set( $k, $v )
    {
//        d( $k, $v );
    }

    /**
     * Magic setter
     * converts area identifier to aea object
     * @param $var
     * @return mixed
     */
    private function setArea( $var )
    {
        /** @var \Kontentblocks\Areas\AreaRegistry $areaRegistry */
        $areaRegistry = Kontentblocks::getService( 'registry.areas' );
        $area = $areaRegistry->getArea( $var );

        if (is_null( $area )) {
            $area = $areaRegistry->getArea( '_internal' );
        }

        if (is_null( $area->settings )) {
            $area->set( 'settings', new AreaSettingsModel( $area, $this->postId ) );
        }

        /**
         * toJSON
         * make certain area properties accessible by js frontend-only
         */
        if (is_user_logged_in()) {
            Kontentblocks::getService( 'utility.jsontransport' )->registerArea( $area );
        }
        return $area;

    }
}