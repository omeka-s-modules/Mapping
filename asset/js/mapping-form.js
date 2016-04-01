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
var wms;

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
            src: $('.mapping-marker-image-select[value="' + markerMediaId + '"').data('mediaThumbnailUrl')
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
        var mediaThumbnail = $('<img>', {src: mediaThumbnailUrl});
    }
    popupContent.find('.mapping-marker-popup-image').html(mediaThumbnail);
    popupContent.data('selectedMediaId', thisInput.val());

    // Update corresponding form input when updating an image.
    var mediaIdInput = $('input[name="o-module-mapping:marker[' + marker._leaflet_id + '][o:media][o:id]"]');
    mediaIdInput.val(thisInput.val());
});

var setWms = function(baseUrl, layers, styles, label) {
    if (wms) {
        map.removeLayer(wms);
        layerControl.removeLayer(wms);
    }
    wms = L.tileLayer.wms(baseUrl, {
        layers: layers,
        styles: styles,
        format: 'image/png',
        transparent: true,
    }).addTo(map);

    if (!label) {
        label = 'Unlabeled Overlay';
    }
    layerControl.addOverlay(wms, label);

    $('input[name="o-module-mapping:mapping[o-module-mapping:wms_base_url]"]').val(baseUrl);
    $('input[name="o-module-mapping:mapping[o-module-mapping:wms_layers]"]').val(layers);
    $('input[name="o-module-mapping:mapping[o-module-mapping:wms_styles]"]').val(styles);
    $('input[name="o-module-mapping:mapping[o-module-mapping:wms_label]"]').val(label);
}


// Set a saved WMS layer to the map.
var mapping = mappingMap.data('mapping');
if (mapping) {
    $('input[name="o-module-mapping:mapping[o:id]"]').val(mapping['o:id']);
    if (mapping['o-module-mapping:wms_base_url']) {
        // WMS is valid only with a base URL.
        setWms(
            mapping['o-module-mapping:wms_base_url'],
            mapping['o-module-mapping:wms_layers'],
            mapping['o-module-mapping:wms_styles'],
            mapping['o-module-mapping:wms_label']
        );
    }
    $('#mapping-wms-base-url').val(mapping['o-module-mapping:wms_base_url']),
    $('#mapping-wms-layers').val(mapping['o-module-mapping:wms_layers']),
    $('#mapping-wms-styles').val(mapping['o-module-mapping:wms_styles']),
    $('#mapping-wms-label').val(mapping['o-module-mapping:wms_label'])
}

$('#mapping-wms-unset').on('click', function(e) {
    e.preventDefault();
    if (wms) {
        map.removeLayer(wms);
        layerControl.removeLayer(wms);
    }
    $('input[name^="o-module-mapping:mapping[o-module-mapping:wms_').val('');
    $('#mapping-wms-base-url, #mapping-wms-layers, #mapping-wms-styles, #mapping-wms-label').val('');
});

$('#mapping-wms-set').on('click', function(e) {
    e.preventDefault();
    setWms(
        $('#mapping-wms-base-url').val(),
        $('#mapping-wms-layers').val(),
        $('#mapping-wms-styles').val(),
        $('#mapping-wms-label').val()
    );
});

});
