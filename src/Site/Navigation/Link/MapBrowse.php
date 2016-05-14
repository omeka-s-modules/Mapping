<?php
namespace Mapping\Site\Navigation\Link;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Site\Navigation\Link\LinkInterface;
use Omeka\Stdlib\ErrorStore;

class MapBrowse implements LinkInterface
{
    public function getLabel()
    {
        return 'Map Browse'; // @translate
    }

    public function getFormTemplate()
    {
        return 'common/navigation-link-form/mapping-map-browse';
    }

    public function isValid(array $data, ErrorStore $errorStore)
    {
        if (!isset($data['label'])) {
            $errorStore->addError('o:navigation', 'Invalid navigation: map browse link missing label');
            return false;
        }
        return true;
    }

    public function toZend(array $data, SiteRepresentation $site)
    {
        return [
            'label' => $data['label'],
            'route' => 'site/mapping-map-browse',
            'params' => [
                'site-slug' => $site->slug(),
            ],
        ];
    }

    public function toJstree(array $data, SiteRepresentation $site)
    {
        $label = isset($data['label']) ? $data['label'] : $sitePage->title();
        return [
            'label' => $label,
        ];
    }
}
