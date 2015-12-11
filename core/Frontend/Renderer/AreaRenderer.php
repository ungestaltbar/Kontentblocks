<?php

namespace Kontentblocks\Frontend\Renderer;

use Kontentblocks\Backend\Environment\Environment;
use Kontentblocks\Backend\Environment\PostEnvironment;
use Kontentblocks\Backend\Environment\Save\ConcatContent;
use Kontentblocks\Common\Interfaces\RendererInterface;
use Kontentblocks\Frontend\AreaNode;
use Kontentblocks\Frontend\ModuleIterator;
use Kontentblocks\Frontend\AreaRenderSettings;
use Kontentblocks\Frontend\ModuleRenderSettings;
use Kontentblocks\Modules\Module;

/**
 * Class AreaRenderer
 * Handles the frontend output for an area and containing modules
 *
 * Usage:
 * - simplified failsafe method: do_action('area', '## id of area ##', '## optional post id or null ##', $args)
 * - manual method: $Render = new \Kontentblocks\Frontend\AreaRenderer($id, $postId, $args);
 *                  $Render->render($echo);
 * @package Kontentblocks\Render
 */
class AreaRenderer implements RendererInterface
{

    /**
     * @var string
     */
    public $areaId;
    /**
     * @var \Kontentblocks\Areas\AreaProperties
     */
    public $area;
    /**
     * @var \Kontentblocks\Backend\Environment\Environment
     */
    public $environment;

    /**
     * Array of additional render settings
     * @var array
     */
    public $renderSettings;
    /**
     * @var AreaNode
     */
    protected $areaHtmlNode;
    /**
     * @var ModuleIterator
     */
    protected $modules;

    /**
     * Position
     * @var int
     */
    private $position = 1;

    /**
     * @var \Kontentblocks\Modules\Module
     */
    private $previousModule;

    /**
     * Flag if prev. module type equals current
     * @var bool
     */
    private $repeating;

    /**
     * @var
     */
    private $moduleRenderer;

    /**
     * Class constructor
     *
     * @param Environment $environment
     * @param AreaRenderSettings $renderSettings
     */
    public function __construct( Environment $environment, AreaRenderSettings $renderSettings )
    {
        $this->renderSettings = $renderSettings;
        $this->area = $renderSettings->area;
        $this->areaId = $this->area->id;
        $this->environment = $environment;
        $modules = $this->environment->getModulesforArea( $this->areaId );
        $this->modules = new ModuleIterator( $modules, $this->environment );
    }

    /**
     * Main render method
     * @param $echo
     * @return string
     */
    public function render( $echo )
    {

        if (!$this->validate()) {
            return false;
        }

        $this->areaHtmlNode = new AreaNode(
            $this->environment,
            $this->renderSettings
        );

        $this->areaHtmlNode->setModuleCount( count( $this->modules ) );
        $output = '';
        // start area output & create opening wrapper
        $output .= $this->areaHtmlNode->openArea();

        /**
         * @var \Kontentblocks\Modules\Module $module
         */
        // Iterate over modules (ModuleIterator)
        foreach ($this->modules as $module) {
            $moduleRenderSettings = new ModuleRenderSettings(
                array( 'moduleElement' => $this->renderSettings->moduleElement ), $module->properties
            );
            $this->moduleRenderer = new SingleModuleRenderer( $module, $moduleRenderSettings );

            if (!is_a( $module, '\Kontentblocks\Modules\Module' ) || !$module->verifyRender()) {
                continue;
            }
            $moduleOutput = $module->module();
            $output .= $this->areaHtmlNode->openLayoutWrapper();
            $output .= $this->beforeModule( $this->_beforeModule( $module ), $module );
            $output .= $moduleOutput;
            $output .= $this->afterModule( $this->_afterModule( $module ), $module );
            $output .= $this->areaHtmlNode->closeLayoutWrapper();


            if (current_theme_supports( 'kb.area.concat' ) && filter_input(
                    INPUT_GET,
                    'concat',
                    FILTER_SANITIZE_STRING
                )
            ) {
                if ($module->properties->getSetting( 'concat' )) {
                    ConcatContent::getInstance()->addString( wp_kses_post( $moduleOutput ) );
                }
            }
        }

        // close area wrapper
        $output .= $this->areaHtmlNode->closeArea();


        if ($echo) {
            echo $output;
        } else {
            return $output;
        }
    }

    /**
     *
     * @return bool
     */
    public function validate()
    {

        if (!$this->area->settings->isActive()) {
            return false;
        }

        if ($this->area->dynamic && !$this->area->settings->isAttached()) {
            return false;
        }

        return true;
    }

    /**
     *
     * @param $classes
     * @param Module $module
     * @return string
     */
    public function beforeModule( $classes, Module $module )
    {

        $layout = $this->areaHtmlNode->getCurrentLayoutClasses();


        if (!empty( $layout )) {
            return sprintf(
                '<div class="kb-wrap %2$s">%1$s',
                $this->moduleRenderer->beforeModule(),
                implode( ' ', $layout )
            );
        } else {
            return $this->moduleRenderer->beforeModule();
        }

    }

    public function _beforeModule( Module $module )
    {
        $moduleClasses = $this->modules->getCurrentModuleClasses();
        $additionalClasses = $this->getAdditionalClasses( $module );

        $mergedClasses = array_merge( $moduleClasses, $additionalClasses );
        if (method_exists( $module, 'preRender' )) {
            $module->preRender();
        }

        return $mergedClasses;
    }

    public function getAdditionalClasses( Module $module )
    {
        $classes = array();
        $classes[] = $module->properties->getSetting( 'slug' );
        $viewfile = $module->getViewfile();

        if (!empty( $viewfile )) {
            $classes[] = 'view-' . str_replace( '.twig', '', $viewfile );
        }

        if ($this->position === 1) {
            $classes[] = 'first-module';
        }

        if ($this->position === count( $this->modules )) {
            $classes[] = 'last-module';
        }

        if (is_user_logged_in()) {
            $classes[] = 'os-edit-container';

            if ($module->properties->getState( 'draft' )) {
                $classes[] = 'draft';
            }
        }

        if ($this->previousModule === $module->properties->getSetting( 'hash' )) {
            $classes[] = 'repeater';
            $this->repeating = true;
        } else {
            $this->repeating = false;
        }

        if ($this->repeating && $this->areaHtmlNode->getSetting( 'mergeRepeating' )) {
            $classes[] = 'module-merged';
            $classes[] = 'module';
        } else {
            $classes[] = 'module';
        }

        return $classes;

    }

    /**
     * @param $_after
     * @param $module
     * @return string
     */
    public function afterModule( $_after, Module $module )
    {
        $layout = $this->areaHtmlNode->getCurrentLayoutClasses();
        if (!empty( $layout )) {
            return "</div>" . sprintf( "%s", $this->moduleRenderer->afterModule() );
        } else {
            return $this->moduleRenderer->afterModule();

        }
    }

    public function _afterModule( Module $module )
    {
        $this->previousModule = $module->properties->getSetting( 'hash' );
        $this->position ++;
        $this->areaHtmlNode->nextLayout();
        return true;
    }


}
