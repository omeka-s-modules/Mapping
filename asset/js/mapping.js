$(document).ready( function() {

var mappingMap = $('#mapping-map');

var addMarker = function(marker) {
    drawnItems.addLayer(marker);

    // Add the corresponding marker inputs to the form. 
    var mappingForm = $('#mapping-form');
    mappingForm.append($('<input>')
        .attr('type', 'hidden')
        .attr('name', 'mapping:geo[' + marker._leaflet_id + '][mapping:latitude]')
        .val(marker.getLatLng().lat));
    mappingForm.append($('<input>')
        .attr('type', 'hidden')
        .attr('name', 'mapping:geo[' + marker._leaflet_id + '][mapping:longitude]')
        .val(marker.getLatLng().lng));
    mappingForm.append($('<input>')
        .attr('type', 'hidden')
        .attr('name', 'mapping:geo[' + marker._leaflet_id + '][@type]')
        .val('mapping:GeoCoordinates'));
};

var editMarker = function(marker) {
    // Edit the corresponding marker inputs.
    $('input[name="mapping:geo[' + marker._leaflet_id + '][mapping:latitude]"]')
        .val(marker.getLatLng().lat);
    $('input[name="mapping:geo[' + marker._leaflet_id + '][mapping:longitude]"]')
        .val(marker.getLatLng().lng);
}

var deleteMarker = function(marker) {
    // Remove the corresponding marker inputs from the form.
    $('input[name^="mapping:geo[' + marker._leaflet_id + ']"]').remove();
}

// Initialise the map and tile layer.
var map = L.map('mapping-map').setView([0, 0], 1);
L.tileLayer('http://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
}).addTo(map);

// Initialise the feature group to store editable layers
var drawnItems = new L.FeatureGroup();
map.addLayer(drawnItems);

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
map.addControl(drawControl);

// Add saved markers to the map.
$.each(mappingMap.data('markers'), function(index, value) {
    var latLng = L.latLng(value['mapping:latitude'], value['mapping:longitude']);
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
