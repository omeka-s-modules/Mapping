document.addEventListener('DOMContentLoaded', function(event) {
    // Iterate all features maps on the page.
    document.querySelectorAll('.mapping-features-map').forEach((mapDiv) => {
        const map = L.map(mapDiv);
        const featureGroup = L.featureGroup();
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        map.scrollWheelZoom.disable()
        map.on('click', function() {
            if (!map.scrollWheelZoom.enabled()) {
                map.scrollWheelZoom.enable();
            }
        });
        // Get the features data and add the features to the map.
        fetch(mapDiv.dataset.mappingFeaturesUrl)
            .then(response => response.json())
            .then(featuresData => {
                featuresData.forEach((featureData) => {
                    // Create the popup content.
                    const popupDiv = document.createElement('div');
                    const popupHeading = document.createElement('h2');
                    const popupHeadingText = document.createTextNode(featureData.label);
                    popupHeading.appendChild(popupHeadingText);
                    popupDiv.appendChild(popupHeading);
                    if (featureData.hasThumbnails) {
                        const popupImg = document.createElement('img');
                        popupImg.src = mapDiv.dataset.relUrl + 'media/' + featureData.mediaId + '/thumbnail_medium.jpg';
                        popupDiv.appendChild(popupImg);
                    }
                    // Create the feature and bind the popup.
                    const feature = L.geoJson(featureData.geoJSON);
                    feature.bindPopup(popupDiv);
                    featureGroup.addLayer(feature);
                });
                featureGroup.addTo(map);
                map.fitBounds(featureGroup.getBounds());
            })
    });
});
