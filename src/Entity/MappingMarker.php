<?php
namespace Omeka\Entity;

use Omeka\Entity\AbstractEntity;

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

    /**
     * @ManyToOne(targetEntity="Mapping", inversedBy="markers")
     * @JoinColumn(nullable=false)
     */
    protected $mapping;

    public function getId()
    {
        return $this->id;
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
        $this->label = $label;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function getMapping()
    {
        return $this->mapping;
    }
}
