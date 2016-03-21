<?php
namespace Omeka\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Omeka\Entity\AbstractEntity;

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
     * @OneToOne(targetEntity="Item")
     * @JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected $item;

    /**
     * @Column(type="float", nullable=true)
     */
    protected $centerLat;

    /**
     * @Column(type="float", nullable=true)
     */
    protected $centerLng;

    /**
     * @OneToMany(
     *     targetEntity="MappingMarker",
     *     mappedBy="mapping",
     *     orphanRemoval=true,
     *     cascade={"persist", "remove"}
     * )
     */
    protected $markers;

    public function __construct()
    {
        $this->markers = new ArrayCollection;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setCenterLat($centerLat)
    {
        $this->centerLat = $centerLat;
    }

    public function getCenterLat()
    {
        return $this->centerLat;
    }

    public function setCenterLng($centerLng)
    {
        $this->centerLng = $centerLng;
    }

    public function getCenterLng()
    {
        return $this->centerLng;
    }

    public function getMarkers()
    {
        return $this->markers;
    }
}
