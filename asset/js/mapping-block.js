$(document).ready( function() {

var mappingMaps = $('.mapping-map');

mappingMaps.each(function() {
    var mappingMap = $(this);
    var data = mappingMap.data('data');

    // Initialize the map with default view.
    var defaultZoom = data['default_view']['zoom'];
    var defaultLat = data['default_view']['lat'];
    var defaultLng = data['default_view']['lng'];
    if (!defaultZoom || !defaultLat || !defaultLng) {
        defaultZoom = 1;
        defaultLat = 0;
        defaultLng = 0;
    }
    var map = L.map(this, {
        center: [defaultLat, defaultLng],
        zoom: defaultZoom
    });

    // Add markers to map.
    $.each(mappingMap.data('markers'), function(index, data) {
        var latLng = L.latLng(data['o-module-mapping:lat'], data['o-module-mapping:lng']);
        var marker = L.marker(latLng);
        var popupContent = $('.mapping-marker-popup-content[data-marker-id="' + data['o:id'] + '"]');
        if (popupContent.length > 0) {
            popupContent = popupContent.clone().show();
            marker.bindPopup(popupContent[0]);
        }
        marker.addTo(map);
    });

    // Add base map and grouped WMS overlay layers to the map.
    var baseMaps = {
        'Streets': L.tileLayer.provider('OpenStreetMap.Mapnik'),
        'Grayscale': L.tileLayer.provider('OpenStreetMap.BlackAndWhite'),
        'Satellite': L.tileLayer.provider('Esri.WorldImagery'),
        'Terrain': L.tileLayer.provider('Esri.WorldShadedRelief')
    };
    var noOverlayLayer = L.tileLayer.canvas();
    var groupedOverlays = {
        'Overlays': {
            'No overlay': noOverlayLayer,
        },
    };
    map.addLayer(baseMaps['Streets']);
    map.addLayer(noOverlayLayer);
    $.each(data['wms'], function(index, data) {
        wms = L.tileLayer.wms(data.base_url, {
            layers: data.layers,
            styles: data.styles,
            format: 'image/png',
            transparent: true,
        });
        groupedOverlays['Overlays'][data.label] = wms;
    });
    L.control.groupedLayers(baseMaps, groupedOverlays, {
        exclusiveGroups: ['Overlays']
    }).addTo(map);

    // Handle the overlay opacity control.
    var opacityControl;
    map.on('overlayadd', function(e) {
        if (opacityControl) {
            map.removeControl(opacityControl);
            opacityControl = null;
        }
        if (e.layer !== noOverlayLayer) {
            opacityControl =  new L.Control.Opacity(e.layer, e.name);
            map.addControl(opacityControl);
        }
    });
});

});
