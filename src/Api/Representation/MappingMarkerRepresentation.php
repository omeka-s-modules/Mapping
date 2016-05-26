<?php
namespace Mapping\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class MappingMarkerRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLdType()
    {
        return 'o-module-mapping:Marker';
    }

    public function getJsonLd()
    {
        return [
            'o:item' => $this->item()->getReference(),
            'o:media' => $this->resource->getMedia() ? $this->media()->getReference() : null,
            'o-module-mapping:lat' => $this->lat(),
            'o-module-mapping:lng' => $this->lng(),
            'o-module-mapping:label' => $this->label(),
        ];
    }

    public function item()
    {
        return $this->getAdapter('items')
            ->getRepresentation($this->resource->getItem());
    }

    public function media()
    {
        return $this->getAdapter('media')
            ->getRepresentation($this->resource->getMedia());
    }

    public function lat()
    {
        return $this->resource->getLat();
    }

    public function lng()
    {
        return $this->resource->getLng();
    }

    public function label()
    {
        return $this->resource->getLabel();
    }
}
