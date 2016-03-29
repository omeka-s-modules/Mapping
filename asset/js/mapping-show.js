$(document).ready( function() {

var mappingMap = $('#mapping-map');
var map = L.map('mapping-map').setView([0, 0], 1);
var baseMaps = {
    'Streets': L.tileLayer.provider('OpenStreetMap.Mapnik'),
    'Grayscale': L.tileLayer.provider('OpenStreetMap.BlackAndWhite'),
    'Satellite': L.tileLayer.provider('Esri.WorldImagery'),
    'Terrain': L.tileLayer.provider('Esri.WorldShadedRelief')
};
var drawnItems = new L.FeatureGroup();
var layerControl = L.control.layers(baseMaps);

map.addLayer(baseMaps['Streets']);
map.addLayer(drawnItems);
map.addControl(layerControl);

$.each(mappingMap.data('markers'), function(index, data) {
    var latLng = L.latLng(data['o-module-mapping:lat'], data['o-module-mapping:lng']);
    var marker = L.marker(latLng);
    var popupContent = $('.template.mapping-marker-popup-content[data-marker-id="' + data['o:id'] + '"]')
        .clone().removeClass('template');
    marker.bindPopup(popupContent[0]);
    drawnItems.addLayer(marker);
});

// Switching sections changes map dimensions, so make the necessary adjustments.
$('a[href="#mapping-section"], #mapping-legend').on('click', function(e) {
    map.invalidateSize();
});

});
