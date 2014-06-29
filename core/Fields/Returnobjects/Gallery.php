<?php

namespace Kontentblocks\Fields\Returnobjects;

class Gallery {

	protected $field;

	protected $value;

	public $images = array();

	public function __construct( $value, $Field ) {


		$this->field = $Field;
		$this->value = $value;

		if ( isset( $value['images'] ) && is_array( $value['images'] ) ) {
			$this->setupMediaElements();
		}
	}

	/**
	 * Create image objects from input
	 */
	private function setupMediaElements() {
		foreach ( $this->value['images'] as $k => $element ) {
			if ( isset( $element['id'] ) && !empty( $element['id'] ) ) {
//				array_push($this->images, new Utilities\ImageObject( $element['file'] ));
				$field = array(
					'key'         => $this->field->getKey() . '.images',
					'arrayKey'    => $this->field->getArg( 'arrayKey' ),
					'index'       => $k,
					'instance_id' => $this->field->parentModuleId,
					'type'        => 'gallery'
				);

				$Obj = new Image( $element, $field );
				$Obj->inlineEdit(false);
				array_push( $this->images, $Obj );
			}
		}

	}


}