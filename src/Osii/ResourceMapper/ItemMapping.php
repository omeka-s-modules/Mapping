<?php
namespace Mapping\Osii\ResourceMapper;

use Exception;
use Osii\ResourceMapper\AbstractResourceMapper;

class ItemMapping extends AbstractResourceMapper
{
    public function prepareResource(array $remoteResource) : array
    {
        $job = $this->getJob();
        $resourceName = $job->getResourceName($remoteResource);

        if ('items' !== $resourceName) {
            return $remoteResource;
        }

        if (isset($remoteResource['o-module-mapping:mapping'])) {
            try {
                $mapping = $this->getApiOutput($remoteResource['o-module-mapping:mapping']['@id']);
                $remoteResource['o-module-mapping:mapping'] = $mapping;
            } catch (Exception $e) {
                $job->getLogger()->err(sprintf(
                    "Cannot prepare o-module-mapping:mapping data for item: %s\n%s",
                    $remoteResource['@id'],
                    (string) $e,
                ));
            }
        }

        if (isset($remoteResource['o-module-mapping:marker'])) {
            $markers = [];
            foreach ($remoteResource['o-module-mapping:marker'] as $marker) {
                try {
                    $marker = $this->getApiOutput($marker['@id']);
                    $markers[] = $marker;
                } catch (Exception $e) {
                    $job->getLogger()->err(sprintf(
                        "Cannot prepare o-module-mapping:marker data for item: %s\n%s",
                        $remoteResource['@id'],
                        (string) $e,
                    ));
                }
            }
            $remoteResource['o-module-mapping:marker'] = $markers;
        }
        return $remoteResource;
    }

    public function mapResource(array $localResource, array $remoteResource) : array
    {
        $resourceName = $this->getJob()->getResourceName($remoteResource);
        $mappings = $this->getJob()->getMappings();

        if ('items' !== $resourceName) {
            return $remoteResource;
        }

        // There's NO WAY to map mappings and markers because they are their own
        // resources. This means that they would need to cached during snapshot
        // and created during import (like items) so this mapper could map their
        // remote o:id to the local o:id.

        return $localResource;
    }

    protected function getApiOutput($url)
    {
        $job = $this->getJob();
        $client = $job->getApiClient($url);
        $query = [
            'key_identity' => $job->getImportEntity()->getKeyIdentity(),
            'key_credential' => $job->getImportEntity()->getKeyCredential(),
        ];
        return $job->getApiOutput($client, $query);
    }
}
