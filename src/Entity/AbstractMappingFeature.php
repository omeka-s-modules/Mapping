<?php
namespace Mapping\Entity;

use LongitudeOne\Spatial\PHP\Types\Geography\GeographyInterface;
use Omeka\Entity\AbstractEntity;

/**
 * @MappedSuperclass
 */
abstract class AbstractMappingFeature extends AbstractEntity
{
    /**
     * @Id
     * @Column(
     *     type="integer",
     *     options={
     *         "unsigned"=true
     *     }
     * )
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Column(
     *     nullable=true
     * )
     */
    protected $label;

    /**
     * @Column(
     *     type="geography"
     * )
     */
    protected $geography;

    public function getId()
    {
        return $this->id;
    }

    public function setLabel(?string $label)
    {
        $this->label = is_string($label) && '' === trim($label) ? null : $label;
    }

    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Get geography.
     *
     * @return GeographyInterface
     */
    public function getGeography()
    {
        return $this->geography;
    }

    /**
     * Set geography.
     *
     * @param GeographyInterface $geography Geography to set
     */
    public function setGeography(GeographyInterface $geography)
    {
        $this->geography = $geography;
    }
}
