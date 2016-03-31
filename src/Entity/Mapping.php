<?php
namespace Mapping\Entity;

use Omeka\Entity\AbstractEntity;
use Omeka\Entity\Item;

/**
 * @Entity
 */
class Mapping extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @OneToOne(targetEntity="Omeka\Entity\Item")
     * @JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected $item;

    /**
     * @Column(nullable=true)
     */
    protected $wmsBaseUrl;

    /**
     * @Column(nullable=true)
     */
    protected $wmsLayers;

    /**
     * @Column(nullable=true)
     */
    protected $wmsStyles;

    /**
     * @Column(nullable=true)
     */
    protected $wmsLabel;

    public function getId()
    {
        return $this->id;
    }

    public function setItem(Item $item)
    {
        $this->item = $item;
    }

    public function getItem()
    {
        return $this->item;
    }

    public function setWmsBaseUrl($wmsBaseUrl)
    {
        $this->wmsBaseUrl = '' === trim($wmsBaseUrl) ? null : $wmsBaseUrl;
    }

    public function getWmsBaseUrl()
    {
        return $this->wmsBaseUrl;
    }

    public function setWmsLayers($wmsLayers)
    {
        $this->wmsLayers = '' === trim($wmsLayers) ? null : $wmsLayers;
    }

    public function getWmsLayers()
    {
        return $this->wmsLayers;
    }

    public function setWmsStyles($wmsStyles)
    {
        $this->wmsStyles = '' === trim($wmsStyles) ? null : $wmsStyles;
    }

    public function getWmsStyles()
    {
        return $this->wmsStyles;
    }

    public function setWmsLabel($wmsLabel)
    {
        $this->wmsLabel = '' === trim($wmsLabel) ? null : $wmsLabel;
    }

    public function getWmsLabel()
    {
        return $this->wmsLabel;
    }
}
