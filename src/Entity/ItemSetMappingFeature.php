<?php
namespace Mapping\Entity;

use LongitudeOne\Spatial\PHP\Types\Geography\GeographyInterface;
use Omeka\Entity\AbstractEntity;
use Omeka\Entity\ItemSet;

/**
 * @Entity
 */
class ItemSetMappingFeature extends AbstractMappingFeature
{
    /**
     * @ManyToOne(
     *     targetEntity="Omeka\Entity\ItemSet",
     * )
     * @JoinColumn(
     *     nullable=false,
     *     onDelete="CASCADE"
     * )
     */
    protected $itemSet;

    public function setItemSet(ItemSet $itemSet)
    {
        $this->itemSet = $itemSet;
    }

    public function getItemSet()
    {
        return $this->itemSet;
    }
}
