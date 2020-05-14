<?php
namespace Mapping\Site\Navigation\Link;

use Mapping\Module;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Site\Navigation\Link\LinkInterface;
use Omeka\Stdlib\ErrorStore;

class MapBrowse implements LinkInterface
{
    public function getName()
    {
        return 'Map Browse'; // @translate
    }

    public function getFormTemplate()
    {
        return 'common/navigation-link-form/mapping-map-browse';
    }

    public function isValid(array $data, ErrorStore $errorStore)
    {
        return true;
    }

    public function getLabel(array $data, SiteRepresentation $site)
    {
        return isset($data['label']) && '' !== trim($data['label'])
            ? $data['label'] : $this->getName();
    }

    public function toZend(array $data, SiteRepresentation $site)
    {
        return [
            'route' => 'site/mapping-map-browse',
            'params' => [
                'site-slug' => $site->slug(),
            ],
            'query' => ['basemap_provider' => self::getBasemapProvider($data)],
        ];
    }

    public function toJstree(array $data, SiteRepresentation $site)
    {
        return [
            'label' => $data['label'],
            'basemap_provider' => self::getBasemapProvider($data),
        ];
    }

    public static function getBasemapProvider(array $data)
    {
        $basemapProvider = Module::DEFAULT_BASEMAP_PROVIDER;
        if (isset($data['basemap_provider']) && in_array($data['basemap_provider'], Module::BASEMAP_PROVIDERS)) {
            $basemapProvider = $data['basemap_provider'];
        }
        return $basemapProvider;
    }
}
