<?php

namespace Mapping\Omeka2Importer;

use Omeka2Importer\Importer\AbstractImporter;

class GeolocationImporter extends AbstractImporter
{
    public function import($itemData, $resourceJson)
    {
        $logger = $this->getServiceLocator()->get('Omeka\Logger');
        $geolocationId = $itemData['extended_resources']['geolocations']['id'];
        if (empty($geolocationId)) {
            return $resourceJson;
        }
        $response = $this->client->geolocations->get($geolocationId);
        $geolocationsData = json_decode($response->getBody(), true);
        $resourceJson['o-module-mapping:marker'][] = [
            'o-module-mapping:lat' => $geolocationsData['latitude'],
            'o-module-mapping:lng' => $geolocationsData['longitude'],

        ];
        return $resourceJson;
    }
}
