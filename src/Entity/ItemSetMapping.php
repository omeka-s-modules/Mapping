<?php
namespace Mapping\Entity;

use Omeka\Entity\AbstractEntity;
use Omeka\Entity\ItemSet;

/**
 * @Entity
 */
class ItemSetMapping extends AbstractMapping
{
    /**
     * @OneToOne(targetEntity="Omeka\Entity\ItemSet")
     * @JoinColumn(nullable=false, onDelete="CASCADE")
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
