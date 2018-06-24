<?php
namespace Mapping\CsvMapping;

use CSVImport\Mapping\AbstractMapping;
use Omeka\Stdlib\Message;
use Zend\View\Renderer\PhpRenderer;

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
        $this->data = [];
        $this->map = [];

        // First, pull in the global settings.
        $this->processGlobalArgs();

        // Prepare the mapping.
        $markerJson = [];
        $mappingJson = ['o-module-mapping:default_zoom' => 1];

        $multivalueMap = isset($this->args['column-multivalue']) ? $this->args['column-multivalue'] : [];
        foreach ($row as $index => $values) {
            if (array_key_exists($index, $multivalueMap) && strlen($multivalueMap[$index])) {
                $values = explode($multivalueMap[$index], $values);
                $values = array_map(function ($v) {
                    return trim($v, "\t\n\r   ");
                }, $values);
            } else {
                $values = [$values];
            }
            $values = array_filter($values, 'strlen');
            if ($values) {
                // Process the cell.

                // There should be one value only when the latitude and the
                // longitude are separated.
                if (isset($this->map['lat'][$index])) {
                    $markerJson['o-module-mapping:lat'] = reset($values);
                }
                if (isset($this->map['lng'][$index])) {
                    $markerJson['o-module-mapping:lng'] = reset($values);
                }
                // Multiple markers can be handled via a combined value.
                if (isset($this->map['latlng'][$index])) {
                    foreach ($values as $value) {
                        $latLng = array_map('trim', explode('/', $value));
                        $markerJson['o-module-mapping:lat'] = $latLng[0];
                        $markerJson['o-module-mapping:lng'] = $latLng[1];
                        $this->data['o-module-mapping:marker'][] = $markerJson;
                    }
                    $markerJson = [];
                }

                if (isset($this->map['default-lat'][$index])) {
                    $mappingJson['o-module-mapping:default_lat'] = reset($value);
                }
                if (isset($this->map['default-lng'][$index])) {
                    $mappingJson['o-module-mapping:default_lng'] = reset($value);
                }
                if (isset($this->map['default-zoom'][$index])) {
                    $mappingJson['o-module-mapping:default_zoom'] = reset($value);
                }

                if (isset($markerJson['o-module-mapping:lat']) && isset($markerJson['o-module-mapping:lng'])) {
                    $this->data['o-module-mapping:marker'][] = $markerJson;
                    $markerJson = [];
                }
            }
        }

        if (!empty($markerJson)) {
            $this->logger->err(new Message('The mapping for the markers is incomplete.')); // @translate
            $this->setHasErr(true);
        }

        // Only one mapping by resource.
        if (count($mappingJson) === 3
            || (count($mappingJson) === 1 && isset($mappingJson['o-module-mapping:default_zoom']))
        ) {
            $this->data['o-module-mapping:mapping'] = $mappingJson;
        } elseif (count($mappingJson) === 2) {
            $this->logger->err(new Message('The mapping is incomplete.')); // @translate
            $this->setHasErr(true);
        }

        return $this->data;
    }

    protected function processGlobalArgs()
    {
        $data = &$this->data;

        // Set columns.
        if (isset($this->args['column-map-lat'])) {
            $this->map['lat'] = $this->args['column-map-lat'];
            $data['o-module-mapping:marker'] = [];
        }
        if (isset($this->args['column-map-lng'])) {
            $this->map['lng'] = $this->args['column-map-lng'];
            $data['o-module-mapping:marker'] = [];
        }
        if (isset($this->args['column-map-latlng'])) {
            $this->map['latlng'] = $this->args['column-map-latlng'];
            $data['o-module-mapping:marker'] = [];
        }

        if (isset($this->args['column-default-lat'])) {
            $this->map['default-lat'] = $this->args['column-default-lat'];
            $data['o-module-mapping:mapping'] = [];
        }
        if (isset($this->args['column-default-lng'])) {
            $this->map['default-lng'] = $this->args['column-default-lng'];
            $data['o-module-mapping:mapping'] = [];
        }
        if (isset($this->args['column-default-zoom'])) {
            $this->map['default-zoom'] = $this->args['column-default-zoom'];
            $data['o-module-mapping:mapping'] = [];
        }

        // No default values currently.
    }
}
