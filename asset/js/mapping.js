$(document).ready( function() {

var mappingMap = $('#mapping-map');
var mappingForm = $('#mapping-form');
// Initialise the map.
var map = L.map('mapping-map').setView([0, 0], 1);
// Initialise the selectable base maps.
var baseMaps = {
    'Streets': L.tileLayer.provider('OpenStreetMap.Mapnik'),
    'Grayscale': L.tileLayer.provider('OpenStreetMap.BlackAndWhite'),
    'Satellite': L.tileLayer.provider('Esri.WorldImagery'),
    'Terrain': L.tileLayer.provider('Esri.WorldShadedRelief')
};
// Initialize the layer control and pass it the base maps.
var layerControl = L.control.layers(baseMaps);
// Initialise the feature group to store editable layers
var drawnItems = new L.FeatureGroup();
// Initialise the draw control and pass it the feature group of editable layers
var drawControl = new L.Control.Draw({
    draw: {
        polyline: false,
        polygon: false,
        rectangle: false,
        circle: false
    },
    edit: {
        featureGroup: drawnItems
    }
});

// Add the layers and controls to the map.
map.addLayer(baseMaps['Streets']);
map.addControl(layerControl);
map.addLayer(drawnItems);
map.addControl(drawControl);

var addMarker = function(marker, markerId, markerLabel) {

    // Build the marker popup content.
    var popupContent = $('.template.mapping-marker-popup-content').clone().removeClass('template');
    popupContent.find('.mapping-marker-label').val(markerLabel).data('marker', marker);

    marker.bindPopup(popupContent[0]);
    drawnItems.addLayer(marker);

    // Add the corresponding marker inputs to the form.
    if (markerId) {
        mappingForm.append($('<input>')
            .attr('type', 'hidden')
            .attr('name', 'o-module-mapping:marker[' + marker._leaflet_id + '][o:id]')
            .val(markerId));
    }
    mappingForm.append($('<input>')
        .attr('type', 'hidden')
        .attr('name', 'o-module-mapping:marker[' + marker._leaflet_id + '][o-module-mapping:lat]')
        .val(marker.getLatLng().lat));
    mappingForm.append($('<input>')
        .attr('type', 'hidden')
        .attr('name', 'o-module-mapping:marker[' + marker._leaflet_id + '][o-module-mapping:lng]')
        .val(marker.getLatLng().lng));
    mappingForm.append($('<input>')
        .attr('type', 'hidden')
        .attr('name', 'o-module-mapping:marker[' + marker._leaflet_id + '][o-module-mapping:label]')
        .val(markerLabel));
};

var editMarker = function(marker) {
    // Edit the corresponding marker form inputs.
    $('input[name="o-module-mapping:marker[' + marker._leaflet_id + '][o-module-mapping:lat]"]')
        .val(marker.getLatLng().lat);
    $('input[name="o-module-mapping:marker[' + marker._leaflet_id + '][o-module-mapping:lng]"]')
        .val(marker.getLatLng().lng);
}

var deleteMarker = function(marker) {
    // Remove the corresponding marker inputs from the form.
    $('input[name^="o-module-mapping:marker[' + marker._leaflet_id + ']"]').remove();
}

// Add saved markers to the map.
$.each(mappingMap.data('markers'), function(index, data) {
    var latLng = L.latLng(data['o-module-mapping:lat'], data['o-module-mapping:lng']);
    var marker = L.marker(latLng);
    addMarker(marker, data['o:id'], data['o-module-mapping:label']);
});

// Add new markers.
map.on('draw:created', function (e) {
    var type = e.layerType;
    var layer = e.layer;
    if (type === 'marker') {
        addMarker(layer);
    }
});

// Edit existing (saved and unsaved) markers.
map.on('draw:edited', function (e) {
    var layers = e.layers;
    layers.eachLayer(function (layer) {
        editMarker(layer);
    });
});

// Delete existing (saved and unsaved) markers.
map.on('draw:deleted', function (e) {
    var layers = e.layers;
    layers.eachLayer(function (layer) {
        deleteMarker(layer);
    });
});

// Switching sections changes map dimensions, so make the necessary adjustments.
$('a[href="#mapping-section"], #mapping-legend').on('click', function(e) {
    map.invalidateSize();
});

// Update corresponding form input when updating a marker label.
mappingMap.on('keyup', 'input.mapping-marker-label', function(e) {
    var thisInput = $(this);
    var marker = thisInput.data('marker');
    var labelInput = $('input[name="o-module-mapping:marker[' + marker._leaflet_id + '][o-module-mapping:label]"]');
    labelInput.val(thisInput.val());
});

// Fit the bounds around the existing markers.
$('#mapping-fit-bounds').on('click', function(e) {
    e.preventDefault()
    if (drawnItems.getBounds().isValid()) {
        map.fitBounds(drawnItems.getBounds());
    }
});

$('#mapping-wms-base-url-set').on('click', function(e) {
    e.preventDefault()
    var wms = L.tileLayer.wms($('#mapping-wms-base-url').val(), {
        format: 'image/png',
    }).addTo(map);
    layerControl.addOverlay(wms, 'WMS');
});

});
