$(document).ready(function() {

$('.mapping-default-bounds').each(function() {
    const container = $(this);
    const hiddenInput = container.find('input[type="hidden"]');
    const mapEl = container.find('.mapping-default-bounds-map')[0];

    const globalBasemapProvider = container.data('global-basemap-provider');
    const basemapSelect = $(container.data('basemap-select') || []);

    const resolveProvider = function() {
        const provider = basemapSelect.val() || globalBasemapProvider || 'OpenStreetMap.Mapnik';
        try {
            return L.tileLayer.provider(provider);
        } catch (e) {
            return L.tileLayer.provider('OpenStreetMap.Mapnik');
        }
    };

    const initMap = function() {
        const map = L.map(mapEl, {
            worldCopyJump: true,
        });

        let tileLayer = resolveProvider().addTo(map);

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

        basemapSelect.on('change.defaultBounds', function() {
            tileLayer.remove();
            tileLayer = resolveProvider().addTo(map);
        });
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
