<?php


class ModuleTemplate
{

    protected $module;
    protected $data;
    protected $tplFile;
    protected $engine;

    public function __construct( $module, $tpl = false, $addData = false )
    {

        if ( !isset( $module ) || !is_object( $module ) ) {
            throw new Exception( 'Module is not set' );
        }

        $this->module  = $module;
        $this->data    = $this->_setupData( $module->new_instance, $addData );
        $this->tplFile = ($tpl !== false) ? $tpl : get_class( $module );

        $this->engine = KBTwig::getInstance();

    }

    public function render( $echo = false )
    {
        if ( $echo ) {
            $this->engine->display( $this->tplFile, $this->data );
        }
        else {
            return $this->engine->render( $this->tplFile, $this->data );
        }

    }

    public function setPath( $path )
    {

        KBTwig::setPath( $path );

    }

    public function __destruct()
    {
        KBTwig::resetPath();

    }

    /**
     * Setup Template Data
     * Merges Instance Data with optional additional data
     * sets up class property $data
     * @param array $modData
     * @param array $addData
     * @return array
     */
    private function _setupData( $modData, $addData )
    {
        if ( $addData ) {
            $data = wp_parse_args( $addData, $modData );
        }
        else {
            $data = $modData;
        }

        // make sure we have a key value pair, if not 
        // make 'data' the default key
        if ( !is_array( $data ) ) {
            $data = array(
                'data' => $data
            );
        }

        return $data;
    }

}