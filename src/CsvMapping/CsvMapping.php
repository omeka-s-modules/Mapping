<?php
namespace Mapping\CsvMapping;

use CSVImport\Mapping\AbstractMapping;
use Laminas\View\Renderer\PhpRenderer;

class CsvMapping extends AbstractMapping
{
    protected $label = 'Map'; // @translate
    protected $name = 'mapping-module';

    public function getSidebar(PhpRenderer $view)
    {
        return $view->partial('admin/csv-mapping');
    }

    public function processRow(array $row)
    {
        // Reset the data and the map between rows.
        $this->setHasErr(false);
        $json = [
            'o-module-mapping:marker' => [],
            'o-module-mapping:mapping' => [],
        ];

        // Set columns.
        $latMap = isset($this->args['column-map-lat']) ? array_keys($this->args['column-map-lat']) : [];
        $lngMap = isset($this->args['column-map-lng']) ? array_keys($this->args['column-map-lng']) : [];
        $latLngMap = isset($this->args['column-map-latlng']) ? array_keys($this->args['column-map-latlng']) : [];

        $defaultLatMap = isset($this->args['column-default-lat']) ? array_keys($this->args['column-default-lat']) : [];
        $defaultLngMap = isset($this->args['column-default-lng']) ? array_keys($this->args['column-default-lng']) : [];
        $defaultZoomMap = isset($this->args['column-default-zoom']) ? array_keys($this->args['column-default-zoom']) : [];

        $multivalueMap = isset($this->args['column-multivalue']) ? $this->args['column-multivalue'] : [];
        $multivalueSeparator = $this->args['multivalue_separator'];

        // Set default values.
        $markerJson = [];
        $mappingJson = ['o-module-mapping:default_zoom' => 1];

        foreach ($row as $index => $value) {
            if (in_array($index, $latMap)) {
                $markerJson['o-module-mapping:lat'] = $value;
            }
            if (in_array($index, $lngMap)) {
                $markerJson['o-module-mapping:lng'] = $value;
            }
            if (in_array($index, $latLngMap)) {
                if (empty($multivalueMap[$index])) {
                    $latLngs = [$value];
                } else {
                    $latLngs = explode($multivalueSeparator, $value);
                }
                foreach ($latLngs as $latLngString) {
                    $latLng = array_map('trim', explode('/', $latLngString));
                    if (count($latLng) !== 2) {
                        continue;
                    }
                    $json['o-module-mapping:marker'][] = [
                        'o-module-mapping:lat' => $latLng[0],
                        'o-module-mapping:lng' => $latLng[1],
                    ];
                }
            }

            if (in_array($index, $defaultLatMap)) {
                $mappingJson['o-module-mapping:default_lat'] = $value;
            }
            if (in_array($index, $defaultLngMap)) {
                $mappingJson['o-module-mapping:default_lng'] = $value;
            }
            if (in_array($index, $defaultZoomMap)) {
                $mappingJson['o-module-mapping:default_zoom'] = $value;
            }
        }

        if (isset($markerJson['o-module-mapping:lat']) && isset($markerJson['o-module-mapping:lng'])) {
            $json['o-module-mapping:marker'][] = $markerJson;
        }

        $json['o-module-mapping:mapping'] = $mappingJson;
        return $json;
    }
}
