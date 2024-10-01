const Mapping = {
    /**
     * Load features into a map asynchronously.
     *
     * @param {L.map}    map                       The Leaflet map object
     * @param {L.layer}  featuresPoint             The Leaflet layer object containing point features
     * @param {L.layer}  featuresPoly              The Leaflet layer object containing polygon features
     * @param {string}   getFeaturesUrl            The "get features" endpoint URL
     * @param {string}   getFeaturePopupContentUrl The "get feature popup content" endpoint URL
     * @param {object}   itemsQuery                The items query
     * @param {object}   featuresQuery             The features query
     * @param {callback} onFeaturesLoadSetView     An optional function called to set view after features are loaded
     * @param {object}   featuresByResource        An optional object
     * @param {int}      featuresPage              The
     */
    loadFeaturesAsync: function(
        map,
        featuresPoint,
        featuresPoly,
        getFeaturesUrl,
        getFeaturePopupContentUrl,
        itemsQuery,
        featuresQuery,
        onLoadSetView = () => null,
        featuresByResource = {},
        featuresPage = 1
    ) {
        // Observe a map interaction (done programmatically or by the user).
        if ('undefined' === typeof map.mapping_map_interaction) {
            map.mapping_map_interaction = false;
            map.on('zoomend moveend', function(e) {
                map.mapping_map_interaction = true;
            });
        }
        const getFeaturesQuery = {
            features_page: featuresPage,
            items_query: itemsQuery,
            features_query: featuresQuery,
        };
        // Get features from the server, one page at a time.
        $.get(getFeaturesUrl, getFeaturesQuery)
            .done(function(featuresData) {
                if (!featuresData.length) {
                    // This page returned no features. Stop recursion.
                    if (!map.mapping_map_interaction) {
                        // Call onLoadSetView only when there was no map interaction.
                        // This prevents the map view from changing after a change
                        // has already been done.
                        onLoadSetView();
                    }
                    return;
                }
                // Iterate the features.
                featuresData.forEach((featureData) => {
                    const featureId = featureData[0];
                    const resourceId = featureData[1];
                    const featureGeography = featureData[2];
                    L.geoJSON(featureGeography, {
                        onEachFeature: function(feature, layer) {
                            const popup = L.popup();
                            layer.bindPopup(popup);
                            if (getFeaturePopupContentUrl) {
                                layer.on('popupopen', function() {
                                    $.get(getFeaturePopupContentUrl, {feature_id: featureId}, function(popupContent) {
                                        popup.setContent(popupContent);
                                    });
                                });
                            }
                            switch (feature.type) {
                                case 'Point':
                                    featuresPoint.addLayer(layer);
                                    break;
                                case 'LineString':
                                case 'Polygon':
                                    layer.on('popupopen', function() {
                                        map.fitBounds(layer.getBounds());
                                    });
                                    featuresPoly.addLayer(layer);
                                    break;
                            }
                            if (!(resourceId in featuresByResource)) {
                                featuresByResource[resourceId] = L.featureGroup();
                            }
                            featuresByResource[resourceId].addLayer(layer);
                        }
                    });
                });
                // Load more features recursively.
                Mapping.loadFeaturesAsync(
                    map,
                    featuresPoint,
                    featuresPoly,
                    getFeaturesUrl,
                    getFeaturePopupContentUrl,
                    itemsQuery,
                    featuresQuery,
                    onLoadSetView,
                    featuresByResource,
                    ++featuresPage
                );
            });
    },
};
