<?php
namespace Mapping\StaticSiteExport\NavigationLink;

use ArrayObject;
use Omeka\Job\JobInterface;
use StaticSiteExport\NavigationLink\NavigationLinkInterface;

class mapping implements NavigationLinkInterface
{
    public function setMenuEntry(
        JobInterface $job,
        ArrayObject $menu,
        array $navLink,
        string $id,
        ?string $parentId,
        ?int $weight
    ): void {
        $menu->append([
            'name' => $navLink['data']['label'] ?: $job->translate('Map browse'),
            'identifier' => $id,
            'parent' => $parentId,
            'pageRef' => '/mapping',
            'weight' => $weight,
        ]);
    }
}
