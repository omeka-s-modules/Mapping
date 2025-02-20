<?php
namespace Mapping\StaticSiteExport\ResourcePageBlockLayout;

use ArrayObject;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Api\Representation\ItemRepresentation;
use Omeka\Api\Representation\ItemSetRepresentation;
use Omeka\Job\JobInterface;
use StaticSiteExport\ResourcePageBlockLayout\ResourcePageBlockLayoutInterface;

class Mapping implements ResourcePageBlockLayoutInterface
{
    public function getMarkdown(
        JobInterface $job,
        ArrayObject $frontMatter,
        AbstractResourceEntityRepresentation $resource
    ): string {
        if ($resource instanceof ItemRepresentation) {
            $queryKey = 'item_id';
            $page = sprintf('items/%s', $resource->id());
        } elseif ($resource instanceof ItemSetRepresentation) {
            $queryKey = 'item_set_id';
            $page = sprintf('item-sets/%s', $resource->id());
        } else {
            return '';
        }
        // Get all features of this resource.
        $hasFeatures = $job->get('Omeka\ApiManager')
            ->search('mapping_features', [$queryKey => $resource->id(), 'limit' => 0])
            ->getTotalResults();
        if (!$hasFeatures) {
            return '';
        }
        // Set the dependencies and return the mapping shortcode.
        $frontMatter['css'][] = 'vendor/leaflet/leaflet.css';
        $frontMatter['js'][] = 'vendor/leaflet/leaflet.js';
        $frontMatter['css'][] = 'vendor/omeka-mapping/mapping-features.css';
        $frontMatter['js'][] = 'vendor/omeka-mapping/mapping-features.js';
        return sprintf('{{< omeka-mapping-features page="%s" >}}', $page);
    }
}
