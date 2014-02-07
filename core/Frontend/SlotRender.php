<?php

namespace Kontentblocks\Frontend;

/**
 * internal working name: SlotMachine
 * Experimental way to render modules manually for an given area
 * The SlotMachine works by providing a index to the ::slot($pos) method,
 * which will get the actual module from the iterator and call render on it
 *
 * Modules are ordered as they were added on the backend
 *
 * Usage:
 * Instantiate a new SlotMachine in your template file and provide
 * arguments for the area and the current post id
 * $SlotMachine = new \Kontentblocks\Frontend\SlotMachine('my-area-id', 66);
 *
 * To render a specific position just call
 * $SlotMachine->slot($pos);
 *
 * Case of use:
 * If you need to create very specific layouts and need fine control, and you actually know
 * what kind and how many modules are present.
 *
 *
 * Class SlotRender
 * @package Kontentblocks\Frontend
 */
class SlotRender
{

    /**
     * internal pointer, starts with 1
     * @var int
     */
    protected $position = 1;

    /**
     * Class Constructor
     * @param $area
     * @param $postId
     */
    public function __construct($area, $postId)
    {
        if (!isset($area) || !isset($postId)) {
            return;
        }


        /** @var $Environment \Kontentblocks\Backend\Environment\PostEnvironment */
        $Environment = \Kontentblocks\Helper\getEnvironment($postId);
        $modules = $Environment->getModulesForArea($area);

        $this->Iterator = new ModuleIterator($modules, $Environment);
    }

    /**
     * Simply render the next module
     * @since 1.0.0
     */
    public function next()
    {
        $this->slot($this->position + 1);
    }

    /**
     * Actual method to handle the stuff
     * @param $pos
     * @since 1.0.0
     * @TODO complete printf
     */
    public function slot($pos)
    {
        $this->position = $pos;

        $module = $this->Iterator->setPosition($pos);
        if (!is_null($module)) {
            printf('<div id="%1$s" class="%2$s">', $module->instance_id, 'os-edit-container');

            echo $module->module();
            echo "</div>";

            $module->toJSON();

        }

    }
}