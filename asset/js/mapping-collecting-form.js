$(document).ready(function() {
    $('.collecting-map').each(function() {

        var mapDiv = $(this);
        var inputLat = mapDiv.siblings('input.collecting-map-lat');
        var inputLng = mapDiv.siblings('input.collecting-map-lng');

        var map = L.map(this);
        var marker;

        L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="http://osm.org/copyright">OpenStreetMap</a>'
        }).addTo(map);
        map.setView([20, 0], 2);

        // Add the marker to the map.
        map.on('click', function(e) {
            if (marker) {
                map.removeLayer(marker);
            }

            marker = new L.marker(e.latlng).addTo(map);
            inputLat.val(e.latlng.lat);
            inputLng.val(e.latlng.lng);

            // Remove the marker if it's clicked.
            $(marker).on('click', function(e) {
                map.removeLayer(marker);
                inputLat.val('');
                inputLng.val('');
            });
        });
    });
});
