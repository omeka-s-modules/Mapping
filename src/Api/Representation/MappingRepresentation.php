<?php
namespace Mapping\Api\Representation;

use Omeka\Api\Representation\AbstractEntityRepresentation;

class MappingRepresentation extends AbstractEntityRepresentation
{
    public function getJsonLdType()
    {
        return 'o-module-mapping:Map';
    }

    public function getJsonLd()
    {
        $this->addTermDefinitionToContext('o-module-mapping', 'http://omeka.org/s/vocabs/module/mapping#');
        return [
            'o:item' => $this->item()->getReference(),
            'o-module-mapping:default_zoom' => $this->defaultZoom(),
            'o-module-mapping:default_lat' => $this->defaultLat(),
            'o-module-mapping:default_lng' => $this->defaultLng(),
        ];
    }

    public function item()
    {
        return $this->getAdapter('items')
            ->getRepresentation($this->resource->getItem());
    }

    public function defaultZoom()
    {
        return $this->resource->getDefaultZoom();
    }

    public function defaultLat()
    {
        return $this->resource->getDefaultLat();
    }

    public function defaultLng()
    {
        return $this->resource->getDefaultLng();
    }
}
