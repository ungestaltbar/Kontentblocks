<?php

namespace Kontentblocks\Fields;


/**
 * FieldManagerPanels
 * Use ReFields outside of module context
 * WIP
 */
class PanelFieldController extends AbstractFieldController
{

    /**
     * Array of Field groups
     * @var array
     */
    public $Structure = array();
    public $preparedFields = array();
    /**
     * Unique ID from module
     * Used to prefix form fields
     * @var string
     */
    protected $baseId;
    /**
     *
     * @var object
     */
    protected $Panel;

    /**
     * Constructor
     *
     * @param $id
     * @param array $data
     * @param $Panel
     */
    public function __construct( $id, $data = array(), $Panel )
    {
        //TODO Check module consistency
        $this->baseId = $id;
        $this->data = $data;
        $this->Panel = $Panel;
    }

    /**
     * Creates a new section if there is not an exisiting one
     * or returns the section
     *
     * @param string $groupId
     * @param array $args
     *
     * @return object groupobject
     */
    public function addGroup( $groupId, $args = array() )
    {
        if (!$this->idExists( $groupId )) {
            $this->Structure[$groupId] = new PanelFieldSection( $groupId, $args, $this->Panel );
        }

        return $this->Structure[$groupId];

    }

    /**
     * Calls save on each group
     *
     * @param $data
     * @param $oldData
     *
     * @return array
     * @since 0.1.0
     */
    public function save( $data, $oldData )
    {
        $collection = array();
        foreach ($this->Structure as $definition) {
            $return = ( $definition->save( $data, $oldData ) );
            $collection = $collection + $return;
        }
        return $collection;

    }

    /**
     * Backend render method | Endpoint
     * output gets generated by attached render object
     * defaults to tabs
     * called by Kontentblocks\Modules\Module::options()
     * if not overridden by extending class
     * @see Kontentblocks\Modules\Module::form
     * @return string
     */
    public function renderFields()
    {
        $Renderer = new FieldRendererTabs( $this->baseId, $this->Structure );
        return $Renderer->render( $this->data );
    }


    /**
     * @param array $data
     */
    public function setData( $data )
    {
        $this->data = $data;
    }

    /**
     * internal
     * @return bool
     */
    public function isPublic()
    {
        return false;
    }

    /**
     * Returns the fields prepared data in one flat array
     * @return array
     */
    public function prepareDataAndGet()
    {
        if (!empty( $this->fieldsById )) {
            if (empty( $this->preparedFields )) {
                /** @var \Kontentblocks\Fields\Field $Field */
                foreach ($this->fieldsById as $Field) {
                    $this->preparedFields[$Field->getKey()] = $Field->getUserValue();
                }
            }
            return $this->preparedFields;
        }
        return $this->data;
    }
}
