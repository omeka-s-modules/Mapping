$(document).ready(function() {

$('.mapping-default-bounds').each(function() {
    const container = $(this);
    const hiddenInput = container.find('input[type="hidden"]');
    const mapEl = container.find('.mapping-default-bounds-map')[0];

    const initMap = function() {
        const map = L.map(mapEl, {
            worldCopyJump: true,
        });

        L.tileLayer.provider('OpenStreetMap.Mapnik').addTo(map);

        const val = hiddenInput.val();
        const globalBounds = container.data('global-bounds');
        const initBounds = val || globalBounds;
        if (initBounds) {
            const b = initBounds.split(',');
            map.fitBounds([[b[1], b[0]], [b[3], b[2]]]);
        } else {
            map.setView([20, 0], 2);
        }

        map.addControl(new L.Control.DefaultView(
            function() {
                hiddenInput.val(map.getBounds().toBBoxString());
            },
            function() {
                const b = hiddenInput.val().split(',');
                map.fitBounds([[b[1], b[0]], [b[3], b[2]]]);
            },
            function() {
                hiddenInput.val('');
                if (globalBounds) {
                    const b = globalBounds.split(',');
                    map.fitBounds([[b[1], b[0]], [b[3], b[2]]]);
                } else {
                    map.setView([20, 0], 2);
                }
            },
            {noInitialDefaultView: !val}
        ));
    };

    // Defer init until the section is visible so Leaflet gets real dimensions.
    const section = container.closest('.section');
    if (section.length && !section.is(':visible')) {
        section.one('o:section-opened', initMap);
    } else {
        initMap();
    }
});

});
