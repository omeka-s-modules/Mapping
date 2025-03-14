<?php
namespace Mapping\StaticSiteExport\BlockLayout;

use ArrayObject;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Job\JobInterface;
use StaticSiteExport\BlockLayout\BlockLayoutInterface;

class Map implements BlockLayoutInterface
{
    public function getMarkdown(
        JobInterface $job,
        SitePageBlockRepresentation $block,
        ArrayObject $frontMatterPage,
        ArrayObject $frontMatterBlock
    ): string {
        $itemIds = [];
        foreach ($block->attachments() as $attachment) {
            if (!$attachment->item()) {
                continue;
            }
            $itemIds[] = $attachment->item()->id();
        }
        // Set the dependencies.
        $frontMatterPage['css'][] = 'vendor/leaflet/leaflet.css';
        $frontMatterPage['js'][] = 'vendor/leaflet/leaflet.js';
        $frontMatterPage['css'][] = 'vendor/omeka-mapping/mapping-features.css';
        $frontMatterPage['js'][] = 'vendor/omeka-mapping/mapping-features.js';

        $frontMatterBlock['params']['mapping']['ids'] = $itemIds;

        // Return the mapping shortcode.
        return '{{< omeka-mapping-features >}}';
    }
}
