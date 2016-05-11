<?php
namespace Mapping\CsvMapping;

use CSVImport\Mapping\AbstractMapping;

class CsvMapping extends AbstractMapping
{
    public static function getLabel()
    {
        return 'Map';
    }
    
    public static function getName()
    {
        return 'mapping-plugin';
    }
    
    public static function getSidebar($view)
    {
        return $view->partial('admin/csv-mapping');
    }
    
    public function processRow($row)
    {
        $json = ['o-module-mapping:marker' => []];
        $latMap = isset($this->args['column-map-lat']) ? array_keys($this->args['column-map-lat']) : [];
        $lngMap = isset($this->args['column-map-lng']) ? array_keys($this->args['column-map-lng']) : [];
        $latLngMap = isset($this->args['column-map-latlng']) ? array_keys($this->args['column-map-latlng']) : [];
        
        $markerJson = [];
        foreach($row as $index => $value) {
            $value = trim($value);
            
            if(in_array($index, $latMap)) {
                $markerJson['o-module-mapping:lat'] = $value;
            }
            
            if(in_array($index, $lngMap)) {
                $markerJson['o-module-mapping:lng'] = $value;
            }
            
            if(in_array($index, $latLngMap)) {
                $latLng = explode('/', $value);
                $markerJson['o-module-mapping:lat'] = trim($latLng[0]);
                $markerJson['o-module-mapping:lng'] = trim($latLng[1]);
            }
            
        }
        if (isset($markerJson['o-module-mapping:lat']) && isset($markerJson['o-module-mapping:lng'])) {
            $json['o-module-mapping:marker'][] = $markerJson;
            return $json;
        }
        
        return [];
    }
}
