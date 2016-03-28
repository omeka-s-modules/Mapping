$(document).ready( function() {

var mappingMap = $('#mapping-map');
var markers = mappingMap.data('markers');
var markerMedia = mappingMap.data('markerMedia');
var map = L.map('mapping-map').setView([0, 0], 1);
var baseMaps = {
    'Streets': L.tileLayer.provider('OpenStreetMap.Mapnik'),
    'Grayscale': L.tileLayer.provider('OpenStreetMap.BlackAndWhite'),
    'Satellite': L.tileLayer.provider('Esri.WorldImagery'),
    'Terrain': L.tileLayer.provider('Esri.WorldShadedRelief')
};
var layerControl = L.control.layers(baseMaps);

map.addLayer(baseMaps['Streets']);
map.addControl(layerControl);

$.each(markers, function(index, data) {
    var latLng = L.latLng(data['o-module-mapping:lat'], data['o-module-mapping:lng']);
    var marker = L.marker(latLng);
    var popupContent = $('.template.mapping-marker-popup-content').clone().removeClass('template');
    if (data['o-module-mapping:label']) {
        popupContent.find('.mapping-marker-popup-label').html(data['o-module-mapping:label']);
    }
    if (data['o:media']) {
        var mediaThumbnailUrl = markerMedia[data['o:id']]['o:thumbnail_urls']['medium'];
        popupContent.find('.mapping-marker-popup-image').html($('<img>', {src: mediaThumbnailUrl}));
    }
    marker.bindPopup(popupContent[0]);
    marker.addTo(map);

});

// Switching sections changes map dimensions, so make the necessary adjustments.
$('a[href="#mapping-section"], #mapping-legend').on('click', function(e) {
    map.invalidateSize();
});

});
