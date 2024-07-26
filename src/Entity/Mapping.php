<?php
namespace Mapping\Entity;

use Omeka\Entity\Item;

/**
 * @Entity
 */
class Mapping extends AbstractMapping
{
    /**
     * @OneToOne(targetEntity="Omeka\Entity\Item")
     * @JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected $item;

    public function setItem(Item $item)
    {
        $this->item = $item;
    }

    public function getItem()
    {
        return $this->item;
    }
}
