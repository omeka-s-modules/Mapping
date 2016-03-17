$(document).ready( function() {

// Initialise the map.
var map = L.map('mapping-map').setView([0, 0], 1);
// Initialise the tile layer.
var tileLayer = L.tileLayer('http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
});
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
map.addLayer(tileLayer);
map.addLayer(drawnItems);
map.addControl(drawControl);

var addMarker = function(marker) {
    drawnItems.addLayer(marker);

    // Add the corresponding marker inputs to the form. 
    var mappingForm = $('#mapping-form');
    mappingForm.append($('<input>')
        .attr('type', 'hidden')
        .attr('name', 'o-module-mapping:geo[' + marker._leaflet_id + '][o-module-mapping:latitude]')
        .val(marker.getLatLng().lat));
    mappingForm.append($('<input>')
        .attr('type', 'hidden')
        .attr('name', 'o-module-mapping:geo[' + marker._leaflet_id + '][o-module-mapping:longitude]')
        .val(marker.getLatLng().lng));
};

var editMarker = function(marker) {
    // Edit the corresponding marker form inputs.
    $('input[name="o-module-mapping:geo[' + marker._leaflet_id + '][o-module-mapping:latitude]"]')
        .val(marker.getLatLng().lat);
    $('input[name="o-module-mapping:geo[' + marker._leaflet_id + '][o-module-mapping:longitude]"]')
        .val(marker.getLatLng().lng);
}

var deleteMarker = function(marker) {
    // Remove the corresponding marker inputs from the form.
    $('input[name^="o-module-mapping:geo[' + marker._leaflet_id + ']"]').remove();
}

// Add saved markers to the map.
$.each($('#mapping-map').data('markers'), function(index, value) {
    var latLng = L.latLng(value['o-module-mapping:latitude'], value['o-module-mapping:longitude']);
    var marker = L.marker(latLng);
    addMarker(marker);
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
$('a[href="#mapping-section"]').on('click', function(e) {
    map.invalidateSize();
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
    wms = L.tileLayer.wms($('#mapping-wms-base-url').val(), {
        format: 'image/png',
    }).addTo(map);
});

});
