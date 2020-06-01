$(document).ready( function() {

/**
 * Add a marker to the map.
 *
 * @param marker
 * @param markerId
 * @param markerLabel
 * @param markerMediaId
 */
var addMarker = function(marker, markerId, markerLabel, markerMediaId) {

    // Build the marker popup content.
    var popupContent = $('.mapping-marker-popup-content.template').clone()
        .removeClass('template')
        .data('marker', marker)
        .data('selectedMediaId', markerMediaId)
        .show();
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
        var selectedMediaId = popupContent.data('selectedMediaId');
        if (selectedMediaId) {
            $('.mapping-marker-image-select[value="' + selectedMediaId + '"]').prop('checked', true);
        } else {
            $('.mapping-marker-image-select:first').prop('checked', true);
        }
    });

    // Close image selector when marker closes.
    marker.on('popupclose', function(e) {
        var sidebar = $('#mapping-marker-image-selector');
        if (sidebar.hasClass('active')) {
            Omeka.closeSidebar(sidebar);
        }
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
    // Account for markers placed outside the CRS's bounds.
    var latLng = marker.getLatLng().wrap();
    mappingForm.append($('<input>')
        .attr('type', 'hidden')
        .attr('name', 'o-module-mapping:marker[' + marker._leaflet_id + '][o-module-mapping:lat]')
        .val(latLng.lat));
    mappingForm.append($('<input>')
        .attr('type', 'hidden')
        .attr('name', 'o-module-mapping:marker[' + marker._leaflet_id + '][o-module-mapping:lng]')
        .val(latLng.lng));
    mappingForm.append($('<input>')
        .attr('type', 'hidden')
        .attr('name', 'o-module-mapping:marker[' + marker._leaflet_id + '][o-module-mapping:label]')
        .val(markerLabel));
    mappingForm.append($('<input>')
        .attr('type', 'hidden')
        .attr('name', 'o-module-mapping:marker[' + marker._leaflet_id + '][o:media][o:id]')
        .val(markerMediaId));

};

/**
 * Edit a marker.
 *
 * @param marker
 */
var editMarker = function(marker) {
    // Edit the corresponding marker form inputs.
    $('input[name="o-module-mapping:marker[' + marker._leaflet_id + '][o-module-mapping:lat]"]')
        .val(marker.getLatLng().lat);
    $('input[name="o-module-mapping:marker[' + marker._leaflet_id + '][o-module-mapping:lng]"]')
        .val(marker.getLatLng().lng);
}

/**
 * Delete a marker.
 *
 * @param marker
 */
var deleteMarker = function(marker) {
    // Remove the corresponding marker inputs from the form.
    $('input[name^="o-module-mapping:marker[' + marker._leaflet_id + ']"]').remove();
}

/**
 * Fit map bounds.
 */
var fitBounds = function() {
    if (mapMoved) {
        // The user moved the map. Do not fit bounds.
        return;
    }
    if (defaultBounds) {
        map.fitBounds(defaultBounds);
    } else {
        var allMarkers = [];
        map.eachLayer(function(layer) {
            if (layer._latlng) {
                allMarkers.push(layer);
            }
        });
        var bounds = L.featureGroup(allMarkers).getBounds();
        if (bounds.isValid()) {
            map.fitBounds(bounds);
        }
    }
}

// Get map data.
var mappingMap = $('#mapping-map');
var mappingForm = $('#mapping-form');
var mappingData = mappingMap.data('mapping');
var markersData = mappingMap.data('markers');

// Initialize the map and set default view.
var map = L.map('mapping-map');
map.setView([20, 0], 2);
var mapMoved = false;
var defaultBounds = null;
if (mappingData && mappingData['o-module-mapping:bounds'] !== null) {
    var bounds = mappingData['o-module-mapping:bounds'].split(',');
    var southWest = [bounds[1], bounds[0]];
    var northEast = [bounds[3], bounds[2]];
    defaultBounds = [southWest, northEast];
}

// Add layers and controls to the map.
var baseMaps = {
    'Streets': L.tileLayer.provider('OpenStreetMap.Mapnik'),
    'Grayscale': L.tileLayer.provider('CartoDB.Positron'),
    'Satellite': L.tileLayer.provider('Esri.WorldImagery'),
    'Terrain': L.tileLayer.provider('Esri.WorldShadedRelief')
};
var layerControl = L.control.layers(baseMaps);
var drawnItems = new L.FeatureGroup();
var geoSearchControl = new window.GeoSearch.GeoSearchControl({
    provider: new window.GeoSearch.OpenStreetMapProvider,
    showMarker: false,
    retainZoomLevel: false,
});
var drawControl = new L.Control.Draw({
    draw: {
        polyline: false,
        polygon: false,
        rectangle: false,
        circle: false,
        circlemarker: false
    },
    edit: {
        featureGroup: drawnItems
    }
});
L.drawLocal.edit.toolbar.buttons.edit = 'Move markers';
L.drawLocal.edit.toolbar.buttons.editDisabled = 'No markers to move';
L.drawLocal.edit.toolbar.buttons.remove = 'Delete markers';
L.drawLocal.edit.toolbar.buttons.removeDisabled = 'No markers to delete';
L.drawLocal.edit.handlers.edit.tooltip.text = 'Drag and drop marker to move it.';
L.drawLocal.edit.handlers.remove.tooltip.text = 'Click on a marker to delete it.';
map.addLayer(baseMaps['Streets']);
map.addLayer(drawnItems);
map.addControl(layerControl);
map.addControl(drawControl);
map.addControl(geoSearchControl);
map.addControl(new L.Control.DefaultView(
    // Set default view callback
    function(e) {
        defaultBounds = map.getBounds();
        $('input[name="o-module-mapping:mapping[o-module-mapping:bounds]"]').val(defaultBounds.toBBoxString());
    },
    // Go to default view callback
    function(e) {
        map.invalidateSize();
        map.fitBounds(defaultBounds);
    },
    // clear default view callback
    function(e) {
        defaultBounds = null;
        $('input[name="o-module-mapping:mapping[o-module-mapping:bounds]"]').val('');
        map.setView([20, 0], 2);
    },
    {noInitialDefaultView: !defaultBounds}
));

// Add saved markers to the map.
$.each(markersData, function(index, data) {
    var latLng = L.latLng(data['o-module-mapping:lat'], data['o-module-mapping:lng']);
    var marker = L.marker(latLng);
    var markerMediaId = data['o:media'] ? data['o:media']['o:id'] : null;
    addMarker(marker, data['o:id'], data['o-module-mapping:label'], markerMediaId);
});

// Set saved mapping data to the map (default view).
if (mappingData) {
    $('input[name="o-module-mapping:mapping[o:id]"]').val(mappingData['o:id']);
    $('input[name="o-module-mapping:mapping[o-module-mapping:bounds]"]').val(mappingData['o-module-mapping:bounds']);
}

fitBounds();

map.on('movestart', function(e) {
    mapMoved = true;
});

// Handle adding new markers.
map.on('draw:created', function(e) {
    if (e.layerType === 'marker') {
        addMarker(e.layer);
    }
});

// Handle editing existing markers (saved and unsaved).
map.on('draw:edited', function(e) {
    e.layers.eachLayer(function(layer) {
        editMarker(layer);
    });
});

// Handle deleting existing (saved and unsaved) markers.
map.on('draw:deleted', function(e) {
    e.layers.eachLayer(function(layer) {
        deleteMarker(layer);
    });
});

// Handle adding a geocoded marker.
map.on('geosearch/showlocation', function(e) {
    addMarker(new L.Marker([e.location.y, e.location.x]), null, e.location.label);
});

// Switching sections changes map dimensions, so make the necessary adjustments.
$('#mapping-section').on('o:section-opened', function(e) {
    $('#content').one('transitionend', function(e) {
        map.invalidateSize();
        fitBounds();
    });
});

// Handle updating corresponding form input when updating a marker label.
mappingMap.on('keyup', '.mapping-marker-popup-label', function(e) {
    var thisInput = $(this);
    var marker = thisInput.closest('.mapping-marker-popup-content').data('marker');
    var labelInput = $('input[name="o-module-mapping:marker[' + marker._leaflet_id + '][o-module-mapping:label]"]');
    labelInput.val(thisInput.val());
});

// Handle select popup image button.
$('#mapping-section').on('click', '.mapping-marker-popup-image-select', function(e) {
    e.preventDefault();
    Omeka.openSidebar($('#mapping-marker-image-selector'));
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
        popupContent.find('.mapping-marker-popup-image-select').html('Change Marker Image');
    } else {
        popupContent.find('.mapping-marker-popup-image-select').html('Select Marker Image');
    }
    popupContent.find('.mapping-marker-popup-image').html(mediaThumbnail);
    popupContent.data('selectedMediaId', thisInput.val());

    // Update corresponding form input when updating an image.
    var mediaIdInput = $('input[name="o-module-mapping:marker[' + marker._leaflet_id + '][o:media][o:id]"]');
    mediaIdInput.val(thisInput.val());

    // Set the media title as the popup label if not already set.
    var mediaTitle = thisInput.data('mediaTitle');
    var popupLabel = popupContent.find('.mapping-marker-popup-label');
    if (!popupLabel.val()) {
        var labelInput = $('input[name="o-module-mapping:marker[' + marker._leaflet_id + '][o-module-mapping:label]"]');
        labelInput.val(mediaTitle);
        popupLabel.val(mediaTitle);
    }
});

});
