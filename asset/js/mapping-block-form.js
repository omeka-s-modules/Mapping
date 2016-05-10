$(document).ready( function() {

/**
 * Set the map with the default view to a block.
 *
 * @param block The page block (div) jQuery object
 */
var setMap = function(block) {
    var mapDiv = block.find('.mapping-map');

    var defaultZoom = mapDiv.find('input[name$="[zoom]"]').val();
    var defaultLat = mapDiv.find('input[name$="[lat]"]').val();
    var defaultLng = mapDiv.find('input[name$="[lng]"]').val();
    var noInitialDefaultView = false;

    if (!defaultZoom || !defaultLat || !defaultLng) {
        noInitialDefaultView = true;
        defaultZoom = 1;
        defaultLat = 0;
        defaultLng = 0;
    }

    var map = L.map(mapDiv[0]).setView([defaultLat, defaultLng], defaultZoom);
    L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a>'
    }).addTo(map);
    map.addControl(new L.Control.DefaultView(
        function(e) {
            var zoom = map.getZoom();
            var center = map.getCenter();
            mapDiv.find('input[name$="[zoom]"]').val(zoom);
            mapDiv.find('input[name$="[lat]"]').val(center.lat);
            mapDiv.find('input[name$="[lng]"]').val(center.lng);
        },
        function(e) {
            var zoom = mapDiv.find('input[name$="[zoom]"]').val();
            var lat = mapDiv.find('input[name$="[lat]"]').val();
            var lng = mapDiv.find('input[name$="[lng]"]').val();
            if (zoom && lat && lng) {
                map.setView([lat, lng], zoom);
            }
        },
        function(e) {
            mapDiv.find('input[name$="[zoom]"]').val('');
            mapDiv.find('input[name$="[lat]"]').val('');
            mapDiv.find('input[name$="[lng]"]').val('');
            map.setView([0, 0], 1);
        },
        {noInitialDefaultView: noInitialDefaultView}
    ));

    // Expanding changes map dimensions, so make the necessary adjustments.
    block.on('o:expanded', '.mapping-map-expander', function(e) {
        map.invalidateSize();
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

// Handle setting the map for added blocks.
$('#blocks').on('o:block-added', '.block[data-block-layout="mappingmap"]', function(e) {
    setMap($(this));
});

// Handle setting the map for existing blocks.
$('.block[data-block-layout="mappingmap"]').each(function() {
    setMap($(this));
});

// Handle preparing the WMS data for submission.
$('form').submit(function(e) {
    $('.mapping-wms-overlay').each(function(index) {
        $(this).find(':input').each(function() {
            var thisInput = $(this);
            var name = thisInput.attr('name').replace('[__mappingWmsIndex__]', '[' + index + ']');
            thisInput.attr('name', name);
        });
    });
});

// Handle adding a new WMS overlay.
$('#blocks').on('click', '.mapping-wms-add', function(e) {
    e.preventDefault();

    var block = $(this).closest('.block');
    block.find('.mapping-wms-add').show();
    block.find('.mapping-wms-edit').hide();
    var wmsOverlays = block.find('ul.mapping-wms-overlays');
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
    var wmsOverlay = block.find('li.mapping-wms-overlay-editing');
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

});
