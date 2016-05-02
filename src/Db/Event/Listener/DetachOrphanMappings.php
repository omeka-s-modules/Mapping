<?php
namespace Mapping\Db\Event\Listener;

use Doctrine\ORM\Event\PreFlushEventArgs;
use Mapping\Entity\Mapping;

/**
 * Automatically detach mappings that reference unknown items.
 */
class DetachOrphanMappings
{
    /**
     * Detach all Mapping entities that reference Items not currently in the entity manager.
     *
     * @param PreFlushEventArgs $event
     */
    public function preFlush(PreFlushEventArgs $event)
    {
        $em = $event->getEntityManager();
        $uow = $em->getUnitOfWork();
        $identityMap = $uow->getIdentityMap();
        if (!isset($identityMap[Mapping::class])) {
            return;
        }
        foreach ($identityMap[Mapping::class] as $mapping) {
            if (!$em->contains($mapping->getItem())) {
                $em->detach($mapping);
            }
        }
    }
}
