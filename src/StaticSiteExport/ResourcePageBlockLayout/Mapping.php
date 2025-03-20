<?php
namespace Mapping\StaticSiteExport\ResourcePageBlockLayout;

use ArrayObject;
use Mapping\Module;
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

        // Make the mapping-features.json file.
        $features = $job->get('Omeka\ApiManager')
            ->search('mapping_features', ['item_id' => $resource->id()])
            ->getContent();
        $job->makeFile(
            sprintf('content/items/%s/mapping-features.json', $resource->id()),
            json_encode(Module::getMappingFeaturesForStaticSiteExport($features))
        );

        // Return the mapping shortcode.
        return sprintf(
            '{{< omeka-mapping-features page="%s" resource="mapping-features.json">}}',
            sprintf('items/%s', $resource->id())
        );
    }
}
