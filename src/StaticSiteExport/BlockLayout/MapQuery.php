<?php
namespace Mapping\StaticSiteExport\BlockLayout;

use ArrayObject;
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
        // Set the dependencies to the page's front matter.
        $frontMatterPage['css'][] = 'vendor/leaflet/leaflet.css';
        $frontMatterPage['js'][] = 'vendor/leaflet/leaflet.js';
        $frontMatterPage['css'][] = 'vendor/omeka-mapping/mapping-features.css';
        $frontMatterPage['js'][] = 'vendor/omeka-mapping/mapping-features.js';

        // Set the item IDs to the block's front matter.
        $api = $job->get('Omeka\ApiManager');
        $blockData = $block->data();
        parse_str($data['query'], $itemsQuery);
        $itemsQuery['site_id'] = $block->page()->site()->id();
        $itemsQuery['has_features'] = true;
        $itemIds = $api->search('items', $itemsQuery, ['returnScalar' => 'id'])->getContent();
        $frontMatterBlock['params']['mapping']['ids'] = array_values($itemIds);

        // Return the mapping shortcode.
        return '{{< omeka-mapping-features >}}';
    }
}
