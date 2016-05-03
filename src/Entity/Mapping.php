<?php
namespace Mapping\Entity;

use Omeka\Entity\AbstractEntity;
use Omeka\Entity\Item;

/**
 * Defines the default state of an item's map.
 *
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
     * @Column(type="smallint", nullable=true)
     */
    protected $defaultZoom;

    /**
     * @Column(type="float", nullable=true)
     */
    protected $defaultLat;

    /**
     * @Column(type="float", nullable=true)
     */
    protected $defaultLng;

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

    public function setDefaultZoom($defaultZoom)
    {
        $this->defaultZoom = '' === trim($defaultZoom) ? null : $defaultZoom;
    }

    public function getDefaultZoom()
    {
        return $this->defaultZoom;
    }

    public function setDefaultLat($defaultLat)
    {
        $this->defaultLat = '' === trim($defaultLat) ? null : $defaultLat;
    }

    public function getDefaultLat()
    {
        return $this->defaultLat;
    }

    public function setDefaultLng($defaultLng)
    {
        $this->defaultLng = '' === trim($defaultLng) ? null : $defaultLng;
    }

    public function getDefaultLng()
    {
        return $this->defaultLng;
    }
}
