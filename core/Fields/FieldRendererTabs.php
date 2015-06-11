<?php

namespace Kontentblocks\Fields;

use Kontentblocks\Templating\CoreView;

/**
 * By default all sections and fields are organized in tabs,
 * which get generated by this class
 *
 * There may be different ways to organize in future releases
 * Instantiated by:
 * @see Kontentblocks\Fields\FieldManager::render()
 *
 * @package Fields
 * @since 0.1.0
 */
class FieldRendererTabs implements InterfaceFieldRenderer
{

    /**
     * Array of sections to render
     * @var array
     */
    protected $structure;

    /**
     * Unique identifier inherited by module
     * @var string
     */
    protected $baseId;

    /**
     * Instance data from module
     * Gets passed through to section handler
     * @var array
     */
    protected $data;


    public function __construct( $baseId, $structure = null )
    {
        $this->baseId = $baseId;

        if (!is_null( $structure )) {
            $this->setStructure( $structure );
        }

    }

    /**
     * @param $structure
     * @return mixed|void
     */
    public function setStructure( $structure )
    {
        $this->structure = $structure;
        return $this;
    }

    /**
     * Wrapper to output methods
     * @param $data
     * @return mixed|void
     */
    public function render( $data )
    {
        if (!is_array( $this->structure )) {
            return;
        }

        $View = new CoreView(
            'renderer/tabs.twig', array(
                'structure' => $this->structure,
                'data' => $data
            )
        );

        return $View->render();
    }

    /**
     * Renders the tab navigation markup
     */
    public function tabNavigation()
    {

        echo "<div class='kb_fieldtabs kb-field--tabs'>";
        echo "<ul>";


        foreach ($this->structure as $section) {

            if ($section->getNumberOfVisibleFields() > 0) {
                echo "<li><a href='#tab-{$section->getID()}'>{$section->getLabel()}</a></li>";
            }
        }
        echo '</ul>';

    }

    /**
     * Renders the tab containers
     */
    public function tabContainers()
    {
        foreach ($this->structure as $section) {
            if ($section->getNumberOfVisibleFields() > 0) {
                echo "<div id='tab-{$section->getID()}'>";
                $section->render( $this->data );
                echo "</div>";
            } else {
                $section->render( $this->data );
            }
        }
        echo "</div>";

    }

}
