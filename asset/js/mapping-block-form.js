$(document).ready( function() {

$('form').submit(function(e) {
    $('.mapping-wms-overlay').each(function(index) {
        $(this).find(':input').each(function() {
            var thisInput = $(this);
            var name = thisInput.attr('name').replace('[__mappingWmsIndex__]', '[' + index + ']');
            thisInput.attr('name', name);
        });
    });
});

$('#blocks').on('click', '.mapping-wms-add', function(e) {
    var block = $(this).closest('.block');
    var wmsOverlays = block.find('ul.mapping-wms-overlays');
    var wmsOverlay = $($.parseHTML(wmsOverlays.data('wmsOverlayTemplate')));

    var wmsLabel = block.find('input.mapping-wms-overlay-label').val();
    var wmsBaseUrl = block.find('input.mapping-wms-overlay-base-url').val();
    var wmsLayers = block.find('input.mapping-wms-overlay-layers').val();
    var wmsStyles = block.find('input.mapping-wms-overlay-styles').val();

    wmsOverlay.find('.mapping-wms-overlay-title').html(wmsLabel);
    wmsOverlay.find('input[name$="[label]"]').val(wmsLabel);
    wmsOverlay.find('input[name$="[base_url]"]').val(wmsBaseUrl);
    wmsOverlay.find('input[name$="[layers]"]').val(wmsLayers);
    wmsOverlay.find('input[name$="[styles]"]').val(wmsStyles);

    block.find('.mapping-wms-overlay-fields :input').val('');
    wmsOverlays.append(wmsOverlay);
});

});
