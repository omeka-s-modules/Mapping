$(document).ready( function() {

var mappingMaps = $('.mapping-map');

mappingMaps.each(function() {
    var mappingMap = $(this);

    var defaultZoom = mappingMap.data('defaultZoom');
    var defaultLat = mappingMap.data('defaultLat');
    var defaultLng = mappingMap.data('defaultLng');
    var wms = mappingMap.data('wms');
    var markers = mappingMap.data('markers');
    var baseMaps = {
        'Streets': L.tileLayer.provider('OpenStreetMap.Mapnik'),
        'Grayscale': L.tileLayer.provider('OpenStreetMap.BlackAndWhite'),
        'Satellite': L.tileLayer.provider('Esri.WorldImagery'),
        'Terrain': L.tileLayer.provider('Esri.WorldShadedRelief')
    };
    var groupedOverlays = {'Overlays': {
        'No overlay': L.tileLayer.canvas()
    }};
    var map = L.map(this, {
        center: [defaultLat, defaultLng],
        zoom: defaultZoom
    });

    $.each(wms, function(index, data) {
        wms = L.tileLayer.wms(data.base_url, {
            layers: data.layers,
            styles: data.styles,
            format: 'image/png',
            transparent: true,
        }).addTo(map);
        groupedOverlays['Overlays'][data.label] = wms;
    });

    map.addLayer(baseMaps['Streets']);
    L.control.groupedLayers(baseMaps, groupedOverlays, {
        exclusiveGroups: ['Overlays']
    }).addTo(map);
    $.each(groupedOverlays['Overlays'], function(index, data) {
        map.removeLayer(groupedOverlays['Overlays'][index]);
    });
    map.addLayer(groupedOverlays['Overlays']['No overlay']);

    $.each(markers, function(index, data) {
        var latLng = L.latLng(data['o-module-mapping:lat'], data['o-module-mapping:lng']);
        var marker = L.marker(latLng);
        var popupContent = $('.mapping-marker-popup-content[data-marker-id="' + data['o:id'] + '"]');
        if (popupContent.length > 0) {
            popupContent = popupContent.clone().show();
            marker.bindPopup(popupContent[0]);
        }
        marker.addTo(map);
    });
});

});
