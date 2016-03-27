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
map.addLayer(drawnItems);
map.addControl(layerControl);
map.addControl(drawControl);

var addMarker = function(marker, markerId, markerLabel, markerMediaId) {

    // Build the marker popup content.
    var popupContent = $('.template.mapping-marker-popup-content').clone()
        .removeClass('template')
        .data('marker', marker)
        .data('selectedMediaId', markerMediaId);
    popupContent.find('.mapping-marker-popup-label').val(markerLabel);
    if (markerMediaId) {
        var mediaThumbnail = $('<img>', {
            src: $('.mapping-marker-image-select[value="' + markerMediaId + '"').data('mediaThumbnailUrl'),
            width: '140px',
            height: '140px'
        });
        popupContent.find('.mapping-marker-popup-image').html(mediaThumbnail);
    }
    marker.bindPopup(popupContent[0]);

    // Prepare image selector when marker is clicked.
    marker.on('click', function(e) {
        Omeka.openSidebar($(this), '#mapping-marker-image-selector');
        var selectedMediaId = popupContent.data('selectedMediaId');
        if (selectedMediaId) {
            $('.mapping-marker-image-select[value="' + selectedMediaId + '"]').prop('checked', true);
        } else {
            $('.mapping-marker-image-select:first').prop('checked', true);
        }
    });

    // Close image selector when marker closes.
    marker.on('popupclose', function(e) {
        // Context must be within the sidebar, thus the ".children()"
        Omeka.closeSidebar($('#mapping-marker-image-selector').children());
    });

    // Add the marker layer before adding marker inputs so Leaflet sets an ID.
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
    mappingForm.append($('<input>')
        .attr('type', 'hidden')
        .attr('name', 'o-module-mapping:marker[' + marker._leaflet_id + '][o:media][o:id]')
        .val(markerMediaId));

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
    var markerMediaId = data['o:media'] ? data['o:media']['o:id'] : null;
    addMarker(marker, data['o:id'], data['o-module-mapping:label'], markerMediaId);
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
mappingMap.on('keyup', '.mapping-marker-popup-label', function(e) {
    var thisInput = $(this);
    var marker = thisInput.closest('.mapping-marker-popup-content').data('marker');
    var labelInput = $('input[name="o-module-mapping:marker[' + marker._leaflet_id + '][o-module-mapping:label]"]');
    labelInput.val(thisInput.val());
});

// Handle media image selection.
$('input.mapping-marker-image-select').on('change', function(e) {
    var thisInput = $(this);
    var popupContent = $('.mapping-marker-popup-content:visible');
    var marker = popupContent.data('marker');

    // Render thumbnail in popup content.
    var mediaThumbnail = null;
    var mediaThumbnailUrl = thisInput.data('mediaThumbnailUrl');
    if (mediaThumbnailUrl) {
        var mediaThumbnail = $('<img>', {
            src: mediaThumbnailUrl,
            width: '140px',
            height: '140px'
        });
    }
    popupContent.find('.mapping-marker-popup-image').html(mediaThumbnail);
    popupContent.data('selectedMediaId', thisInput.val());

    // Update corresponding form input when updating an image.
    var mediaIdInput = $('input[name="o-module-mapping:marker[' + marker._leaflet_id + '][o:media][o:id]"]');
    mediaIdInput.val(thisInput.val());
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
