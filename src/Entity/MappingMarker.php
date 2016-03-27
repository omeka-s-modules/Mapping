<?php
namespace Mapping\Entity;

use Omeka\Entity\AbstractEntity;
use Omeka\Entity\Item;
use Omeka\Entity\Media;

/**
 * @Entity
 */
class MappingMarker extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Omeka\Entity\Item")
     * @JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected $item;

    /**
     * @ManyToOne(targetEntity="Omeka\Entity\Media")
     * @JoinColumn(nullable=true, onDelete="SET NULL")
     */
    protected $media;

    /**
     * @Column(type="float")
     */
    protected $lat;

    /**
     * @Column(type="float")
     */
    protected $lng;

    /**
     * @Column(nullable=true)
     */
    protected $label;

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

    public function setMedia(Media $media = null)
    {
        $this->media = $media;
    }

    public function getMedia()
    {
        return $this->media;
    }

    public function setLat($lat)
    {
        $this->lat = $lat;
    }

    public function getLat()
    {
        return $this->lat;
    }

    public function setLng($lng)
    {
        $this->lng = $lng;
    }

    public function getLng()
    {
        return $this->lng;
    }

    public function setLabel($label)
    {
        $this->label = '' === trim($label) ? null : $label;
    }

    public function getLabel()
    {
        return $this->label;
    }
}
