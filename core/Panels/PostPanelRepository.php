<?php

namespace Kontentblocks\Panels;


use Kontentblocks\Backend\Environment\Environment;
use Kontentblocks\Backend\Storage\ModuleStorage;
use Kontentblocks\Kontentblocks;

/**
 * Class PanelRepository
 * @package Kontentblocks\Panels
 */
class PostPanelRepository
{

    protected $environment;

    protected $panels = array();

    /**
     * @param Environment $environment
     */
    public function __construct( Environment $environment )
    {
        $this->environment = $environment;
        $this->setupPanelsforPost();
    }

    /**
     *
     * @since 0.3.8
     */
    public function setupPanelsForPost()
    {
        $environment = $this->environment;
        $filtered = $this->filterPanelsForPost( $environment );
        foreach ($filtered as $id => $panel) {
            $panel['uid'] = hash( 'crc32', serialize( $panel ) );
            $panel['postId'] = $environment->getId();
            $this->panels[$id] = $instance = new $panel['class']( $panel, $environment );
            $instance->prepare();
        }
    }

    /**
     *
     * @since 0.3.8
     * @param Environment $environment
     * @return array
     */
    private function filterPanelsForPost( Environment $environment )
    {
        $red = [ ];

        /** @var \Kontentblocks\Panels\PanelRegistry $registry */
        $registry = Kontentblocks::getService( 'registry.panels' );

        foreach ($registry->getAll() as $id => $panel) {
            $postTypes = !empty($panel['postTypes']) ? $panel['postTypes'] : [];
            $pageTemplates = !empty($panel['pageTemplates']) ? $panel['pageTemplates'] : [];

            if (is_array( $pageTemplates ) && !empty( $pageTemplates )) {
                if (!in_array( $environment->getPageTemplate(), $pageTemplates )) {
                    continue;
                }
            }

            if (is_array( $postTypes ) && !empty( $postTypes )) {
                if (!in_array( $environment->getPostType(), $postTypes )) {
                    continue;
                }
            }

            $red[$id] = $panel;
        }

        return $red;
    }

    /**
     * @return array
     */
    public function getPanelObjects()
    {
        return $this->panels;
    }

    /**
     * Get PropertiesObject from collection by id
     * @param $panelId
     * @return Panel|null
     */
    public function getPanelObject( $panelId )
    {
        if (isset( $this->panels[$panelId] )) {
            return $this->panels[$panelId];
        }
        return null;
    }
}