$(document).ready( function() {

var mappingMaps = $('.mapping-map');

mappingMaps.each(function() {
    var mappingMap = $(this);

    // Initialize the map with default view.
    var defaultZoom = mappingMap.data('defaultZoom');
    var defaultLat = mappingMap.data('defaultLat');
    var defaultLng = mappingMap.data('defaultLng');
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

    // Add base map and WMS overlay layers to map.
    var baseMaps = {
        'Streets': L.tileLayer.provider('OpenStreetMap.Mapnik'),
        'Grayscale': L.tileLayer.provider('OpenStreetMap.BlackAndWhite'),
        'Satellite': L.tileLayer.provider('Esri.WorldImagery'),
        'Terrain': L.tileLayer.provider('Esri.WorldShadedRelief')
    };
    var groupedOverlays = {
        'Overlays': {
            // Set an empty canvas for a "no overlay" option.
            'No overlay': L.tileLayer.canvas()
        }
    };
    $.each(mappingMap.data('wms'), function(index, data) {
        wms = L.tileLayer.wms(data.base_url, {
            layers: data.layers,
            styles: data.styles,
            format: 'image/png',
            transparent: true,
        }).addTo(map);
        groupedOverlays['Overlays'][data.label] = wms;
    });
    L.control.groupedLayers(baseMaps, groupedOverlays, {
        exclusiveGroups: ['Overlays']
    }).addTo(map);
    // Remove grouped overlays from the map so the user can select one at a time.
    $.each(groupedOverlays['Overlays'], function(index, data) {
        map.removeLayer(data);
    });
    map.addLayer(baseMaps['Streets']);
    map.addLayer(groupedOverlays['Overlays']['No overlay']);
});

});
