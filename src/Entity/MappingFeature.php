<?php
namespace Mapping\Entity;

use LongitudeOne\Spatial\PHP\Types\Geography\GeographyInterface;
use Omeka\Entity\AbstractEntity;
use Omeka\Entity\Item;
use Omeka\Entity\Media;

/**
 * @Entity
 */
class MappingFeature extends AbstractMappingFeature
{
    /**
     * @ManyToOne(
     *     targetEntity="Omeka\Entity\Item",
     * )
     * @JoinColumn(
     *     nullable=false,
     *     onDelete="CASCADE"
     * )
     */
    protected $item;

    /**
     * @ManyToOne(
     *     targetEntity="Omeka\Entity\Media",
     * )
     * @JoinColumn(
     *     nullable=true,
     *     onDelete="SET NULL"
     * )
     */
    protected $media;

    public function setItem(Item $item)
    {
        $this->item = $item;
    }

    public function getItem()
    {
        return $this->item;
    }

    public function setMedia(?Media $media)
    {
        $this->media = $media;
    }

    public function getMedia()
    {
        return $this->media;
    }
}
