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
        AbstractResourceEntityRepresentation $resource,
        ArrayObject $frontMatterPage,
        ArrayObject $frontMatterBlock
    ): string {
        if (!($resource instanceof ItemRepresentation)) {
            return '';
        }
        // Get all features of this resource.
        $hasFeatures = $job->get('Omeka\ApiManager')
            ->search('mapping_features', ['item_id' => $resource->id(), 'limit' => 0])
            ->getTotalResults();
        if (!$hasFeatures) {
            return '';
        }
        // Set the dependencies.
        $frontMatterPage['css'][] = 'vendor/leaflet/leaflet.css';
        $frontMatterPage['js'][] = 'vendor/leaflet/leaflet.js';
        $frontMatterPage['css'][] = 'vendor/omeka-mapping/mapping-features.css';
        $frontMatterPage['js'][] = 'vendor/omeka-mapping/mapping-features.js';

        $frontMatterBlock['params']['mapping']['ids'] = [$resource->id()];

        // Return the mapping shortcode.
        return '{{< omeka-mapping-features >}}';
    }
}
