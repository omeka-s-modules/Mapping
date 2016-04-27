$(document).ready( function() {

$('form').submit(function(e) {
    $('.mapping-wms-data').each(function(index) {
        $(this).find(':input').each(function() {
            var thisInput = $(this);
            var name = thisInput.attr('name').replace('[__mappingWmsDataIndex__]', '[' + index + ']');
            thisInput.attr('name', name);
        });
    });
});

});
