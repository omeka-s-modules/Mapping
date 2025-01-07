const MappingModule = {
    /**
     *
     * @param {DOM object} mapDiv The map div DOM object
     * @param {object} mapOptions Leaflet map options
     * @param {object} options Options for initializing the map
     *      - disableClustering: (bool) Disable feature clustering?
     *      - basemapProvider: (string) The default basemap provider
     *      - excludeLayersControl: (bool) Exclude the layers control?
     *      - excludeFitBoundsControl: (bool) Exclude the fit bounds control?
     * @returns array
     */
    initializeMap: function(mapDiv, mapOptions, options) {
        mapOptions.fullscreenControl = true;
        mapOptions.worldCopyJump = true;

        // Initialize the map and features.
        const map = new L.map(mapDiv, mapOptions);
        const features = L.featureGroup();
        const featuresPoint = options.disableClustering
            ? L.featureGroup()
            : L.markerClusterGroup({
                polygonOptions: {
                    color: 'green'
                }
            });
        const featuresPoly = L.deflate({
            // Enable clustering of poly features
            markerLayer: featuresPoint,
            // Must set to false or small poly features will not be inflated at high zoom.
            greedyCollapse: false
        });

        // Set base maps and grouped overlays.
        const urlParams = new URLSearchParams(window.location.search);
        let defaultProvider;
        try {
            defaultProvider = L.tileLayer.provider(urlParams.get('mapping_basemap_provider'));
        } catch (error) {
            try {
                defaultProvider = L.tileLayer.provider(options.basemapProvider);
            } catch (error) {
                defaultProvider = L.tileLayer.provider('OpenStreetMap.Mapnik');
            }
        }
        const baseMaps = {
            'Default': defaultProvider,
            'Streets': L.tileLayer.provider('OpenStreetMap.Mapnik'),
            'Grayscale': L.tileLayer.provider('CartoDB.Positron'),
            'Satellite': L.tileLayer.provider('Esri.WorldImagery'),
            'Terrain': L.tileLayer.provider('Esri.WorldShadedRelief')
        };

        // Add features and controls to the map.
        features.addLayer(featuresPoint).addLayer(featuresPoly);
        map.addLayer(defaultProvider).addLayer(features);
        if (!options.excludeLayersControl) {
            map.addControl(new L.Control.Layers(baseMaps));
        }
        if (!options.excludeFitBoundsControl) {
            map.addControl(new L.Control.FitBounds(features));
        }

        // Set the initial view to the geographical center of world.
        map.setView([20, 0], 2);

        return [map, features, featuresPoint, featuresPoly, baseMaps];
    },
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
        onFeaturesLoad = () => null,
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
                    onFeaturesLoad();
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
                            MappingModule.addFeature(map, featuresPoint, featuresPoly, layer, feature.type);
                            if (!(resourceId in featuresByResource)) {
                                featuresByResource[resourceId] = L.featureGroup();
                            }
                            featuresByResource[resourceId].addLayer(layer);
                        }
                    });
                });
                // Load more features recursively.
                MappingModule.loadFeaturesAsync(
                    map,
                    featuresPoint,
                    featuresPoly,
                    getFeaturesUrl,
                    getFeaturePopupContentUrl,
                    itemsQuery,
                    featuresQuery,
                    onFeaturesLoad,
                    featuresByResource,
                    ++featuresPage
                );
            });
    },
    /**
     * Add a feature layer to its respective layer.
     *
     * @param {L.map} map
     * @param {L.layer} featuresPoint
     * @param {L.layer} featuresPoly
     * @param {L.layer} layer
     * @param {string} type
     */
    addFeature: function(map, featuresPoint, featuresPoly, layer, type) {
        switch (type) {
            case 'Point':
                featuresPoint.addLayer(layer);
                break;
            case 'LineString':
            case 'Polygon':
            case 'MultiPolygon':
                layer.on('popupopen', function() {
                    layer.setStyle({color: '#9fc6fc'});
                    map.fitBounds(layer.getBounds());
                });
                layer.on('popupclose', function() {
                    layer.setStyle({color: '#3388ff'});
                });
                featuresPoly.addLayer(layer);
                break;
        }
    }
};
