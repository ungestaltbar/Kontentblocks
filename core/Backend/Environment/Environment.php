<?php

namespace Kontentblocks\Backend\Environment;

use JsonSerializable;
use Kontentblocks\Areas\AreaSettingsModel;
use Kontentblocks\Backend\Storage\ModuleStorage;
use Kontentblocks\Backend\DataProvider\DataProviderController;
use Kontentblocks\Backend\Environment\Save\SavePost;
use Kontentblocks\Kontentblocks;
use Kontentblocks\Modules\ModuleRepository;
use Kontentblocks\Panels\PostPanelRepository;
use Kontentblocks\Utils\Utilities;


/**
 * Post Environment
 *
 * @package Kontentblocks
 * @subpackage Post
 * @since 0.1.0
 */
class Environment implements JsonSerializable
{

    /**
     * generic low-level data handler
     * @var \Kontentblocks\Backend\DataProvider\DataProviderController
     */
    protected $DataProvider;

    /**
     * Module specific storage handler
     * @var \Kontentblocks\Backend\Storage\ModuleStorage
     */
    protected $Storage;

    /**
     * Access object to all env related modules
     * @var ModuleRepository
     */
    protected $ModuleRepository;


    /**
     * Access object to all env related panels
     * @var ModuleRepository
     */
    protected $PostPanelRepository;


    /**
     * @var int
     */
    protected $storageId;

    /**
     * @var \WP_Post
     */
    protected $postObj;

    /**
     * @var string
     */
    protected $pageTemplate;

    /**
     * @var string
     */
    protected $postType;

    /**
     * @var array
     */
    protected $modules;

    /**
     * @var array
     */
    protected $areas;

    /**
     * @var array
     */
    protected $panels;

    /**
     * @var array
     */
    protected $areasByContext;


    /**
     * Class constructor
     *
     * @param $storageId
     * @param \WP_Post $postObj
     * @since 0.1.0
     */
    public function __construct( $storageId, \WP_Post $postObj )
    {

        $this->postObj = $postObj;
        $this->storageId = $storageId;

        $this->Storage = new ModuleStorage( $storageId );
        $this->ModuleRepository = new ModuleRepository( $this );
        $this->PostPanelRepository = new PostPanelRepository( $this );

        $this->pageTemplate = $this->getPageTemplate();
        $this->postType = $this->getPostType();
        $this->modules = $this->setupModules();
        $this->modulesByArea = $this->getSortedModules();
        $this->areas = $this->setupAreas();
        $this->areasToContext();
        $this->panels = $this->PostPanelRepository->getPanelObjects();

    }

    /**
     * returns the page template if available
     * returns 'default' if not. in order to normalize the object property
     * If post type does not support page templates, it's still
     * 'default' on the module
     * @return string
     * @since 0.1.0
     */
    public function getPageTemplate()
    {
        // value is handled by wordpress, so stick to post meta api
        $tpl = get_post_meta( $this->postObj->ID, '_wp_page_template', true );

        if ($tpl !== '') {
            return $tpl;
        }

        return 'default';

    }

    /**
     * Get Post Type
     * @since 0.1.0
     */
    public function getPostType()
    {
        return $this->postObj->post_type;
    }

    /**
     * prepares modules attached to this post
     * @return array
     * @since 0.1.0
     */
    private function setupModules()
    {
        return $this->ModuleRepository->getModules();
    }

    /**
     * Sorts module definitions to areas
     * @return array
     * @since 0.1.0
     */
    public function getSortedModules()
    {
        $sorted = array();
        if (is_array( $this->modules )) {
            /** @var \Kontentblocks\Modules\Module $module */
            foreach ($this->modules as $module) {
                $sorted[$module->Properties->area->id][$module->getId()] = $module;
            }
            return $sorted;
        }
    }

    /**
     * Augment areas with Settings instance
     * Settings are environment related so this must happen late
     * @since 0.3.0
     */
    private function setupAreas()
    {
        $areas = $this->findAreas();
        /** @var \Kontentblocks\Areas\AreaProperties $area */
        foreach ($areas as $area) {
            $area->set( 'settings', new AreaSettingsModel( $area, $this->postObj->ID ) );
        }
        return $areas;

    }

    /**
     * returns all areas which are available in this environment
     * @return array
     * @since 0.1.0
     */
    public function findAreas()
    {
        /** @var \Kontentblocks\Areas\AreaRegistry $AreaRegistry */
        $AreaRegistry = Kontentblocks::getService( 'registry.areas' );
        return $AreaRegistry->filterForPost( $this );
    }

    /**
     * @since 0.3.0
     */
    private function areasToContext()
    {
        if (is_array( $this->areas ) && !empty( $this->areas )) {
            foreach ($this->areas as $id => $area) {
                $this->areasByContext[$area->context][$id] = $area;
            }
        }
    }

    public function getPanelObject( $id )
    {
        if (isset($this->panels[$id])){
            return $this->panels[$id];
        }
        return null;
    }

    /**
     * Return ID for the current storage entity
     * (most likely equals post id)
     * @return int
     * @since 0.1.0
     */
    public function getId()
    {
        return absint( $this->storageId );
    }

    /**
     * @return \WP_Post
     * @since 0.1.0
     */
    public function getPostObject()
    {
        return $this->postObj;
    }

    /**
     * get arbitrary property
     * @param string $param
     * @return mixed
     * @since 0.1.0
     */
    public function get( $param )
    {
        if (isset( $this->$param )) {
            return $this->$param;
        } else {
            return false;
        }
    }

    /**
     * returns the DataProvider instance
     * @return DataProviderController
     * @since 0.1.0
     */
    public function getDataProvider()
    {
        return $this->Storage->getDataProvider();
    }

    /**
     * Returns all modules set to this post
     * @return array
     * @since 0.1.0
     */
    public function getAllModules()
    {
        return $this->modules;
    }

    /**
     * @param $mid
     * @return \Kontentblocks\Modules\Module
     * @since 0.1.0
     */
    public function getModuleById( $mid )
    {
        return $this->ModuleRepository->getModuleObject( $mid );
    }

    /**
     * returns module definitions filtered by area
     *
     * @param string $areaid
     * @return mixed
     * @since 0.1.0
     */
    public function getModulesForArea( $areaid )
    {
        $byArea = $this->getSortedModules();
        if (!empty( $byArea[$areaid] )) {
            return $byArea[$areaid];
        } else {
            return false;
        }
    }

    /**
     * Get Area Definition
     *
     * @param string $area
     * @return mixed
     * @since 0.1.0
     */
    public function getAreaDefinition( $area )
    {
        if (isset( $this->areas[$area] )) {
            return $this->areas[$area];
        } else {
            return false;
        }

    }

    /**
     * Get all post-specific areas
     * @return array
     * @since 0.1.0
     */
    public function getAreas()
    {
        return $this->areas;
    }

    /**
     *
     * @param $context
     * @return array
     * @since 0.3.0
     */
    public function getAreasForContext( $context )
    {
        if (isset( $this->areasByContext[$context] ) && is_array( $this->areasByContext[$context] )) {
            return $this->areasByContext[$context];
        }

        return array();
    }

    /**
     * Get settings for given area
     *
     * @param string $id
     *
     * @return mixed
     */
    public function getAreaSettings( $id )
    {
        $settings = $this->Storage->getDataProvider()->get( 'kb_area_settings' );
        if (!empty( $settings[$id] )) {
            return $settings[$id];
        }
        return false;
    }

    /**
     * Wrapper to low level handler method
     * returns instance data or an empty string
     *
     * @param string $id
     *
     * @return string
     * @since 0.1.0
     */
    public function getModuleData( $id )
    {
        $this->Storage->reset();
        $data = $this->Storage->getModuleData( $id );
        if ($data !== null) {
            return $data;
        } else {
            return array();
        }

    }

    /**
     * Save callback handler
     * @return void
     * @since 0.1.0
     */
    public function save()
    {
        $SaveHandler = new SavePost( $this );
        $SaveHandler->save();
    }

    /**
     * (PHP 5 &gt;= 5.4.0)<br/>
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 0.1.0
     */
    function jsonSerialize()
    {
        return array(
            'postId' => absint( $this->storageId ),
            'pageTemplate' => $this->getPageTemplate(),
            'postType' => $this->getPostType()
//            'moduleCount' => $this->getModuleCount()
        );
    }
//
//    /**
//     * @return mixed
//     * @since 0.1.0
//     */
//    public function getModuleCount()
//    {
//        return Utilities::getHighestId( $this->getStorage()->getIndex() );
//    }

    /**
     * Return this Storage Object
     * @return ModuleStorage
     * @since 0.1.0
     */
    public function getStorage()
    {
        return $this->Storage;
    }

    /**
     * @since 0.1.0
     */
    public function toJSON()
    {
        echo "<script> var KB = KB || {}; KB.Environment =" . json_encode( $this ) . "</script>";
    }
}
