<?php

namespace Kontentblocks\Backend\Dynamic;

use Kontentblocks\Backend\Environment\Environment;
use Kontentblocks\Backend\Storage\ModuleStorage;
use Kontentblocks\Kontentblocks;
use Kontentblocks\Modules\ModuleWorkshop;
use Kontentblocks\Templating\CoreView;
use Kontentblocks\Utils\Utilities;

/**
 * Class ModuleTemplates
 * @package Kontentblocks\Backend\Dynamic
 */
class GlobalModules
{


    /**
     * Class constructor
     * Add relevant Hooks
     * @since 0.1.0
     */
    public function __construct()
    {

        add_action( 'init', array( $this, 'registerPostType' ) );
        add_action( 'init', array( $this, 'registerPseudoArea' ) );
        add_action( 'admin_menu', array( $this, 'addAdminMenu' ), 19 );
        add_action( 'edit_form_after_title', array( $this, 'addForm' ), 1 );
        add_action( 'save_post', array( $this, 'save' ), 11, 2 );
        add_filter( 'wp_insert_post_data', array( $this, 'postData' ), 10, 2 );
        add_filter( 'post_updated_messages', array( $this, 'postTypeMessages' ) );
    }


    /**
     * Add admin menu entry
     *
     * @since 0.1.0
     * @return void
     */
    public function addAdminMenu()
    {
        if (!Utilities::adminMenuExists( 'Kontentblocks' )) {
            add_menu_page(
                'kontentblocks',
                'Kontentblocks',
                'manage_kontentblocks',
                '/edit.php?post_type=kb-mdtpl',
                false,
                false
            );
        }

        add_submenu_page(
            '/edit.php?post_type=kb-dyar',
            'Global Modules',
            'Global Modules',
            'manage_kontentblocks',
            '/edit.php?post_type=kb-mdtpl',
            false
        );

    }

    /**
     * Handles which form to show
     * either add new
     * or the module form
     * @since 0.1.0
     * @return void
     */
    public function addForm()
    {
        global $post;

        wp_nonce_field( 'kontentblocks_save_post', 'kb_noncename' );
        wp_nonce_field( 'kontentblocks_ajax_magic', '_kontentblocks_ajax_nonce' );
        $Storage = new ModuleStorage( $post->ID );
        // on this screen we always deal with only
        // one module
        // instance_id equals post_name
        $template = $Storage->getModuleDefinition( $post->post_name );
        // no template yet, create new
        if (empty( $template )) {
            $this->createForm();
        } else {
            $this->globalModule( $template, $Storage->getDataProvider() );
        }

    }


    /**
     * Display form of the module
     *
     * @param $gmodule
     * @since 0.1.0
     * @retutn void
     */
    protected function globalModule( $gmodule )
    {
        global $post;
        if (empty( $gmodule )) {
            wp_die( 'no template arg provided' );
        }
        // TODO Include a public context switch
        // Modules resp. the input form of a module may rely on a certain context
        // or have different fields configuration
        // TODO Explanation text for non-developers on page
        $context = ( isset( $_GET['area-context'] ) ) ? $_GET['area-context'] : 'normal';

        //set area context on init
        $gmodule['areaContext'] = $context;
        $gmodule['area'] = 'global-module';
        // create essential markup and render the module
        // infamous hidden editor hack
        Utilities::hiddenEditor();
        $Environment = Utilities::getEnvironment( $post->ID );
        $Module = $Environment->getModuleById( $gmodule['mid'] );

        Kontentblocks::getService( 'utility.jsontransport' )->registerModule( $Module->toJSON() );
        // Data for twig
        $templateData = array(
            'nonce' => wp_create_nonce( 'update-template' ),
            'instance' => $Module
        );

        if (isset( $_GET['return'] )) {
            echo "<input type='hidden' name='kb_return_to_post' value='{$_GET['return']}' >";
        }
        // To keep html out of php files as much as possible twig is used
        $FormNew = new CoreView( 'module-template.twig', $templateData );
        $FormNew->render( true );


    }

    /**
     * Form for creating new template
     *
     * @since 0.1.0
     * @return void
     */
    protected function createForm()
    {

        $screen = get_current_screen();
        if ($screen->post_type !== 'kb-mdtpl') {
            return;
        }


        /*
         * Form validation happens on the frontend
         * if this fails for any reason, data is preserved anyway
         * for completeness
         */
        $postData = ( !empty( $_POST['new-gmodule'] ) ) ? $_POST['new-gmodule'] : array();

        // Data for twig
        $templateData = array(
            'modules' => $this->prepareModulesforSelectbox( $postData ),
            'nonce' => wp_create_nonce( 'new-gmodule' ),
            'data' => $postData,
            'master' => ( isset( $postData['master'] ) ) ? 'checked="checked"' : ''
        );

        // To keep html out of php files as much as possible twig is used
        // Good thing about twig is it handles unset vars gracefully
        $FormNew = new CoreView( 'global-modules/add-new.twig', $templateData );
        $FormNew->render( true );

    }

    /**
     * Save handler, either creates a new module or just updates an exisiting
     *
     * @param int $postId
     * @param array $postObj
     * @since 0.1.0
     * @return bool|void
     */
    public function save( $postId, $postObj )
    {
        // auth request
        if (!$this->auth( $postId )) {
            return false;
        }

        $Environment = Utilities::getEnvironment( $postId );
        $Module = $Environment->getModuleById( $postObj->post_name );
        // no template yet, create an new one
        if (!$Module) {
            $this->createTemplate( $postId, $Environment );
        } else {
            // update existing
            $old = $Module->Model->getOriginalData();
            $data = $_POST[$Module->getId()];
            $new = $Module->save( $data, $old );
            $toSave = Utilities::arrayMergeRecursive( $new, $old );
            // save viewfile if present
            $Module->Properties->viewfile = ( !empty( $data['viewfile'] ) ) ? $data['viewfile'] : '';
            $Environment->getStorage()->saveModule( $Module->getId(), $toSave );
            $Environment->getStorage()->reset();
            $Environment->getStorage()->addToIndex( $Module->getId(), $Module->Properties->export() );
            // return to original post if the edit request came from outside
            if (isset( $_POST['kb_return_to_post'] )) {
                $url = get_edit_post_link( $_POST['kb_return_to_post'] );
                wp_redirect( html_entity_decode( $url ) );
                exit;
            }
        }

    }

    /**
     * Create a new template from form data
     *
     * @param $postId
     * @param Environment $Environment
     * @internal param ModuleStorage $Storage
     * @since 0.1.0
     */
    public function createTemplate( $postId, Environment $Environment )
    {

        // no template data send
        if (empty( $_POST['new-gmodule'] )) {
            return;
        }

        // set defaults
        $defaults = array(
            'master' => false,
            'masterRef' => array(
                'parentId' => $postId,
            ),
            'template' => true,
            'name' => null,
            'id' => null,
            'type' => null,
        );
        // parse $_POST data
        $data = wp_parse_args( $_POST['new-gmodule'], $defaults );

        // convert checkbox input to boolean
        $data['master'] = filter_var( $data['master'], FILTER_VALIDATE_BOOLEAN );

        // 3 good reasons to stop
        if (is_null( $data['id'] ) || is_null( $data['name'] || is_null( $data['type'] ) )) {
            wp_die( 'Missing arguments' );
        }

        $workshop = new ModuleWorkshop(
            $Environment, array(
                'master' => $data['master'],
                'masterRef' => array(
                    'parentId' => $postId,
                ),
                'template' => true,
                'templateRef' => array(
                    'id' => $data['id'],
                    'name' => $data['name']
                ),
                'area' => 'module-template',
                'areaContext' => 'normal',
                'class' => $data['type'],
                'mid' => $data['id']
            )
        );


        $definition = $workshop->getDefinitionArray();
        do_action( 'kb.create:module', $definition );

        // add to post meta kb_kontentblocks
        $Environment->getStorage()->addToIndex( $data['id'], $definition );
        // single post meta entry, to make meta queries easier
//        $Environment->getStorage()->getDataProvider()->update( 'master', $definition['master'] );
    }


    /**
     *
     * Since all native wp controls are removed from this screen
     * we need to manually save essential post data
     *
     * @param $data
     * @param $post
     * @return mixed
     *
     * @since 0.1.0
     */
    public function postData( $data, $post )
    {

        if ($post['post_type'] !== 'kb-mdtpl') {
            return $data;
        }

        if (!isset( $_POST['new-gmodule'] )) {
            return $data;
        }


        $title = filter_var( $_POST['new-gmodule']['name'], FILTER_SANITIZE_STRING );
        $slug = filter_var( $_POST['new-gmodule']['id'], FILTER_SANITIZE_STRING );
        // no template data send
        if (!isset( $title, $slug )) {
            return $data;
        }

        $data['post_title'] = $title;
        $data['post_name'] = $slug;

        return $data;
    }

    /**
     * Register the template post type
     *
     * @since 0.1.0
     * @return void
     */
    public function registerPostType()
    {

        $labels = array(
            'name' => _x( 'Global Module', 'post type general name', 'Kontentblocks' ),
            'singular_name' => _x( 'Global Module', 'post type singular name', 'Kontentblocks' ),
            'menu_name' => _x( 'Global Modules', 'admin menu', 'Kontentblocks' ),
            'name_admin_bar' => _x( 'Global Modules', 'add new on admin bar', 'Kontentblocks' ),
            'add_new' => _x( 'add New', 'book', 'Kontentblocks' ),
            'add_new_item' => __( 'add New global module', 'Kontentblocks' ),
            'new_item' => __( 'new global module', 'Kontentblocks' ),
            'edit_item' => __( 'edit global module', 'Kontentblocks' ),
            'view_item' => __( 'view global module', 'Kontentblocks' ),
            'all_items' => __( 'all global modules', 'Kontentblocks' ),
            'search_items' => __( 'search global modules', 'Kontentblocks' ),
            'parent_item_colon' => __( 'parent global modules:', 'Kontentblocks' ),
            'not_found' => __( 'No global modules found.', 'Kontentblocks' ),
            'not_found_in_trash' => __( 'No global modules found in Trash.', 'Kontentblocks' ),
        );

        $args = array(
            'labels' => $labels,
            'public' => false,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => false,
            'query_var' => true,
            'capability_type' => 'post',
            'has_archive' => false,
            'hierarchical' => false,
            'menu_position' => 999,
            'supports' => null
        );

        register_post_type( 'kb-mdtpl', $args );
        remove_post_type_support( 'kb-mdtpl', 'editor' );
        remove_post_type_support( 'kb-mdtpl', 'title' );
    }

    /**
     * Modify template specific messages
     *
     * @param $messages
     *
     * @since 0.1.0
     * @return mixed
     */
    public function postTypeMessages( $messages )
    {
        $post = get_post();

        $messages['kb-mdtpl'] = array(
            0 => '', // Unused. Messages start at index 1.
            1 => __( 'global modules updated.', 'Kontentblocks' ),
            2 => __( 'Custom field updated.', 'Kontentblocks' ), // not used
            3 => __( 'Custom field deleted.', 'Kontentblocks' ), // not used
            4 => __( 'global modules updated.', 'Kontentblocks' ),
            /* translators: %s: date and time of the revision */
            5 => isset( $_GET['revision'] ) ? sprintf(
                __(
                    'global module restored to revision from %s',
                    'Kontentblocks'
                ),
                wp_post_revision_title( (int) $_GET['revision'], false )
            ) : false,
            6 => __( 'global module published.', 'Kontentblocks' ),
            7 => __( 'global module saved.', 'Kontentblocks' ),
            8 => __( 'global module submitted.', 'Kontentblocks' ),
            9 => sprintf(
                __( 'global module scheduled for: <strong>%1$s</strong>.', 'Kontentblocks' ),
                // translators: Publish box date format, see http://php.net/date
                date_i18n( __( 'M j, Y @ G:i', 'Kontentblocks' ), strtotime( $post->post_date ) )
            ),
            10 => __( 'global module draft updated.', 'Kontentblocks' ),
        );

        return $messages;
    }

    /**
     * Various checks
     *
     * @param $postId
     *
     * @since 0.1.0
     * @return bool
     */
    private function auth( $postId )
    {
        // verify if this is an auto save routine.
        // If it is our form has not been submitted, so we dont want to do anything
        if (empty( $_POST )) {
            return false;
        }

        if (defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE) {
            return false;
        }

        // verify this came from the our screen and with proper authorization,
        // because save_post can be triggered at other times
//        if (!wp_verify_nonce($_POST['kb_noncename'], 'kontentblocks_save_post')) {
//            return false;
//        }

        // Check permissions
        if (!current_user_can( 'edit_post', $postId )) {
            return false;
        }

        if (!current_user_can( 'edit_kontentblocks' )) {
            return false;
        }

        if (get_post_type( $postId ) == 'revision' && !isset( $_POST['wp-preview'] )) {
            return false;
        }

        // checks passed
        return true;
    }

    /**
     * Gets all available Modules from registry
     *
     * @param array $postData potential incomplete form data
     *
     * @since 0.1.0
     * @return array
     */
    private function prepareModulesforSelectbox( $postData )
    {

        $type = ( isset( $postData['type'] ) ) ? $postData['type'] : '';
        $modules =

        $modules = $this->getTemplateables();

        $collection = array();

        if (!empty( $modules )) {
            foreach ($modules as $module) {
                $collection[] = array(
                    'name' => $module['settings']['name'],
                    'class' => $module['settings']['class'],
                    'selected' => ( $module['settings']['class'] === $type ) ? 'selected="selected"' : ''
                );
            }

        }

        return $collection;

    }


    /**
     * Filter all modules which may be created as a template
     * @since 0.1.0
     * @return array
     */
    public function getTemplateables()
    {


        /** @var \Kontentblocks\Modules\ModuleRegistry $ModuleRegistry */
        $ModuleRegistry = Kontentblocks::getService( 'registry.modules' );

        return array_filter(
            $ModuleRegistry->getAll(),
            function ( $module ) {
                if (isset( $module['settings']['asGlobalModule'] ) && $module['settings']['asGlobalModule'] == true) {
                    return true;
                }
                return false;
            }
        );

    }

    public function registerPseudoArea()
    {
        \Kontentblocks\registerArea(
            array(
                'id' => 'global-module',
                'internal' => true,
                'dynamic' => false
            )
        );
    }

}