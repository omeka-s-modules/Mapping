function MappingBlock(mapDiv, timelineDiv) {

    // Call remove() on an existing Leaflet map object to destroy it.
    if (mapDiv[0].mapping_map) {
        mapDiv[0].mapping_map.remove();
    }

    // Instantiate the Leaflet map object.
    const mapData = mapDiv.data('data');

    // Set the basemap provider.
    let basemapProvider;
    if (mapData.basemap_provider) {
        basemapProvider = mapData.basemap_provider;
    } else if (mapDiv.data('basemap-provider')) {
        basemapProvider = mapDiv.data('basemap-provider');
    }

    const [
        map,
        features,
        featuresPoint,
        featuresPoly,
        baseMaps
    ] = MappingModule.initializeMap(mapDiv[0], {
        minZoom: mapData.min_zoom ? mapData.min_zoom : 0,
        maxZoom: mapData.max_zoom ? mapData.max_zoom : 19
    }, {
        disableClustering: mapDiv.data('disable-clustering'),
        basemapProvider: basemapProvider,
        excludeLayersControl: true,
        excludeFitBoundsControl: (timelineDiv && timelineDiv.length),
    });

    // For easy reference, assign the Leaflet map object directly to the map element.
    mapDiv[0].mapping_map = map;

    // Set and prepare opacity control.
    let opacityControl;
    const handleOpacityControl = function(overlay, label) {
        if ('inclusive' === mapData.overlay_mode) {
            // Do not display opacity control when overlay mode is inclusive.
            return;
        }
        if (opacityControl) {
            // Only one control at a time.
            map.removeControl(opacityControl);
            opacityControl = null;
        }
        if (overlay !== noOverlayLayer) {
            // The "No overlay" overlay gets no control.
            opacityControl = new L.Control.Opacity(overlay, label);
            map.addControl(opacityControl);
        }
    };

    // Add base map and grouped layers.
    const featuresByResource = {};
    const noOverlayLayer = new L.GridLayer();
    const groupedLayersGroups = {'Overlays': {}};
    const groupedLayersOptions = {};
    if ('inclusive' !== mapData.overlay_mode) {
        groupedLayersGroups['Overlays']['No overlay'] = noOverlayLayer;
        groupedLayersOptions.exclusiveGroups = ['Overlays'];
    }
    const groupedLayers = L.control.groupedLayers(
        baseMaps,
        groupedLayersGroups,
        groupedLayersOptions
    ).addTo(map);
    map.addLayer(noOverlayLayer);

    // Add overlays.
    const addOverlays = async function () {
        if (!mapData.overlays) {
            return;
        }
        for (const overlayData of mapData.overlays) {
            let overlayLayer;
            switch (overlayData.type) {
                case 'wms':
                    overlayLayer = L.tileLayer.wms(overlayData.base_url, {
                        layers: overlayData.layers,
                        styles: overlayData.styles,
                        format: 'image/png',
                        transparent: true,
                        // Leaflet sets the default maxZoom for TileLayer to 18.
                        // Here we set a high maxZoom for WMS layers that zoom
                        // beyond that. This way the only realistic limitation
                        // is the map's default maxZoom.
                        maxZoom: 30,
                    });
                    break;
                case 'iiif':
                    overlayLayer = new Allmaps.WarpedMapLayer()
                    await overlayLayer.addGeoreferenceAnnotationByUrl(overlayData.url)
                    break;
                case 'geojson':
                    overlayLayer = L.geoJSON(JSON.parse(overlayData.geojson), {
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
                                    const labelKey = overlayData.property_key_label;
                                    if (feature.properties[labelKey] && 'string' === typeof feature.properties[labelKey]) {
                                        $('<span>', {class: 'group-type'}).text(feature.properties[labelKey]).appendTo(popup);
                                    }
                                    // Add the popup comment.
                                    const commentKey = overlayData.property_key_comment;
                                    if (feature.properties[commentKey] && 'string' === typeof feature.properties[commentKey]) {
                                        $('<span>', {class: 'group-value'}).text(feature.properties[commentKey]).appendTo(popup);
                                    }
                                    // Add the GeoJSON properties to the popup.
                                    if (overlayData.show_property_list) {
                                        const dl = $('<dl class="geojson-properties">');
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
                        }
                    });
                    break;
            }
            if (overlayLayer) {
                if (overlayData.open) {
                    // This overlay is open by default.
                    map.removeLayer(noOverlayLayer);
                    map.addLayer(overlayLayer);
                    handleOpacityControl(overlayLayer, overlayData.label);
                }
                groupedLayers.addOverlay(overlayLayer, overlayData.label, 'Overlays');
            }
        }
    }
    addOverlays();

    // Handle the overlay opacity control.
    map.on('overlayadd', function(e) {
        handleOpacityControl(e.layer, e.name);
    });

    // Set the scroll wheel zoom behavior.
    switch (mapData['scroll_wheel_zoom']) {
        case 'disable':
            map.scrollWheelZoom.disable()
            break;
        case 'click':
            map.scrollWheelZoom.disable()
            map.on('click', function() {
                if (!map.scrollWheelZoom.enabled()) {
                    map.scrollWheelZoom.enable();
                }
            });
            break;
        default:
            map.scrollWheelZoom.enable()
            break;
    }

    // Set the default view.
    const setDefaultView = function() {
        if (mapData['bounds']) {
            const bounds = mapData['bounds'].split(',');
            const southWest = [bounds[1], bounds[0]];
            const northEast = [bounds[3], bounds[2]];
            map.fitBounds([southWest, northEast]);
        } else {
            const bounds = features.getBounds();
            if (bounds.isValid()) {
                map.fitBounds(bounds);
            }
        }
    };

    const getFeaturesUrl = mapDiv.data('featuresUrl');
    const getFeaturePopupContentUrl = mapDiv.data('featurePopupContentUrl');

    // Load features synchronously.
    mapDiv.closest('.mapping-block').find('.mapping-feature-popup-content').each(function() {
        const popupContent = $(this);
        const featureId = popupContent.data('featureId');
        const featureGeography = popupContent.data('featureGeography');
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
                } else {
                    popup.setContent(popupContent[0]);
                }
                MappingModule.addFeature(map, featuresPoint, featuresPoly, layer, feature.type);
            }
        });
    });

    // Load features asynchronously.
    if (getFeaturesUrl) {
        const onFeaturesLoad = function() {
            if (!map.mapping_map_interaction) {
                // Call setDefaultView only when there was no map interaction. This
                // prevents the map view from changing after a change has already
                // been done.
                setDefaultView();
            }
        };
        MappingModule.loadFeaturesAsync(
            map,
            featuresPoint,
            featuresPoly,
            getFeaturesUrl,
            getFeaturePopupContentUrl,
            JSON.stringify(mapDiv.data('itemsQuery')),
            JSON.stringify(mapDiv.data('featuresQuery')),
            onFeaturesLoad,
            featuresByResource
        );
    }

    setDefaultView();

    if (timelineDiv && timelineDiv.length) {
        timeline = new TL.Timeline(
            timelineDiv[0],
            timelineDiv.data('data'),
            timelineDiv.data('options')
        )
        timeline.on('change', function(e) {
            if ($.isNumeric(e.unique_id)) {
                // Changed to an event slide. Set the timeline event view.
                map.removeLayer(features);
                $.each(featuresByResource, function(resourceId, itemFeatures) {
                    map.removeLayer(itemFeatures);
                });
                // Changed to an event slide. Set the event's map view.
                const currentEvent = this.config.event_dict[e.unique_id];
                const currentEventStart = currentEvent.start_date.data.date_obj;
                const currentEventEnd = ('undefined' === typeof currentEvent.end_date) ? null : currentEvent.end_date.data.date_obj;
                const eventFeatures = featuresByResource[currentEvent.unique_id];
                // features.addLayer(eventFeatures);
                map.addLayer(eventFeatures);
                if ($.isNumeric(mapData['timeline']['fly_to'])) {
                    map.flyToBounds(eventFeatures.getBounds(), {maxZoom: parseInt(mapData['timeline']['fly_to'])});
                } else {
                    if (mapData['timeline']['show_contemporaneous']) {
                        // Show all event features that are contemporaneous with the current event.
                        $.each(this.config.event_dict, function(index, event) {
                            if ($.isNumeric(index) && (index != currentEvent.unique_id)) {
                                const eventStart = event.start_date.data.date_obj;
                                const eventEnd = ('undefined' === typeof event.end_date) ? null : event.end_date.data.date_obj;
                                // For a timeline using intervals, a portion of this event
                                // must fall within the interval of the current event.
                                if (currentEventEnd && eventStart <= currentEventEnd && eventEnd >= currentEventStart) {
                                    features.addLayer(featuresByResource[event.unique_id])
                                }
                                // For a timeline using timestamps, this event must have
                                // the same timestamp as the current event.
                                if (!currentEventEnd && currentEventStart.getTime() == eventStart.getTime()) {
                                    features.addLayer(featuresByResource[event.unique_id])
                                }
                            }
                        });
                    }
                    setDefaultView();
                }
            } else {
                // Changed to the title slide. Set the default map view.
                map.addLayer(features);
                setDefaultView();
            }
        });
    }
}

$(document).ready( function() {
    $('.mapping-block:visible').each(function() {
        const blockDiv = $(this);
        MappingBlock(
            blockDiv.children('.mapping-map'),
            blockDiv.children('.mapping-timeline')
        );
    });
});

$(document).on('click', '.mapping-show-group-item-features', function(e) {
    const thisButton = $(this);
    const groupPopup = thisButton.closest('.mapping-feature-popup-content');
    const groupBlock = thisButton.closest('.mapping-block');
    const itemsBlock = groupBlock.next('.mapping-block');
    const itemsBlockMap = itemsBlock.find('.mapping-map');

    // Copy filters markup to items block.
    itemsBlock.find('.search-filters').html(groupPopup.find('.mapping-search-filters-template').html());

    groupBlock.hide();
    itemsBlock.show();

    // Prepare and load the items map.
    itemsBlockMap.data('itemsQuery', groupPopup.data('itemsQuery'));
    MappingBlock(itemsBlockMap);
});

$(document).on('click', '.mapping-show-group-features', function() {
    const thisButton = $(this);
    const mappingBlockItems = thisButton.closest('.mapping-block');
    const mappingBlock = mappingBlockItems.prev('.mapping-block');
    mappingBlockItems.hide();
    mappingBlock.show();
});
