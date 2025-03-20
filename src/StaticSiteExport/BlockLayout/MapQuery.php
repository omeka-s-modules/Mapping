<?php
namespace Mapping\StaticSiteExport\BlockLayout;

use ArrayObject;
use Mapping\Module;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Job\JobInterface;
use StaticSiteExport\BlockLayout\BlockLayoutInterface;

class MapQuery implements BlockLayoutInterface
{
    public function getMarkdown(
        JobInterface $job,
        SitePageBlockRepresentation $block,
        ArrayObject $frontMatterPage,
        ArrayObject $frontMatterBlock
    ): string {
        $api = $job->get('Omeka\ApiManager');

        // Set the dependencies to the page's front matter.
        $frontMatterPage['css'][] = 'vendor/leaflet/leaflet.css';
        $frontMatterPage['js'][] = 'vendor/leaflet/leaflet.js';
        $frontMatterPage['css'][] = 'vendor/omeka-mapping/mapping-features.css';
        $frontMatterPage['js'][] = 'vendor/omeka-mapping/mapping-features.js';

        // Make the mapping-features.json file.
        $blockData = $block->data();
        parse_str($data['query'], $itemsQuery);
        $itemsQuery['site_id'] = $block->page()->site()->id();
        $itemsQuery['has_features'] = true;
        $itemIds = $api->search('items', $itemsQuery, ['returnScalar' => 'id'])->getContent();
        $featuresQuery = [
            'item_id' => $itemIds ? $itemIds : 0,
        ];
        $features = $api->search('mapping_features', $featuresQuery)->getContent();
        $job->makeFile(
            sprintf('content/pages/%s/mapping-features-%s.json', $block->page()->slug(), $block->id()),
            json_encode(Module::getMappingFeaturesForStaticSiteExport($features))
        );

        // Return the mapping shortcode.
        return sprintf(
            '{{< omeka-mapping-features page="%s" resource="%s">}}',
            sprintf('pages/%s', $block->page()->slug()),
            sprintf('mapping-features-%s.json', $block->id())
        );
    }
}
