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
            'o-module-mapping:wms_base_url' => $this->wmsBaseUrl(),
            'o-module-mapping:wms_layers' => $this->wmsLayers(),
            'o-module-mapping:wms_styles' => $this->wmsStyles(),
            'o-module-mapping:wms_label' => $this->wmsLabel(),
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

    public function wmsBaseUrl()
    {
        return $this->resource->getWmsBaseUrl();
    }

    public function wmsLayers()
    {
        return $this->resource->getWmsLayers();
    }

    public function wmsStyles()
    {
        return $this->resource->getWmsStyles();
    }

    public function wmsLabel()
    {
        return $this->resource->getWmsLabel();
    }
}
