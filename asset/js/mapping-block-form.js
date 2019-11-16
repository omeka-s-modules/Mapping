$(document).ready( function() {

/**
 * Set the map with the default view to a block.
 *
 * @param block The page block (div) jQuery object
 */
var setMap = function(block) {
    var mapDiv = block.find('.mapping-map');

    var map = L.map(mapDiv[0]);
    var defaultBounds = null;
    var defaultBoundsData = mapDiv.find('input[name$="[bounds]"]').val();
    if (defaultBoundsData) {
        var bounds = defaultBoundsData.split(',');
        var southWest = [bounds[1], bounds[0]];
        var northEast = [bounds[3], bounds[2]];
        defaultBounds = [southWest, northEast];
    }

    L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

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
};

/**
 * Set WMS data to page form.
 *
 * @param block The page block (div) jQuery object
 * @param wmsOverlay The WMS overlay (li) jQuery object
 * @return bool Whether the WMS data is valid
 */
var setWmsData = function(block, wmsOverlay) {
    var wmsLabel = block.find('input.mapping-wms-label').val();
    var wmsBaseUrl = block.find('input.mapping-wms-base-url').val();
    var wmsLayers = block.find('input.mapping-wms-layers').val();
    var wmsStyles = block.find('input.mapping-wms-styles').val();

    // Label and base URL are required for WMS overlays.
    if (!wmsLabel || !wmsBaseUrl) {
        return false;
    }

    wmsOverlay.find('.mapping-wms-overlay-title').html(wmsLabel);
    wmsOverlay.find('input[name$="[label]"]').val(wmsLabel);
    wmsOverlay.find('input[name$="[base_url]"]').val(wmsBaseUrl);
    wmsOverlay.find('input[name$="[layers]"]').val(wmsLayers);
    wmsOverlay.find('input[name$="[styles]"]').val(wmsStyles);

    block.find('.mapping-wms-fields :input').val('');
    return true;
}

/**
 * Set WMTS data to page form.
 *
 * @param block The page block (div) jQuery object
 * @param wmtsOverlay The WMTS overlay (li) jQuery object
 * @return bool Whether the WMTS data is valid
 */
var setWmtsData = function(block, wmtsOverlay) {
    var wmtsLabel = block.find('input.mapping-wmts-label').val();
    var wmtsUrl = block.find('input.mapping-wmts-url').val();
    var wmtsTileMatrixSet = block.find('input.mapping-wmts-tile-matrix-set').val();
    var wmtsStyle = block.find('input.mapping-wmts-style').val();

    // Label, URL, TileMatrixSet, and style are required for WMTS overlays.
    if (!wmtsLabel || !wmtsUrl || !wmtsTileMatrixSet || !wmtsStyle) {
        return false;
    }

    wmtsOverlay.find('.mapping-wmts-overlay-title').html(wmtsLabel);
    wmtsOverlay.find('input[name$="[label]"]').val(wmtsLabel);
    wmtsOverlay.find('input[name$="[url]"]').val(wmtsUrl);
    wmtsOverlay.find('input[name$="[tile_matrix_set]"]').val(wmtsTileMatrixSet);
    wmtsOverlay.find('input[name$="[style]"]').val(wmtsStyle);

    block.find('.mapping-wmts-fields :input').val('');
    return true;
}

// Handle setting the map for added blocks.
$('#blocks').on('o:block-added', '.block[data-block-layout="mappingMap"]', function(e) {
    setMap($(this));
});

// Handle setting the map for existing blocks.
$('.block[data-block-layout="mappingMap"]').each(function() {
    setMap($(this));
});

// Handle preparing the WMS and WMTS data for submission.
$('form').submit(function(e) {
    $('.mapping-wms-overlay').each(function(index) {
        $(this).find('input[type="hidden"]').each(function() {
            var thisInput = $(this);
            var name = thisInput.attr('name').replace('[__mappingWmsIndex__]', '[' + index + ']');
            thisInput.attr('name', name);
        });
    });
    $('.mapping-wmts-overlay').each(function(index) {
        $(this).find('input[type="hidden"]').each(function() {
            var thisInput = $(this);
            var name = thisInput.attr('name').replace('[__mappingWmtsIndex__]', '[' + index + ']');
            thisInput.attr('name', name);
        });
    });
    // Due to a change to core JS that moved blockIndex replacment from
    // immediately before submit to block creation, we need to replace
    // blockIndex here. Otherwise, dynamically created inputs are ignored.
    // Unfortunately the only way to get the blockIndex at this stage is to
    // extract it from the layout input's name (ideally the index would be set
    // to a data attribute, but that doesn't exist at the time of this fix).
    $('.block[data-block-layout="mappingMap"]').each(function() {
        var thisBlock = $(this);
        var layoutInput = thisBlock.find('input[type="hidden"][name$="[o:layout]"]');
        var index = /\[(\d)\]/.exec(layoutInput.attr('name'))[1];
        thisBlock.find('.mapping-wms-overlay').find('input[type="hidden"]').each(function() {
            var thisInput = $(this);
            var name = thisInput.attr('name').replace('[__blockIndex__]', '[' + index + ']');
            thisInput.attr('name', name);
        });
        thisBlock.find('.mapping-wmts-overlay').find('input[type="hidden"]').each(function() {
            var thisInput = $(this);
            var name = thisInput.attr('name').replace('[__blockIndex__]', '[' + index + ']');
            thisInput.attr('name', name);
        });
    });
});

//** Handle WMS overlays **//

// Handle adding a new WMS overlay.
$('#blocks').on('click', '.mapping-wms-add', function(e) {
    e.preventDefault();

    var block = $(this).closest('.block');
    block.find('.mapping-wms-add').show();
    block.find('.mapping-wms-edit').hide();
    var wmsOverlays = block.find('.mapping-wms-overlays');
    var wmsOverlay = $($.parseHTML(wmsOverlays.data('wmsOverlayTemplate')));

    if (setWmsData(block, wmsOverlay)) {
        wmsOverlays.append(wmsOverlay);
    } else {
        alert('A label and base URL are required for WMS overlays.');
    }
});

// Handle editing an existing WMS overlay.
$('#blocks').on('click', '.mapping-wms-edit', function(e) {
    e.preventDefault();

    var block = $(this).closest('.block');
    block.find('.mapping-wms-add').show();
    block.find('.mapping-wms-edit').hide();
    var wmsOverlay = block.find('.mapping-wms-overlay-editing');
    wmsOverlay.removeClass('mapping-wms-overlay-editing');

    if (!setWmsData(block, wmsOverlay)) {
        alert('A label and base URL are required for WMS overlays.');
    }
});

// Handle clearing the WMS input form.
$('#blocks').on('click', '.mapping-wms-clear', function(e) {
    e.preventDefault();

    var block = $(this).closest('.block');
    block.find('.mapping-wms-add').show();
    block.find('.mapping-wms-edit').hide();
    block.find('.mapping-wms-fields :input').val('');
    block.find('li.mapping-wms-overlay').removeClass('mapping-wms-overlay-editing');
});

// Handle populating existing WMS data to the WMS input form.
$('#blocks').on('click', '.mapping-wms-overlay-edit', function(e) {
    e.preventDefault();

    var block = $(this).closest('.block');
    block.find('.mapping-wms-add').hide();
    block.find('.mapping-wms-edit').show();
    var wmsOverlay = $(this).closest('.mapping-wms-overlay');
    $('.mapping-wms-overlay-editing').removeClass('mapping-wms-overlay-editing');
    wmsOverlay.addClass('mapping-wms-overlay-editing');

    var wmsLabel = wmsOverlay.find('input[name$="[label]"]').val();
    var wmsBaseUrl = wmsOverlay.find('input[name$="[base_url]"]').val();
    var wmsLayers = wmsOverlay.find('input[name$="[layers]"]').val();
    var wmsStyles = wmsOverlay.find('input[name$="[styles]"]').val();

    block.find('input.mapping-wms-label').val(wmsLabel);
    block.find('input.mapping-wms-base-url').val(wmsBaseUrl);
    block.find('input.mapping-wms-layers').val(wmsLayers);
    block.find('input.mapping-wms-styles').val(wmsStyles);
});

// Handle WMS overlay deletion.
$('#blocks').on('click', '.mapping-wms-overlay-delete', function(e) {
    e.preventDefault();

    var wmsOverlay = $(this).closest('.mapping-wms-overlay');
    if (wmsOverlay.hasClass('mapping-wms-overlay-editing')) {
        var block = $(this).closest('.block');
        block.find('.mapping-wms-add').show();
        block.find('.mapping-wms-edit').hide();
        block.find('.mapping-wms-fields :input').val('');
    }
    wmsOverlay.remove();
});

// Handle WMS overlay open/closed checkboxes.
$('#blocks').on('change', '.mapping-wms-open', function(e) {
    var thisCheckbox = $(this);
    var isChecked = thisCheckbox.prop('checked');
    var wmsOverlay = thisCheckbox.closest('.mapping-wms-overlay');
    var wmsOverlays = thisCheckbox.closest('.mapping-wms-overlays');

    wmsOverlays.find('.mapping-wms-open').prop('checked', false);
    thisCheckbox.prop('checked', isChecked);

    wmsOverlays.find('input[name$="[open]"]').val(0);
    wmsOverlay.find('input[name$="[open]"]').val(isChecked ? 1 : 0);
});

//** Handle WMTS overlays **//

// Handle adding a new WMTS overlay.
$('#blocks').on('click', '.mapping-wmts-add', function(e) {
    e.preventDefault();

    var block = $(this).closest('.block');
    block.find('.mapping-wmts-add').show();
    block.find('.mapping-wmts-edit').hide();
    var wmtsOverlays = block.find('.mapping-wmts-overlays');
    var wmtsOverlay = $($.parseHTML(wmtsOverlays.data('wmtsOverlayTemplate')));

    if (setWmtsData(block, wmtsOverlay)) {
        wmtsOverlays.append(wmtsOverlay);
    } else {
        alert('A label, URL, tile matrix set, and style are required for WMTS overlays.');
    }
});

// Handle editing an existing WMTS overlay.
$('#blocks').on('click', '.mapping-wmts-edit', function(e) {
    e.preventDefault();

    var block = $(this).closest('.block');
    block.find('.mapping-wmts-add').show();
    block.find('.mapping-wmts-edit').hide();
    var wmtsOverlay = block.find('.mapping-wmts-overlay-editing');
    wmtsOverlay.removeClass('mapping-wmts-overlay-editing');

    if (!setWmtsData(block, wmtsOverlay)) {
        alert('A label, URL, tile matrix set, and style are required for WMTS overlays.');
    }
});

// Handle clearing the WMTS input form.
$('#blocks').on('click', '.mapping-wmts-clear', function(e) {
    e.preventDefault();

    var block = $(this).closest('.block');
    block.find('.mapping-wmts-add').show();
    block.find('.mapping-wmts-edit').hide();
    block.find('.mapping-wmts-fields :input').val('');
    block.find('li.mapping-wmts-overlay').removeClass('mapping-wmts-overlay-editing');
});

// Handle populating existing WMTS data to the WMTS input form.
$('#blocks').on('click', '.mapping-wmts-overlay-edit', function(e) {
    e.preventDefault();

    var block = $(this).closest('.block');
    block.find('.mapping-wmts-add').hide();
    block.find('.mapping-wmts-edit').show();
    var wmtsOverlay = $(this).closest('.mapping-wmts-overlay');
    $('.mapping-wmts-overlay-editing').removeClass('mapping-wmts-overlay-editing');
    wmtsOverlay.addClass('mapping-wmts-overlay-editing');

    var wmtsLabel = wmtsOverlay.find('input[name$="[label]"]').val();
    var wmtsUrl = wmtsOverlay.find('input[name$="[url]"]').val();
    var wmtsTileMatrixSet = wmtsOverlay.find('input[name$="[tile_matrix_set]"]').val();
    var wmtsStyle = wmtsOverlay.find('input[name$="[style]"]').val();

    block.find('input.mapping-wmts-label').val(wmtsLabel);
    block.find('input.mapping-wmts-url').val(wmtsUrl);
    block.find('input.mapping-wmts-tile-matrix-set').val(wmtsTileMatrixSet);
    block.find('input.mapping-wmts-style').val(wmtsStyle);
});

// Handle WMTS overlay deletion.
$('#blocks').on('click', '.mapping-wmts-overlay-delete', function(e) {
    e.preventDefault();

    var wmtsOverlay = $(this).closest('.mapping-wmts-overlay');
    if (wmtsOverlay.hasClass('mapping-wmts-overlay-editing')) {
        var block = $(this).closest('.block');
        block.find('.mapping-wmts-add').show();
        block.find('.mapping-wmts-edit').hide();
        block.find('.mapping-wmts-fields :input').val('');
    }
    wmtsOverlay.remove();
});

// Handle WMTS overlay open/closed checkboxes.
$('#blocks').on('change', '.mapping-wmts-open', function(e) {
    var thisCheckbox = $(this);
    var isChecked = thisCheckbox.prop('checked');
    var wmtsOverlay = thisCheckbox.closest('.mapping-wmts-overlay');
    var wmtsOverlays = thisCheckbox.closest('.mapping-wmts-overlays');

    wmtsOverlays.find('.mapping-wmts-open').prop('checked', false);
    thisCheckbox.prop('checked', isChecked);

    wmtsOverlays.find('input[name$="[open]"]').val(0);
    wmtsOverlay.find('input[name$="[open]"]').val(isChecked ? 1 : 0);
});

});
