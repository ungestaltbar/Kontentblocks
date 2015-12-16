<?php

namespace Kontentblocks\Fields\Returnobjects;


use Kontentblocks\Utils\ImageResize;

class Image extends StandardFieldReturn
{

    public $attachment;

    public $attId;

    public $title = '';

    public $caption = '';

    public $size = array( 150, 150 );

    public $crop = true;

    public $upscale = false;

    public $src;

    private $valid = false;

    /**
     * @return int
     */
    public function getId()
    {
        if ($this->isValid()) {
            return $this->attId;
        }
        return 0;
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return $this->valid;
    }

    /**
     * @return string
     */
    public function getCaption()
    {
        return $this->caption;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getSrc()
    {
        if (is_null( $this->src )) {
            $this->resize();
        }
        return $this->src;
    }

    public function resize( $args = array() )
    {
        $defaults = array(
            'width' => $this->size[0],
            'height' => $this->size[1],
            'crop' => $this->crop,
            'upscale' => $this->upscale
        );

        $resizeargs = wp_parse_args( $args, $defaults );
        $this->src = ImageResize::getInstance()->process(
            $this->attId,
            $resizeargs['width'],
            $resizeargs['height'],
            $resizeargs['crop'],
            true,
            $resizeargs['upscale']
        );

        return $this;

    }

    public function html( $withsizes = false )
    {
        return $this->imageTag( $withsizes );
    }

    public function imageTag( $withsizes = false )
    {
        if (is_null( $this->src )) {
            $this->resize();
        }

        if ($withsizes) {
            return sprintf(
                '<img src="%s" title="%s" width="%d" height="%d" >',
                $this->src,
                $this->title,
                $this->size[0],
                $this->size[1]
            );
        }
        return sprintf( '<img src="%s" title="%s" >', $this->src, $this->title );
    }

    /**
     * @param $string
     * @return $this
     */
    public function nsize( $string )
    {
        if (isset( $this->attachment['sizes'][$string] )) {
            $size = $this->attachment['sizes'][$string];
            $this->setSize( $size['width'], $size['height'] );
        }
        return $this;

    }

    /**
     * @return $this|bool
     */
    public function setSize( $width, $height )
    {
        $this->size = array( $width, $height );
        return $this;

    }

    public function size( $width, $height )
    {
        return $this->setSize( $width, $height );
    }

    /**
     * @param $bool
     * @return $this
     */
    public function upscale( $bool = false )
    {
        $this->upscale = $bool;
        return $this;

    }

    /**
     * @param $crop
     * @return $this
     */
    public function crop( $crop = false )
    {
        $this->crop = $crop;
        return $this;
    }

    /**
     * @param $value
     * @return mixed
     */
    protected function prepareValue( $value )
    {

        if (array_key_exists( 'id', $value )) {
            $this->attId = $value['id'];
            $att = wp_prepare_attachment_for_js( $value['id'] );
            if (is_array( $att )) {
                $this->attachment = $att;
                $this->valid;
            }

            if (array_key_exists( 'caption', $value )) {
                $this->caption = $value['caption'];
            }

            if (array_key_exists( 'title', $value )) {
                $this->title = $value['title'];
            }

        }

        return $value;
    }
}