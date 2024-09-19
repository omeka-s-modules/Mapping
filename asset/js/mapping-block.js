function MappingBlock(mapDiv, timelineDiv) {

    // Call remove() on an existing Leaflet map object to destroy it.
    if (mapDiv[0].mapping_map) {
        mapDiv[0].mapping_map.remove();
    }

    // Instantiate the Leaflet map object.
    const mapData = mapDiv.data('data');
    const map = new L.map(mapDiv[0], {
        minZoom: mapData.min_zoom ? mapData.min_zoom : 0,
        maxZoom: mapData.max_zoom ? mapData.max_zoom : 19,
        fullscreenControl: true,
        worldCopyJump:true
    });

    // For easy reference, assign the Leaflet map object directly to the map element.
    mapDiv[0].mapping_map = map;

    const features = L.featureGroup();
    const featuresPoint = mapDiv.data('disable-clustering')
        ? L.featureGroup()
        : L.markerClusterGroup({
            polygonOptions: {
                color: 'green'
            }
        });
    const featuresPoly = L.deflate({
        markerLayer: featuresPoint, // Enable clustering of poly features
        greedyCollapse: false // Must set to false or small poly features will not be inflated at high zoom.
    });
    const featuresByResource = {};

    // Set base maps and grouped overlays.
    let defaultProvider;
    try {
        defaultProvider = L.tileLayer.provider(mapData['basemap_provider']);
    } catch (error) {
        try {
            defaultProvider = L.tileLayer.provider(mapDiv.data('basemap-provider'));
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
    const noOverlayLayer = new L.GridLayer();
    const groupedOverlays = {'Overlays': {'No overlay': noOverlayLayer}};

    // Set and prepare opacity control.
    let opacityControl;
    const handleOpacityControl = function(overlay, label) {
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

    // Gather features and add them as map layers.
    const featurePopupContentUrl = mapDiv.data('featurePopupContentUrl');
    mapDiv.closest('.mapping-block').find('.mapping-feature-popup-content').each(function() {
        const popup = $(this).clone().show();
        const resourceId = popup.data('resource-id') ?? popup.data('item-id');
        const geography = popup.data('feature-geography');
        L.geoJSON(geography, {
            onEachFeature: function(feature, layer) {
                layer.bindPopup(popup[0]);
                if (featurePopupContentUrl) {
                    layer.on('popupopen', function() {
                        $.get(featurePopupContentUrl, {feature_id: popup.data('featureId')}, function(data) {
                            popup.html(data);
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
            } else {
                map.setView([20, 0], 2);
            }
        }
    };

    // Add the features to the map.
    features.addLayer(featuresPoint);
    features.addLayer(featuresPoly);
    map.addLayer(features);
    setDefaultView();

    // Add base map and grouped WMS overlay layers.
    map.addLayer(baseMaps['Default']);
    map.addLayer(noOverlayLayer);
    $.each(mapData['wms'], function(index, data) {
        wmsLayer = L.tileLayer.wms(data.base_url, {
            layers: data.layers,
            styles: data.styles,
            format: 'image/png',
            transparent: true,
        });
        if (data.open) {
            // This WMS overlay is open by default.
            map.removeLayer(noOverlayLayer);
            map.addLayer(wmsLayer);
            handleOpacityControl(wmsLayer, data.label);
        }
        groupedOverlays['Overlays'][data.label] = wmsLayer;
    });
    L.control.groupedLayers(baseMaps, groupedOverlays, {
        exclusiveGroups: ['Overlays']
    }).addTo(map);

    // Handle the overlay opacity control.
    map.on('overlayadd', function(e) {
        handleOpacityControl(e.layer, e.name);
    });

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
    const mappingFeature = thisButton.closest('.mapping-feature-popup-content');

    const mappingBlock = thisButton.closest('.mapping-block');
    const mappingBlockItems = mappingBlock.next('.mapping-block');
    const mappingMap = mappingBlock.children('.mapping-map');
    const mappingMapItems = mappingBlockItems.children('.mapping-map');

    const url = mappingMap.data('groupUrl');
    const postData = {
        group_type: mappingMap.data('groupType'),
        group: mappingFeature.data('group')
    };
    $.post(url, postData, function(data) {
        mappingBlock.hide();
        mappingBlockItems.show().children('.mapping-feature-popups').html(data);
        MappingBlock(mappingMapItems);
    });
});

$(document).on('click', '.mapping-show-group-features', function() {
    const thisButton = $(this);
    const mappingBlockItems = thisButton.closest('.mapping-block');
    const mappingBlock = mappingBlockItems.prev('.mapping-block');
    mappingBlockItems.hide();
    mappingBlock.show();
});
