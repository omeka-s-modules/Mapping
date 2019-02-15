var MappingBlock = {
    /**
     * Get markers belonging to an item.
     *
     * @param itemId
     * @param mapMarkers
     * @return []
     */
    getItemMarkers : function(itemId, mapMarkers) {
        var itemMarkers = [];
        $.each(mapMarkers, function(index, data) {
            if (itemId == data['o:item']['o:id']) {
                itemMarkers.push(data);
            }
        });
        return itemMarkers;
    },
    /**
     * Add markers to the map and set the default view.
     *
     * @param map
     * @param mapData
     * @param mapMarkers
     * @param markerClusterGroup
     */
    loadMap : function(map, mapData, mapMarkers, markerClusterGroup) {
        // Add markers to the map.
        markerClusterGroup.clearLayers();
        $.each(mapMarkers, function(index, data) {
            var marker = L.marker(L.latLng(
                data['o-module-mapping:lat'],
                data['o-module-mapping:lng']
            ));
            var popupContent = $('.mapping-marker-popup-content[data-marker-id="' + data['o:id'] + '"]');
            if (popupContent.length > 0) {
                popupContent = popupContent.clone().show();
                marker.bindPopup(popupContent[0]);
            }
            markerClusterGroup.addLayer(marker);
        });
        map.addLayer(markerClusterGroup);
        // Set the default view.
        if (mapData['bounds']) {
            var bounds = mapData['bounds'].split(',');
            var southWest = [bounds[1], bounds[0]];
            var northEast = [bounds[3], bounds[2]];
            map.fitBounds([southWest, northEast]);
        } else {
            var bounds = markerClusterGroup.getBounds();
            if (bounds.isValid()) {
                map.fitBounds(bounds);
            } else {
                map.setView([20, 0], 2);
            }
        }
    }
}

$(document).ready( function() {

$('.mapping-block').each(function() {
    var blockDiv = $(this);
    var mapDiv = blockDiv.children('.mapping-map');
    var timelineDiv = blockDiv.children('.mapping-timeline');

    var mapData = mapDiv.data('data');
    var mapMarkers = mapDiv.data('markers');

    // Initialize the map.
    var map = L.map(mapDiv[0], {maxZoom: 18});
    var markerClusterGroup = L.markerClusterGroup();

    if (timelineDiv.length) {
        // Initialize the timeline.
        var timeline = new TL.Timeline(
            timelineDiv[0],
            timelineDiv.data('data'),
            timelineDiv.data('options')
        );
        mapMarkers = MappingBlock.getItemMarkers(
            timeline.getData(0).unique_id,
            mapDiv.data('markers')
        );
        // Reload the map when an event changes.
        timeline.on('change', function(data) {
            mapMarkers = MappingBlock.getItemMarkers(
                data.unique_id,
                mapDiv.data('markers')
            );
            MappingBlock.loadMap(map, mapData, mapMarkers, markerClusterGroup);
        });
    }

    MappingBlock.loadMap(map, mapData, mapMarkers, markerClusterGroup);

    // Set base map and grouped overlay layers.
    var baseMaps = {
        'Streets': L.tileLayer.provider('OpenStreetMap.Mapnik'),
        'Grayscale': L.tileLayer.provider('OpenStreetMap.BlackAndWhite'),
        'Satellite': L.tileLayer.provider('Esri.WorldImagery'),
        'Terrain': L.tileLayer.provider('Esri.WorldShadedRelief')
    };
    var noOverlayLayer = new L.GridLayer();
    var groupedOverlays = {
        'Overlays': {
            'No overlay': noOverlayLayer,
        },
    };

    // Set and prepare opacity control.
    var opacityControl;
    var handleOpacityControl = function(overlay, label) {
        if (opacityControl) {
            // Only one control at a time.
            map.removeControl(opacityControl);
            opacityControl = null;
        }
        if (overlay !== noOverlayLayer) {
            // The "No overlay" overlay gets no control.
            opacityControl =  new L.Control.Opacity(overlay, label);
            map.addControl(opacityControl);
        }
    };

    // Add base map and grouped WMS overlay layers.
    map.addLayer(baseMaps['Streets']);
    map.addLayer(noOverlayLayer);
    $.each(mapData['wms'], function(index, data) {
        wmsLayer = L.tileLayer.wms(data.base_url, {
            layers: data.layers,
            styles: data.styles,
            format: 'image/png',
            transparent: true,
        });
        if (data.open) {
            // This WMS overlay is open by default.
            map.removeLayer(noOverlayLayer);
            map.addLayer(wmsLayer);
            handleOpacityControl(wmsLayer, data.label);
        }
        groupedOverlays['Overlays'][data.label] = wmsLayer;
    });
    L.control.groupedLayers(baseMaps, groupedOverlays, {
        exclusiveGroups: ['Overlays']
    }).addTo(map);

    // Handle the overlay opacity control.
    map.on('overlayadd', function(e) {
        handleOpacityControl(e.layer, e.name);
    });
});

});
