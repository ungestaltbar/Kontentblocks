<?php

namespace Kontentblocks\Fields;

/**
 * By default all sections and fields are organized in tabs,
 * which get generated by this class
 * 
 * There may be different ways to organize in future releases
 * Instantiated by:
 * @see Kontentblocks\Fields\Refield::render()
 * 
 * @package Fields
 * @since 1.0.0
 */
class FieldRenderTabs
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

    
    /**
     * Constructor
     * @param type $structure
     */
    public function __construct( $structure)
    {
        $this->structure = $structure;

    }
    
    
    public function render($baseId, $data)
    {
        $this->baseId = $baseId;
        $this->data = $data;
        
        $this->tabNavigation();
        $this->tabContainers();

    }

    public function tabNavigation()
    {
        echo "<div class='kb_fieldtabs'>";
        echo "<ul>";

        foreach ( $this->structure as $definition ) {

            echo "<li><a href='#tab-{$definition->getID()}'>{$definition->getLabel()}</a></li>";
        }
        echo '</ul>';

    }

    public function tabContainers()
    {
        foreach( $this->structure as $definition) {
            echo "<div id='tab-{$definition->getID()}'>";
            $definition->render($this->baseId, $this->data);
            echo "</div>";
        }
        echo "</div>";
    }

}