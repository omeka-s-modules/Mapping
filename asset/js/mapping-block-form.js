$(document).ready( function() {

/**
 * Set the map with the default view to a block.
 *
 * @param block The page block (div) jQuery object
 */
var setMap = function(block) {
    var mapDiv = block.find('.mapping-map');
    var basemapProviderSelect = block.find('select.basemap-provider');
    var currentZoomLevelSpan = block.find('span.current-zoom');

    var map = L.map(mapDiv[0], {
        fullscreenControl: true,
        worldCopyJump:true
    });
    var defaultBounds = null;
    var defaultBoundsData = mapDiv.find('input[name$="[bounds]"]').val();
    if (defaultBoundsData) {
        var bounds = defaultBoundsData.split(',');
        var southWest = [bounds[1], bounds[0]];
        var northEast = [bounds[3], bounds[2]];
        defaultBounds = [southWest, northEast];
    }

    var layer;
    try {
        layer = L.tileLayer.provider(basemapProviderSelect.val());
    } catch (error) {
        layer = L.tileLayer.provider('OpenStreetMap.Mapnik');
    }
    map.addLayer(layer);

    map.addControl(new L.Control.DefaultView(
        function(e) {
            defaultBounds = map.getBounds();
            mapDiv.find('input[name$="[bounds]"]').val(defaultBounds.toBBoxString());
        },
        function(e) {
            map.invalidateSize();
            map.fitBounds(defaultBounds);
        },
        function(e) {
            defaultBounds = null;
            mapDiv.find('input[name$="[bounds]"]').val('');
            map.setView([20, 0], 2);
        },
        {noInitialDefaultView: !defaultBounds}
    ));

    // Expanding changes map dimensions, so make the necessary adjustments.
    block.on('o:expanded', '.mapping-map-expander', function(e) {
        map.invalidateSize();
        defaultBounds ? map.fitBounds(defaultBounds) : map.setView([20, 0], 2);
    })

    basemapProviderSelect.on('change', function(e) {
        map.removeLayer(layer);
        try {
            layer = L.tileLayer.provider(basemapProviderSelect.val());
        } catch (error) {
            layer = L.tileLayer.provider('OpenStreetMap.Mapnik');
        }
        map.addLayer(layer);
    });

    map.on('zoom', function(e) {
        currentZoomLevelSpan.text(this.getZoom());
    });
};

// Initialize the overlay container.
const initOverlaysContainer = function(block) {
    const overlaysContainer = block.find('.mapping-overlays-container');
    if (!overlaysContainer.length) {
        return;
    }
    const overlays = overlaysContainer.find('.mapping-overlays');
    overlaysContainer.data('overlaysData').forEach(function(overlayData) {
        const overlay = $($.parseHTML(overlaysContainer.data('overlayTemplate')));
        overlay.find('.mapping-overlay-open').prop('checked', overlayData.open);
        populateOverlay(overlay, overlayData);
        overlays.append(overlay);
    });
    new Sortable(overlays[0], {draggable: '.mapping-overlay', handle: '.sortable-handle'});
    resetOverlaysContainer(block);
};

// Reset the overlay container.
const resetOverlaysContainer = function(block) {
    const overlaysContainer = block.find('.mapping-overlays-container');
    overlaysContainer.find('.mapping-overlays-form :input').val('');
    overlaysContainer.find('.mapping-overlay-label').closest('.field').hide();
    overlaysContainer.find('.mapping-overlays-fieldset-wms').hide();
    overlaysContainer.find('.mapping-overlays-fieldset-iiif').hide();
    overlaysContainer.find('.mapping-overlays-fieldset-geojson').hide();
    overlaysContainer.find('.mapping-overlays-save-button').hide();
    overlaysContainer.find('.mapping-overlays-cancel-button').hide();
    overlaysContainer.find('.mapping-overlay-editing').removeClass('mapping-overlay-editing');
}

// Populate an overlay with data.
const populateOverlay = function(overlay, overlayData) {
    overlay.find('.mapping-overlay-input').data('overlayData', overlayData);
    overlay.find('.mapping-overlay-label-span').text(overlayData.label);
};

// Handle the overlay type select.
$(document).on('change', '.mapping-overlays-type-select', function(e) {
    const thisSelect = $(this);
    const overlaysContainer = thisSelect.closest('.mapping-overlays-container');
    const block = overlaysContainer.closest('.block');
    const overlayType = thisSelect.val();
    resetOverlaysContainer(block);
    overlaysContainer.find('.mapping-overlay-label').closest('.field').show();
    thisSelect.val(overlayType);
    switch (overlayType) {
        case 'wms':
            overlaysContainer.find('.mapping-overlays-fieldset-wms').show();
            overlaysContainer.find('.mapping-overlays-save-button').show();
            overlaysContainer.find('.mapping-overlays-cancel-button').show();
            break;
        case 'iiif':
            overlaysContainer.find('.mapping-overlays-fieldset-iiif').show();
            overlaysContainer.find('.mapping-overlays-save-button').show();
            overlaysContainer.find('.mapping-overlays-cancel-button').show();
            break;
        case 'geojson':
            overlaysContainer.find('.mapping-overlays-fieldset-geojson').show();
            overlaysContainer.find('.mapping-overlays-save-button').show();
            overlaysContainer.find('.mapping-overlays-cancel-button').show();
            break;
        default:
            resetOverlaysContainer(block);
    }
});

// Handle the overlay form save button.
$(document).on('click', '.mapping-overlays-save-button', function(e) {
    const overlaysContainer = $(this).closest('.mapping-overlays-container');
    const block = overlaysContainer.closest('.block');
    const overlays = overlaysContainer.find('.mapping-overlays');
    let overlay = overlays.find('.mapping-overlay-editing');
    const isEditing = overlay.length;
    if (!isEditing) {
        overlay = $($.parseHTML(overlaysContainer.data('overlayTemplate')));
    }
    const overlaysSelect = overlaysContainer.find('.mapping-overlays-type-select');
    const overlayLabel = overlaysContainer.find('.mapping-overlay-label').val().trim();
    if (!overlayLabel) {
        alert('An overlay must have a label.');
        return;
    }
    const overlayData = {
        type: overlaysSelect.val(),
        label: overlayLabel,
    };
    let overlayFieldset;
    switch (overlaysSelect.val()) {
        case 'wms':
            overlayFieldset = overlaysContainer.find('.mapping-overlays-fieldset-wms');
            overlayData.base_url = overlayFieldset.find('.mapping-overlay-wms-base-url').val();
            overlayData.layers = overlayFieldset.find('.mapping-overlay-wms-layers').val();
            overlayData.styles = overlayFieldset.find('.mapping-overlay-wms-styles').val();
            break;
        case 'iiif':
            overlayFieldset = overlaysContainer.find('.mapping-overlays-fieldset-iiif');
            overlayData.url = overlayFieldset.find('.mapping-overlay-iiif-url').val();
            break;
        case 'geojson':
            overlayFieldset = overlaysContainer.find('.mapping-overlays-fieldset-geojson');
            overlayData.geojson = overlayFieldset.find('.mapping-overlay-geojson-geojson').val();
            overlayData.property_key_label = overlayFieldset.find('.mapping-overlay-geojson-property-key-label').val();
            overlayData.property_key_comment = overlayFieldset.find('.mapping-overlay-geojson-property-key-comment').val();
            overlayData.show_property_list = overlayFieldset.find('.mapping-overlay-geojson-show-property-list').is(':checked');
            break;
    }
    if (overlayFieldset) {
        populateOverlay(overlay, overlayData);
        if (!isEditing) {
            overlays.append(overlay);
        }
    }
    resetOverlaysContainer(block);
});

// Handle the overlay form cancel button.
$(document).on('click', '.mapping-overlays-cancel-button', function(e) {
    const block = $(this).closest('.block');
    resetOverlaysContainer(block);
});

// Handle the overlay delete button.
$(document).on('click', '.mapping-overlay-delete', function(e) {
    e.preventDefault();
    const overlay = $(this).closest('.mapping-overlay');
    overlay.remove();
});

// Handle the overlay edit button.
$(document).on('click', '.mapping-overlay-edit', function(e) {
    e.preventDefault();
    const overlay = $(this).closest('.mapping-overlay');
    const block = overlay.closest('.block');
    const overlaysContainer = overlay.closest('.mapping-overlays-container');
    const overlaysSelect = overlaysContainer.find('.mapping-overlays-type-select');
    const overlayData = overlay.find('.mapping-overlay-input').data('overlayData');
    let overlayFieldset;
    resetOverlaysContainer(block);
    overlaysContainer.find('.mapping-overlay-label').val(overlayData.label).closest('.field').show();
    switch (overlayData.type) {
        case 'wms':
            overlayFieldset = overlaysContainer.find('.mapping-overlays-fieldset-wms');
            overlayFieldset.find('.mapping-overlay-label').val(overlayData.label);
            overlayFieldset.find('.mapping-overlay-wms-base-url').val(overlayData.base_url);
            overlayFieldset.find('.mapping-overlay-wms-layers').val(overlayData.layers);
            overlayFieldset.find('.mapping-overlay-wms-styles').val(overlayData.styles);
            break;
        case 'iiif':
            overlayFieldset = overlaysContainer.find('.mapping-overlays-fieldset-iiif');
            overlayFieldset.find('.mapping-overlay-label').val(overlayData.label);
            overlayFieldset.find('.mapping-overlay-iiif-url').val(overlayData.url);
            break;
        case 'geojson':
            overlayFieldset = overlaysContainer.find('.mapping-overlays-fieldset-geojson');
            overlayFieldset.find('.mapping-overlay-label').val(overlayData.label);
            overlayFieldset.find('.mapping-overlay-geojson-geojson').val(overlayData.geojson);
            overlayFieldset.find('.mapping-overlay-geojson-property-key-label').val(overlayData.property_key_label);
            overlayFieldset.find('.mapping-overlay-geojson-property-key-comment').val(overlayData.property_key_comment);
            overlayFieldset.find('.mapping-overlay-geojson-show-property-list').prop('checked', overlayData.show_property_list);
            break;
        default:
            resetOverlaysContainer(block);
    }
    if (overlayFieldset) {
        overlayFieldset.show();
        overlaysContainer.find('.mapping-overlays-save-button').show();
        overlaysContainer.find('.mapping-overlays-cancel-button').show();
        overlaysSelect.val(overlayData.type);
        overlay.addClass('mapping-overlay-editing');
    }
});

// Handle the overlay open checkbox.
$(document).on('click', '.mapping-overlay-open', function(e) {
    const thisCheckbox = $(this);
    const isOpen = thisCheckbox.prop('checked');
    const overlay = thisCheckbox.closest('.mapping-overlay');
    const overlays = thisCheckbox.closest('.mapping-overlays');
    const overlaysContainer = overlays.closest('.mapping-overlays-container');
    const overlayModeSelect = overlaysContainer.find('.mapping-overlay-mode-select');
    if ('inclusive' !== overlayModeSelect.val()) {
        overlays.find('.mapping-overlay-open').prop('checked', false);
    }
    overlay.find('.mapping-overlay-open').prop('checked', isOpen);
});

// Handle overlay mode select.
$(document).on('change', '.mapping-overlay-mode-select', function(e) {
    const thisSelect = $(this);
    const overlaysContainer = thisSelect.closest('.mapping-overlays-container');
    if ('inclusive' !== thisSelect.val()) {
        overlaysContainer.find('.mapping-overlay-open').prop('checked', false);
    }
});

// Handle form onSubmit actions.
$('form').on('submit', function(e) {
    // Handle mapping overlays on form submit.
    $('.mapping-overlays').each(function() {
        const overlays = $(this);
        const overlaysContainer = overlays.closest('.mapping-overlays-container');
        const block = overlaysContainer.closest('.block');
        const overlayModeSelect = overlaysContainer.find('.mapping-overlay-mode-select');
        overlayModeSelect.attr('name', overlayModeSelect.attr('name').replace('__blockIndex__', block.data('blockIndex')));
        overlaysContainer.find('.mapping-overlay').each(function() {
            const overlay = $(this);
            const overlayInput = overlay.find('.mapping-overlay-input');
            const overlayData = overlayInput.data('overlayData');
            overlayData.open = overlay.find('.mapping-overlay-open').prop('checked');
            overlayInput.val(JSON.stringify(overlayData));
            overlayInput.attr('name', overlayInput.attr('name').replace('__blockIndex__', block.data('blockIndex')));
        });;
    });
    // Handle GeoJSON validation on form submit.
    $('.mapping-geojson').each(function() {
        const thisTextarea = $(this);
        const geoJSON = thisTextarea.val().trim();
        if (!geoJSON) {
            return;
        }
        try {
            JSON.parse(geoJSON);
        } catch (error) {
            e.preventDefault();
            alert('Invalid GeoJSON in Mapping block');
        }
    });
});

// Handle "mappingMapGroups" blocks.
const prepareBlockMapGroups = function(block) {
    const groupsType = block.find('select.groups-type').val();
    block.find('.hidden_by_default').closest('.field').hide();
    switch (groupsType) {
        case 'item_sets':
            block.find('select.item_set_ids,select.resource_class_id').closest('.field').show();
            break;
        case 'resource_classes':
            block.find('select.resource_class_ids, select.item_set_id').closest('.field').show();
            break;
        case 'property_values_eq':
        case 'property_values_in':
        case 'property_values_res':
            block.find('select.property_id, textarea.values, select.item_set_id, select.resource_class_id').closest('.field').show();
            break;
        case 'properties_ex':
            block.find('select.property_ids, select.item_set_id, select.resource_class_id').closest('.field').show();
            break;
    }
    block.find('select.groups-type').one('change', function(e) {
        prepareBlockMapGroups(block);
    });
};
$('.block[data-block-layout="mappingMapGroups"]').each(function() {
    const thisBlock = $(this);
    prepareBlockMapGroups(thisBlock);
});
$('#blocks').on('o:block-added', '.block[data-block-layout="mappingMapGroups"]', function(e) {
    const thisBlock = $(this);
    thisBlock.find('.chosen-select').chosen();
    prepareBlockMapGroups(thisBlock);
});

// Handle setting the map for added blocks.
$('#blocks').on('o:block-added', '.block[data-block-layout^="mappingMap"]', function(e) {
    const block = $(this);
    setMap(block);
    initOverlaysContainer(block);
});

// Handle setting the map for existing blocks.
$('.block[data-block-layout^="mappingMap"]').each(function() {
    const block = $(this);
    setMap(block);
    initOverlaysContainer(block);
});

});
