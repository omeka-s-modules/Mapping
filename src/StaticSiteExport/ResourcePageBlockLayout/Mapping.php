<?php
namespace Mapping\StaticSiteExport\ResourcePageBlockLayout;

use ArrayObject;
use Mapping\Module;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Api\Representation\ItemRepresentation;
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
        $api = $job->get('Omeka\ApiManager');

        // Get mappings and all features of this item. There can only ever be
        // one "mappings" for one item.
        $mappings = $api->search('mappings', ['item_id' => $resource->id()])->getContent();
        $mappings = $mappings[0] ?? null;
        $features = $api->search('mapping_features', ['item_id' => $resource->id()])->getContent();

        if (!$mappings && !$features) {
            return '';
        }

        // Set the dependencies.
        $frontMatterPage['css'][] = 'vendor/leaflet/leaflet.css';
        $frontMatterPage['css'][] = 'vendor/leaflet.markercluster/MarkerCluster.css';
        $frontMatterPage['css'][] = 'vendor/leaflet.markercluster/MarkerCluster.Default.css';
        $frontMatterPage['css'][] = 'vendor/omeka-mapping/mapping-features.css';
        $frontMatterPage['js'][] = 'vendor/leaflet/leaflet.js';
        $frontMatterPage['js'][] = 'vendor/leaflet.markercluster/leaflet.markercluster-src.js';
        $frontMatterPage['js'][] = 'vendor/omeka-mapping/mapping-features.js';

        // Make the mapping-config.json file.
        $job->makeFile(
            sprintf('content/items/%s/mapping-config.json', $resource->id()),
            json_encode(Module::prepareMappingConfigForStaticSite($mappings))
        );
        // Make the mapping-features.json file.
        $job->makeFile(
            sprintf('content/items/%s/mapping-features.json', $resource->id()),
            json_encode(Module::prepareMappingFeaturesForStaticSite($features))
        );

        // Return the mapping shortcode.
        return sprintf(
            '{{< omeka-mapping-features page="%s" configResource="%s" featuresResource="%s" >}}',
            sprintf('items/%s', $resource->id()),
            'mapping-config.json',
            'mapping-features.json'
        );
    }
}
