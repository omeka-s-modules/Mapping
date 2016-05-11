$(document).ready( function() {

var mappingMap = $('#mapping-map');
var mappingData = mappingMap.data('mapping');
var markersData = mappingMap.data('markers');

// Initialize the map and set default view.
var map = L.map('mapping-map');
var mapDefaultCenter = [0, 0];
var mapDefaultZoom = 1;
var noInitialDefaultView = false;
if (mappingData
    && mappingData['o-module-mapping:default_lat'] !== null
    && mappingData['o-module-mapping:default_lng'] !== null
    && mappingData['o-module-mapping:default_zoom'] !== null
) {
    mapDefaultCenter = [
        mappingData['o-module-mapping:default_lat'],
        mappingData['o-module-mapping:default_lng']
    ];
    mapDefaultZoom = mappingData['o-module-mapping:default_zoom'];
} else {
    noInitialDefaultView = true;
}

var baseMaps = {
    'Streets': L.tileLayer.provider('OpenStreetMap.Mapnik'),
    'Grayscale': L.tileLayer.provider('OpenStreetMap.BlackAndWhite'),
    'Satellite': L.tileLayer.provider('Esri.WorldImagery'),
    'Terrain': L.tileLayer.provider('Esri.WorldShadedRelief')
};
var markers = new L.FeatureGroup();
var layerControl = new L.Control.Layers(baseMaps);
var fitBoundsControl = new L.Control.FitBounds(markers);

$.each(markersData, function(index, data) {
    var latLng = L.latLng(data['o-module-mapping:lat'], data['o-module-mapping:lng']);
    var marker = L.marker(latLng);
    var popupContent = $('.mapping-marker-popup-content[data-marker-id="' + data['o:id'] + '"]');
    if (popupContent.length > 0) {
        popupContent = popupContent.clone().show();
        marker.bindPopup(popupContent[0]);
    }
    markers.addLayer(marker);
});

map.addLayer(baseMaps['Streets']);
map.addLayer(markers);
map.addControl(layerControl);
map.addControl(fitBoundsControl);

// Switching sections changes map dimensions, so make the necessary adjustments.
$('#mapping-section').one('o:section-opened', function(e) {
    map.invalidateSize();
    if (noInitialDefaultView) {
        var bounds = markers.getBounds();
        if (bounds.isValid()) {
            map.fitBounds(bounds);
        }
    } else {
        map.setView(mapDefaultCenter, mapDefaultZoom);
    }
});

});
