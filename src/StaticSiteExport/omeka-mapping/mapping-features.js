document.addEventListener('DOMContentLoaded', function(event) {
    // Function to check whether the passed URL exists.
    const checkUrl = async function(url) {
        try {
            const response = await fetch(url);
            return response.ok;
        } catch (error) {
            return false;
        }
    }
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
                    const popupHeadingContent = document.createTextNode(featureData.label);
                    popupHeading.appendChild(popupHeadingContent);
                    popupDiv.appendChild(popupHeading);
                    const popupImg = document.createElement('img');
                    const popupImgSrc = `${mapDiv.dataset.relUrl}media/${featureData.mediaId}/thumbnail_medium.jpg`;
                    checkUrl(popupImgSrc).then((exists) => {
                        if (exists) {
                            popupImg.src = popupImgSrc;
                            popupDiv.appendChild(popupImg);
                        }
                    });
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
