<?php
namespace Mapping\Site\Navigation\Link;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Site\Navigation\Link\AbstractLink;
use Omeka\Stdlib\ErrorStore;

class MapBrowse extends AbstractLink
{
    public function getLabel()
    {
        return 'Map Browse';
    }

    public function isValid(array $data, ErrorStore $errorStore)
    {
        if (!isset($data['label'])) {
            $errorStore->addError('o:navigation', 'Invalid navigation: map browse link missing label');
            return false;
        }
        return true;
    }

    public function getForm(array $data, SiteRepresentation $site)
    {
        $escape = $this->getViewHelper('escapeHtml');
        $label = isset($data['label']) ? $data['label'] : $this->getLabel();
        $query = isset($data['query']) ? $data['query'] : null;
        return '<label>Type <input type="text" value="' . $escape($this->getLabel()) . '" disabled></label>'
            . '<label>Label <input type="text" data-name="label" value="' . $escape($label) . '"></label>';
    }

    public function toZend(array $data, SiteRepresentation $site)
    {
        return [
            'label' => $data['label'],
            'route' => 'site/mapping',
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
