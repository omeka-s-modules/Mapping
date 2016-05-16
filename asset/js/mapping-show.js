$(document).ready( function() {

var mappingMap = $('#mapping-map');
var mappingData = mappingMap.data('mapping');

var map = L.map('mapping-map');
var center = [0, 0];
var zoom = 1;
var noDefaultView = false;
var markers = new L.FeatureGroup();
var baseMaps = {
    'Streets': L.tileLayer.provider('OpenStreetMap.Mapnik'),
    'Grayscale': L.tileLayer.provider('OpenStreetMap.BlackAndWhite'),
    'Satellite': L.tileLayer.provider('Esri.WorldImagery'),
    'Terrain': L.tileLayer.provider('Esri.WorldShadedRelief')
};

if (mappingData
    && mappingData['o-module-mapping:default_lat'] !== null
    && mappingData['o-module-mapping:default_lng'] !== null
    && mappingData['o-module-mapping:default_zoom'] !== null
) {
    center = [
        mappingData['o-module-mapping:default_lat'],
        mappingData['o-module-mapping:default_lng']
    ];
    zoom = mappingData['o-module-mapping:default_zoom'];
} else {
    noDefaultView = true;
}

$('.mapping-marker-popup-content').each(function() {
    var popup = $(this).clone().show();
    var latLng = new L.LatLng(popup.data('marker-lat'), popup.data('marker-lng'));
    var marker = new L.Marker(latLng);
    marker.bindPopup(popup[0]);
    markers.addLayer(marker);
});

map.addLayer(baseMaps['Streets']);
map.addLayer(markers);
map.addControl(new L.Control.Layers(baseMaps));
map.addControl(new L.Control.FitBounds(markers));

// Switching sections changes map dimensions, so make the necessary adjustments.
$('#mapping-section').one('o:section-opened', function(e) {
    map.invalidateSize();
    if (noDefaultView) {
        var bounds = markers.getBounds();
        if (bounds.isValid()) {
            map.fitBounds(bounds);
        }
    } else {
        map.setView(center, zoom);
    }
});

});
