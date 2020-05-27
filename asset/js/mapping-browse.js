$(document).ready( function() {

const urlParams = new URLSearchParams(window.location.search);
var defaultProvider;
try {
    defaultProvider = L.tileLayer.provider(urlParams.get('mapping_basemap_provider'));
} catch (error) {
    defaultProvider = L.tileLayer.provider('OpenStreetMap.Mapnik');
}

var map = L.map('mapping-map');
var markers = L.markerClusterGroup();
var baseMaps = {
    'Default': defaultProvider,
    'Streets': L.tileLayer.provider('OpenStreetMap.Mapnik'),
    'Grayscale': L.tileLayer.provider('CartoDB.Positron'),
    'Satellite': L.tileLayer.provider('Esri.WorldImagery'),
    'Terrain': L.tileLayer.provider('Esri.WorldShadedRelief')
};

$('.mapping-marker-popup-content').each(function() {
    var popup = $(this).clone().show();
    var latLng = new L.LatLng(popup.data('marker-lat'), popup.data('marker-lng'));
    var marker = new L.Marker(latLng);
    marker.bindPopup(popup[0]);
    markers.addLayer(marker);
});

map.addLayer(baseMaps['Default']);
map.addLayer(markers);
map.addControl(new L.Control.Layers(baseMaps));
map.addControl(new L.Control.FitBounds(markers));

var bounds = markers.getBounds();
if (bounds.isValid()) {
    map.fitBounds(bounds);
} else {
    map.setView([0, 0], 1);
}

});
