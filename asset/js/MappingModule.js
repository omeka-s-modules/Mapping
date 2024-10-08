const MappingModule = {
    /**
     *
     * @param {DOM object} mapDiv The map div DOM object
     * @param {object} mapOptions Leaflet map options
     * @param {object} options Options for initializing the map
     *      - disableClustering: (bool) Disable feature clustering?
     *      - basemapProvider: (string) The default basemap provider
     *      - excludeLayersControl: (bool) Exclude the layers control?
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

        // Add features to the map.
        features.addLayer(featuresPoint)
            .addLayer(featuresPoly);
        map.addLayer(defaultProvider)
            .addLayer(features)
            .addControl(new L.Control.FitBounds(features));
        if (!options.excludeLayersControl) {
            map.addControl(new L.Control.Layers(baseMaps));
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
     * Load GeoJSON features into a map.
     *
     * @param {L.map}   map
     * @param {L.layer} featuresPoint
     * @param {L.layer} featuresPoly
     * @param {object}  geojsonData
     * @returns
     */
    loadGeojsonFeatures: function(map, featuresPoint, featuresPoly, geojsonData) {
        if (!geojsonData.geojson) {
            return;
        }
        L.geoJSON(JSON.parse(geojsonData.geojson), {
            onEachFeature: function(feature, layer) {
                if (feature.properties) {
                    // Filter out non-string properties.
                    $.each(feature.properties, function(key, value) {
                        if ('string' !== typeof value) {
                            delete feature.properties[key];
                        }
                    });
                    if (!$.isEmptyObject(feature.properties)) {
                        // Add the popup.
                        const popup = $('<div>', {
                            class: 'mapping-feature-popup-content',
                        });
                        // Add the popup label.
                        const labelKey = geojsonData.property_key_label;
                        if (feature.properties[labelKey] && 'string' === typeof feature.properties[labelKey]) {
                            $('<h3>').text(feature.properties[labelKey]).appendTo(popup);
                        }
                        // Add the popup comment.
                        const commentKey = geojsonData.property_key_comment;
                        if (feature.properties[commentKey] && 'string' === typeof feature.properties[commentKey]) {
                            $('<p>').text(feature.properties[commentKey]).appendTo(popup);
                        }
                        // Add the GeoJSON properties to the popup.
                        if (geojsonData.show_property_list) {
                            const dl = $('<dl>', {
                                style: 'height: 200px; overflow: scroll;',
                            });
                            $.each(feature.properties, function(key, value) {
                                if ('string' === typeof value) {
                                    const dt = $('<dt>').text(key);
                                    const dd = $('<dd>').text(value);
                                    dl.append(dt, dd);
                                }
                            });
                            popup.append(dl);
                        }
                        // Show popup only when it has contents.
                        if (popup.contents().length) {
                            layer.bindPopup(popup[0]);
                        }
                    }
                }
                // Add features to map.
                switch (feature.geometry.type) {
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
            }
        });
    }
};
