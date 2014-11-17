<?php


use Kontentblocks\Kontentblocks;
use Kontentblocks\Language\I18n;
use Kontentblocks\Modules\Module;
use Kontentblocks\Templating\CoreView;

/**
 * Class ModuleCoreMasterModule
 */
class ModuleCoreMasterModule extends Module
{

    public static $defaults = array(
        'publicName' => 'Master Module',
        'id' => 'core-master-module',
        'description' => 'Handles reference to master templates',
        'globallyAvailable' => false,
        'asTemplate' => false,
        'master' => true,
        'hidden' => true,
    );

    public static function init()
    {
        // runs only once on module creation and sets the original class reference to this class
        // to create the correct backend form
        add_filter( 'kb.intercept.creation.args', array( __CLASS__, 'manipulateModuleArgs' ) );

        // runs whenever a module parameter array is passed to the factory
        add_filter( 'kb.module.before.factory', array( __CLASS__, 'validateModule' ) );

        // runs when the frontend modal updates form data and changes the mid to the original
        // template id
        add_filter( 'kb.modify.module.save', array( __CLASS__, 'setTemplateId' ) );

        // runs on module setup of the module iterator. change module parameter before
        // frontend output here
        add_filter( 'kb.before.frontend.setup', array( __CLASS__, 'setupModule' ) );

        // runs inside of the module factory, before module data is set to the module instance
        add_filter( 'kb.module.factory.data', array( __CLASS__, 'setupModuleData' ), 10, 2 );
    }

    /**
     * Only for this Core Module
     * Verifies that the original template still exists
     *
     * @param $module
     * @return mixed
     */
    public static function validateModule( $module )
    {
        if (!isset( $module['parentId'] )) {
            return $module;
        }

        $masterId = $module['parentId'];
        $icl = get_post_meta( get_the_ID(), '_icl_lang_duplicate_of', true );
        $duplicate = !empty( $icl );

        if (I18n::getInstance()->wpmlActive() && !$duplicate) {
            $iclId = icl_object_id( $masterId, 'kb-mdtpl' );
            $translated = ( $iclId !== $masterId );

            if ($translated) {
                $masterId = $iclId;
            }

        }


        if (is_null( $masterId )) {
            $module['state']['draft'] = true;
            $module['state']['active'] = false;
            $module['state']['valid'] = false;
        } else {
            $module['state']['valid'] = ( get_post_status( $masterId ) === 'trash' ) ? false : true;
        }
        return $module;

    }

    /**
     * Module Form
     * @return bool|void
     */
    public function form()
    {

        $masterId = $this->parentId;
        $translated = false;
        $icl = get_post_meta( get_the_ID(), '_icl_lang_duplicate_of', true );
        $duplicate = !empty( $icl );

        if (I18n::getInstance()->wpmlActive() && !$duplicate) {
            $iclId = icl_object_id( $masterId, 'kb-mdtpl' );
            $translated = ( $iclId !== $masterId );

            if ($translated) {
                $masterId = $iclId;
            }

        }

        $templateData = array(
            'valid' => $this->state['valid'],
            'editUrl' => html_entity_decode( get_edit_post_link( $masterId ) . '&amp;return=' . get_the_ID() ),
            'translated' => $translated,
            'duplicate' => $duplicate,
            'module' => $this,
            'i18n' => I18n::getInstance()->getPackage( 'Modules.master' )
        );

        $tpl = ( isset( $this->state['valid'] ) && $this->state['valid'] ) ? 'master-module-valid.twig' : 'master-module-invalid.twig';

        $Tpl = new CoreView( $tpl, $templateData );
        $Tpl->render( true );

    }


    /**
     * Output is mapped back to the original module class which was set
     * when the master template was created
     */
    public function render()
    {

    }


    /**
     * Prepare moduleArgs for frontend output
     *
     * @param $module
     *
     * @return array
     */
    public static function setupModule( $module )
    {
        /** @var \Kontentblocks\Modules\ModuleRegistry $ModuleRegistry */
        $ModuleRegistry = Kontentblocks::getService( 'registry.modules' );

        if ($module['master'] && isset( $module['parentId'] )) {
            $masterId = $module['parentId']; // post id of the template
            $icl = get_post_meta( get_the_ID(), '_icl_lang_duplicate_of', true );
            $duplicate = ( !empty( $icl ) );


            if (I18n::getInstance()->wpmlActive() && !$duplicate) {
                $iclId = icl_object_id( $masterId, 'kb-mdtpl' );
                $translated = ( $iclId !== $masterId );
                if ($translated) {
                    $masterId = $iclId;
                }
            }

            // original template module definition
            $index = get_post_meta( $masterId, 'kb_kontentblocks', true );
            $template = $index[$module['templateObj']['id']];
            // actual module definition
            $originalDefiniton = $ModuleRegistry->get( $template['class'] );

            // $module is actually the Master_Module, we need to override everything to the actual module
            $glued = wp_parse_args( $template, $originalDefiniton );

            // $glued holds whatever was set to the original template + missing default values
            // now we need to override settings from the actual edit screen
            unset( $glued['state'] );
            unset( $glued['master_id'] );
            unset( $glued['areaContext'] );
            unset( $glued['area'] );
            // finally
            $final = wp_parse_args( $glued, $module );
            $final['parentId'] = $masterId;
            return $final;
        }

        return $module;
    }

    /**
     * @param $module
     * @param $moduleDef
     * @return mixed
     */
    public static function setupModuleData( $module, $moduleDef )
    {
        if (isset( $moduleDef['master'] ) && filter_var( $moduleDef['master'], FILTER_VALIDATE_BOOLEAN )) {
            $masterId = $moduleDef['parentId'];
            $tplId = $moduleDef['templateObj']['id'];
            $data = get_post_meta( $masterId, '_' . $tplId, true );
            return $data;
        }
        return $module;
    }

    /**
     * Intercept module args on creation and override module class
     *
     * @param $moduleArgs
     *
     * @return array
     */
    public static function manipulateModuleArgs( $moduleArgs )
    {
        if ($moduleArgs['master']) {
            $moduleArgs['class'] = 'ModuleCoreMasterModule';

        }
        return $moduleArgs;
    }

    /**
     * When creating the instance, the mid must be set to the orginal master id
     * @param $args
     * @return mixed
     */
    public static function setTemplateId( $args )
    {
        if (isset( $args['master'] ) && $args['master']) {
            if (isset( $args['templateObj'] )) {
                $args['instance_id'] = $args['templateObj']['id'];
                $args['mid'] = $args['templateObj']['id'];
            }
        }
        return $args;
    }

    // Nothing to save here
    public function save( $data, $old )
    {
        return;
    }


}
