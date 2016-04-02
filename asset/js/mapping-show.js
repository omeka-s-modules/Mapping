$(document).ready( function() {

var mappingMap = $('#mapping-map');
var map = L.map('mapping-map').setView([0, 0], 1);
var baseMaps = {
    'Streets': L.tileLayer.provider('OpenStreetMap.Mapnik'),
    'Grayscale': L.tileLayer.provider('OpenStreetMap.BlackAndWhite'),
    'Satellite': L.tileLayer.provider('Esri.WorldImagery'),
    'Terrain': L.tileLayer.provider('Esri.WorldShadedRelief')
};
var wms;
var drawnItems = new L.FeatureGroup();
var layerControl = L.control.layers(baseMaps);

map.addLayer(baseMaps['Streets']);
map.addLayer(drawnItems);
map.addControl(layerControl);
map.addControl(L.control.fitBounds(drawnItems));

$.each(mappingMap.data('markers'), function(index, data) {
    var latLng = L.latLng(data['o-module-mapping:lat'], data['o-module-mapping:lng']);
    var marker = L.marker(latLng);
    var popupContent = $('.mapping-marker-popup-content[data-marker-id="' + data['o:id'] + '"]')
        .clone().show();
    marker.bindPopup(popupContent[0]);
    drawnItems.addLayer(marker);
});

var mapping = mappingMap.data('mapping');
if (mapping && mapping['o-module-mapping:wms_base_url']) {
    // WMS layers and styles cannot be null.
    if (!mapping['o-module-mapping:wms_layers']) {
        mapping['o-module-mapping:wms_layers'] = '';;
    }
    if (!mapping['o-module-mapping:wms_styles']) {
        mapping['o-module-mapping:wms_styles'] = '';;
    }
    wms = L.tileLayer.wms(mapping['o-module-mapping:wms_base_url'], {
        layers: mapping['o-module-mapping:wms_layers'],
        styles: mapping['o-module-mapping:wms_styles'],
        format: 'image/png',
        transparent: true,
    }).addTo(map);

    var label = 'Unlabeled Overlay';
    if (mapping['o-module-mapping:wms_label']) {
        label = mapping['o-module-mapping:wms_label'];
    }
    layerControl.addOverlay(wms, label);
    map.addControl(L.control.opacity(wms));
}

// Switching sections changes map dimensions, so make the necessary adjustments.
$('a[href="#mapping-section"], #mapping-legend').on('click', function(e) {
    map.invalidateSize();
});

});
