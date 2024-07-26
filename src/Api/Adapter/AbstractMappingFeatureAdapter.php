<?php
namespace Mapping\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use LongitudeOne\Spatial\Exception\InvalidValueException;
use LongitudeOne\Spatial\PHP\Types\Geography;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Adapter\AbstractResourceEntityAdapter;
use Omeka\Api\Adapter\ItemAdapter;
use Omeka\Api\Adapter\ItemSetAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;

abstract class AbstractMappingFeatureAdapter extends AbstractEntityAdapter
{
    public function hydrate(Request $request, EntityInterface $entity, ErrorStore $errorStore)
    {
        $data = $request->getContent();
        if ($this->shouldHydrate($request, 'o:label')) {
            $entity->setLabel($request->getValue('o:label'));
        } elseif ($this->shouldHydrate($request, 'o-module-mapping:label')) {
            // Hydrate from legacy (pre-2.0) label key.
            $entity->setLabel($request->getValue('o-module-mapping:label'));
        }
        if ($this->shouldHydrate($request, 'o-module-mapping:geography-coordinates')) {
            $geographyType = $data['o-module-mapping:geography-type'] ?? null;
            $geographyCoordinates = $data['o-module-mapping:geography-coordinates'] ?? null;
            if (is_string($geographyCoordinates)) {
                $geographyCoordinates = json_decode($geographyCoordinates, true);
            }
            try {
                switch (strtolower($geographyType)) {
                    case 'point':
                        $geography = new Geography\Point($geographyCoordinates);
                        break;
                    case 'linestring':
                        $geography = new Geography\LineString($geographyCoordinates);
                        break;
                    case 'polygon':
                        $geography = new Geography\Polygon($geographyCoordinates);
                        break;
                    default:
                        throw new InvalidValueException('Invalid geography type');
                }
                $entity->setGeography($geography);
            } catch (InvalidValueException $e) {
                $errorStore->addError('o-module-mapping:geography-type', $e->getMessage());
            }
        } elseif ($this->shouldHydrate($request, 'o-module-mapping:lng') && $this->shouldHydrate($request, 'o-module-mapping:lat')) {
            // Hydrate from legacy (pre-2.0) latitude and longitude keys.
            $point = new Geography\Point(
                $request->getValue('o-module-mapping:lng'),
                $request->getValue('o-module-mapping:lat')
            );
            $entity->setGeography($point);
        }
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        if (!$entity->getGeography()) {
            $errorStore->addError('o:item', 'A Mapping feature must have a geography.');
        }
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
        $address = $query['address'] ?? null;
        $radius = $query['radius'] ?? null;
        $radiusUnit = $query['radius_unit'] ?? null;
        if (isset($address) && '' !== trim($address) && isset($radius) && is_numeric($radius)) {
            $this->buildGeographicLocationQuery($qb, $address, $radius, $radiusUnit);
        }
    }

    /**
     * Build a geographic location query.
     *
     * @param QueryBuilder $qb
     * @param string $address A geographic address
     * @param string $radius The radius within which to search
     * @param string $radiusUnit The radius unit, "km" or "mile"
     * @param AbstractResourceEntityAdapter|null $resourceEntityAdapter The adapter, if searching items or item sets
     * @return bool Whether an address was found
     */
    public function buildGeographicLocationQuery($qb, $address, $radius, $radiusUnit, AbstractResourceEntityAdapter $resourceEntityAdapter = null)
    {
        // Get the address' latitude and longitude from OpenStreetMap.
        $client = $this->getServiceLocator()->get('Omeka\HttpClient')
            ->setUri('http://nominatim.openstreetmap.org/search')
            ->setParameterGet([
                'q' => $address,
                'format' => 'json',
            ]);
        $response = $client->send();

        $addressFound = false;
        if ($response->isSuccess()) {
            $results = json_decode($response->getBody(), true);
            if (isset($results[0]['lat']) && isset($results[0]['lon'])) {

                // Address coordinates were found.
                $addressFound = true;

                // The adapter and alias depend on whether an item adapter was
                // passed. If not, assume this is a direct feature search. If so,
                // assume this is an indirect item search.
                $adapter = $this;
                $mappingFeatureAlias = 'omeka_root';
                if ($resourceEntityAdapter) {
                    $adapter = $resourceEntityAdapter;
                    $mappingFeatureAlias = $resourceEntityAdapter->createAlias();
                    if ($resourceEntityAdapter instanceof ItemAdapter) {
                        $rootTable = 'item';
                    } elseif ($resourceEntityAdapter instanceof ItemSetAdapter) {
                        $rootTable = 'item_set';
                    }
                    $qb->innerJoin(
                        'Mapping\Entity\MappingFeature', $mappingFeatureAlias,
                        'WITH', "$mappingFeatureAlias.$rootTable = omeka_root.id"
                    );
                }
                // The buffer degree is the radius divided by the circumference
                // of the earth divided by 360. This formula does not correct
                // for latitude. The further away the center point is from the
                // equator, the less accurate the results.
                $bufferDegree = 'miles' === $radiusUnit ? $radius / 69.170725 : $radius / 111.319491667;
                $buffercCenterPoint = sprintf('POINT(%s %s)', $results[0]['lon'], $results[0]['lat']);
                $dql = sprintf(
                    'ST_Intersects(ST_Buffer(ST_GeomFromText(%s), %s), %s.geography) = 1',
                    $adapter->createNamedParameter($qb, $buffercCenterPoint),
                    $adapter->createNamedParameter($qb, $bufferDegree),
                    $mappingFeatureAlias
                );
                $qb->andWhere($dql);
            }
        }
        if (!$addressFound) {
            // If no address is found there are no results. This WHERE
            // statement will always have no results.
            $qb->andWhere(sprintf('%s.id = 0', 'omeka_root'));
        }
        return $addressFound;
    }
}
