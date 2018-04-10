$(document).ready( function() {

var mappingMaps = $('.mapping-map');

mappingMaps.each(function() {
    var mappingMap = $(this);
    var data = mappingMap.data('data');

    // Build the marker feature group.
    var markers = L.markerClusterGroup();
    $.each(mappingMap.data('markers'), function(index, data) {
        var latLng = L.latLng(data['o-module-mapping:lat'], data['o-module-mapping:lng']);
        var marker = L.marker(latLng);
        var popupContent = $('.mapping-marker-popup-content[data-marker-id="' + data['o:id'] + '"]');
        if (popupContent.length > 0) {
            popupContent = popupContent.clone().show();
            marker.bindPopup(popupContent[0]);
        }
        markers.addLayer(marker);
    });

    // Initialize the map, add markers, and set the default view.
    var map = L.map(this, {maxZoom: 18});
    map.addLayer(markers);
    if (data['bounds']) {
        var bounds = data['bounds'].split(',');
        var southWest = [bounds[1], bounds[0]];
        var northEast = [bounds[3], bounds[2]];
        map.fitBounds([southWest, northEast]);
    } else {
        var bounds = markers.getBounds();
        if (bounds.isValid()) {
            map.fitBounds(bounds);
        } else {
            map.setView([20, 0], 2);
        }
    }

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
    $.each(data['wms'], function(index, data) {
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
