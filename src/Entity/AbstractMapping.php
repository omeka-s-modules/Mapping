<?php
namespace Mapping\Entity;

use Omeka\Entity\AbstractEntity;

/**
 * @MappedSuperclass
 */
abstract class AbstractMapping extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @Column(type="string", nullable=true)
     */
    protected $bounds;

    public function getId()
    {
        return $this->id;
    }

    public function setBounds($bounds)
    {
        $this->bounds = $bounds;
    }

    public function getBounds()
    {
        return $this->bounds;
    }
}
