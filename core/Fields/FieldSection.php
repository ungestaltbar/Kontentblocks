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
 * @since 0.1.0
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
     * @param Module $module
     *
     * @return \Kontentblocks\Fields\FieldSection
     */
    public function __construct( $id, $args, $module, $baseid )
    {

        $this->id = $id;
        $this->args = $this->prepareArgs( $args );
        $this->module = $module;
        $this->baseId = $baseid;
    }

    /**
     * Set visibility of field based on environment vars given by the module
     * By following a hierachie: PostType -> PageTemplate -> AreaContext
     *
     * @param \Kontentblocks\Fields\Field $field
     *
     * @return void
     */
    public function markVisibility( Field $field )
    {

        $field->setDisplay( true );
        $areaContext = $this->module->context->get( 'areaContext' );
        $postType = $this->module->context->get( 'postType' );
        $pageTemplate = $this->module->context->get( 'pageTemplate' );
        if ($this->module->properties->getSetting( 'views' )) {
            $moduleTemplate = $this->module->getViewfile();
            if ($field->getCondition( 'viewfile' ) && !in_array(
                    $moduleTemplate,
                    (array) $field->getCondition( 'viewfile' )
                )
            ) {
                $field->setDisplay( false );
                $this->_decreaseVisibleFields();

                return;
            }
        }

        if ($field->getCondition( 'postType' ) && !in_array( $postType, (array) $field->getCondition( 'postType' ) )) {
            $field->setDisplay( false );
            $this->_decreaseVisibleFields();

            return;
        }

        if ($field->getCondition( 'pageTemplate' ) && !in_array(
                $pageTemplate,
                (array) $field->getCondition( 'pageTemplate' )
            )
        ) {
            $field->setDisplay( false );
            $this->_decreaseVisibleFields();

            return;
        }

        if (!isset( $areaContext ) || $areaContext === false || ( $field->getCondition( 'areaContext' ) === false )) {
            $field->setDisplay( true );
            return;
        } else if (!in_array( $areaContext, $field->getCondition( 'areaContext' ) )) {
            $field->setDisplay( false );
            return;
        }

        $this->_decreaseVisibleFields();

        return;
    }


}
