<?php

namespace Kontentblocks\Fields;

use Kontentblocks\Modules\Module;

/**
 * Purpose of this Class:
 *
 * This serves as a collection handler for fields and offers
 * methods to interact with registered fields.
 *
 * Gets instantiated by Kontentblocks\Fields\ModuleFieldController when
 * addGroup() is called
 *
 * @see Kontentblocks\Fields\FieldManager::addSection()
 * @package Fields
 * @package Fields
 * @since 1.0.0
 */
class FieldSection extends AbstractFieldSection
{

    /**
     * Section id
     * @var string
     */
    public $id;


    /**
     * Constructor
     *
     * @param string $id
     * @param $args
     * @param $envVars
     * @param Module $module
     *
     * @return \Kontentblocks\Fields\FieldSection
     */
    public function __construct( $id, $args, $envVars, $module, $baseid )
    {

        $this->id = $id;
        $this->args = $this->prepareArgs( $args );
        $this->envVars = $envVars;
        $this->Module = $module;
        $this->baseId = $baseid;
    }

    /**
     * Set visibility of field based on environment vars given by the module
     * By following a hierachie: PostType -> PageTemplate -> AreaContext
     *
     * @param \Kontentblocks\Fields\Field $Field
     *
     * @return void
     */
    public function markVisibility( Field $Field )
    {

        $Field->setDisplay( true );

        $areaContext = $this->envVars['areaContext'];
        $postType = $this->envVars['postType'];
        $pageTemplate = $this->envVars['pageTemplate'];

        if ($this->Module->getSetting( 'useViewLoader' )) {
            $moduleTemplate = $this->Module->getViewfile();
            if ($Field->getCondition( 'viewfile' ) && !in_array(
                    $moduleTemplate,
                    (array) $Field->getCondition( 'viewfile' )
                )
            ) {
                $Field->setDisplay( false );
                $this->_decreaseVisibleFields();

                return;
            }
        }

        if ($Field->getCondition( 'postType' ) && !in_array( $postType, (array) $Field->getCondition( 'postType' ) )) {
            $Field->setDisplay( false );
            $this->_decreaseVisibleFields();

            return;
        }

        if ($Field->getCondition( 'pageTemplate' ) && !in_array(
                $pageTemplate,
                (array) $Field->getCondition( 'pageTemplate' )
            )
        ) {
            $Field->setDisplay( false );
            $this->_decreaseVisibleFields();

            return;
        }

        if (!isset( $areaContext ) || $areaContext === false || ( $Field->getCondition( 'areaContext' ) === false )) {
            $Field->setDisplay( true );
            return;
        } else if (!in_array( $areaContext, $Field->getCondition( 'areaContext' ) )) {
            $Field->setDisplay( false );

            return;
        }

        $this->_decreaseVisibleFields();

        return;
    }


}
