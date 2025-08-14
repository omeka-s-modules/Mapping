document.addEventListener('DOMContentLoaded', function(event) {
    // Iterate all features maps on the page.
    document.querySelectorAll('.mapping-features-map').forEach(async (mapDiv) => {
        const relUrl = mapDiv.dataset.relUrl;
        const configResponse = await fetch(mapDiv.dataset.configUrl);
        const congigData = await configResponse.json();
        const featuresResponse = await fetch(mapDiv.dataset.featuresUrl);
        const featuresData = await featuresResponse.json();

        const map = L.map(mapDiv, {
            center: [0, 0],
            zoom: 2
        });
        const featureGroup = L.markerClusterGroup();
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
        map.scrollWheelZoom.disable()
        map.on('click', function() {
            if (!map.scrollWheelZoom.enabled()) {
                map.scrollWheelZoom.enable();
            }
        });
        // Get the features data and add the features to the map.
        featuresData.forEach((featureData) => {
            L.geoJSON(featureData.geoJSON, {
                onEachFeature: function(feature, layer) {
                    // Create the popup content.
                    const popupDiv = document.createElement('div');
                    const popupHeading = document.createElement('h2');
                    const popupHeadingLink = document.createElement('a');
                    const popupHeadingText = document.createTextNode(featureData.label);
                    popupHeadingLink.href =  relUrl + 'items/' + featureData.itemId
                    popupHeadingLink.appendChild(popupHeadingText);
                    popupHeading.appendChild(popupHeadingLink);
                    popupDiv.appendChild(popupHeading);
                    if (featureData.hasThumbnails) {
                        const popupImg = document.createElement('img');
                        popupImg.src = relUrl + 'media/' + featureData.mediaId + '/thumbnail_medium.jpg';
                        popupDiv.appendChild(popupImg);
                    }
                    // Create the feature and bind the popup.
                    layer.bindPopup(popupDiv);
                    featureGroup.addLayer(layer);
                }
            });
        });
        featureGroup.addTo(map);

        // Fit the map to bounds, if any.
        let defaultBounds;
        if (congigData.bounds) {
            const bounds = congigData.bounds.split(',');
            const southWest = [bounds[1], bounds[0]];
            const northEast = [bounds[3], bounds[2]];
            map.fitBounds([southWest, northEast]);
        } else if (featureGroup.getBounds().isValid()) {
            map.fitBounds(featureGroup.getBounds(), {padding: [50, 50]});
        }
    })
});
