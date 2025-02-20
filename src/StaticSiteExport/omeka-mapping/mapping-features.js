document.addEventListener('DOMContentLoaded', function(event) {
    document.querySelectorAll('.mapping-features-map').forEach((element) => {
        const map = L.map(element);
        const featureGroup = L.featureGroup();
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        map.scrollWheelZoom.disable()
        map.on('click', function() {
            if (!map.scrollWheelZoom.enabled()) {
                map.scrollWheelZoom.enable();
            }
        });
        fetch(element.dataset.mappingFeaturesUrl)
            .then(response => response.json())
            .then(featuresData => {
                featuresData.forEach((featureData) => {
                    const feature = L.geoJson(featureData.geoJSON);
                    featureGroup.addLayer(feature);
                });
                featureGroup.addTo(map);
                map.fitBounds(featureGroup.getBounds());
            })
    });
});
