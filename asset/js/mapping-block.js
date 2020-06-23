function MappingBlock(mapDiv, timelineDiv) {

    var mapData = mapDiv.data('data');
    var markerData = mapDiv.data('markers');
    var map = new L.map(mapDiv[0], {
        minZoom: mapData.min_zoom ? mapData.min_zoom : 0,
        maxZoom: mapData.max_zoom ? mapData.max_zoom : 19,
    });
    var timelineData = timelineDiv.length ? timelineDiv.data('data') : null;
    var timelineOptions = timelineDiv.length ? timelineDiv.data('options') : null;
    var timeline = timelineDiv.length ? new TL.Timeline(timelineDiv[0], timelineData, timelineOptions) : null;
    var markers = new L.markerClusterGroup();
    var markersByItem = {};

    // Set base map and grouped overlay layers.
    var defaultProvider;
    try {
        defaultProvider = L.tileLayer.provider(mapData['basemap_provider']);
    } catch (error) {
        defaultProvider = L.tileLayer.provider('OpenStreetMap.Mapnik');
    }
    var baseMaps = {
        'Default': defaultProvider,
        'Streets': L.tileLayer.provider('OpenStreetMap.Mapnik'),
        'Satellite': L.tileLayer.provider('Esri.WorldImagery'),
        'Terrain': L.tileLayer.provider('Esri.WorldShadedRelief')
    };
    var noOverlayLayer = new L.GridLayer();
    var groupedOverlays = {'Overlays': {'No overlay': noOverlayLayer}};

    // Set and prepare opacity control.
    var opacityControl;
    var handleOpacityControl = function(overlay, label) {
        if (opacityControl) {
            // Only one control at a time.
            map.removeControl(opacityControl);
            opacityControl = null;
        }
        if (overlay !== noOverlayLayer) {
            // The "No overlay" overlay gets no control.
            opacityControl =  new L.Control.Opacity(overlay, label);
            map.addControl(opacityControl);
        }
    };

    // Set the default view.
    var setDefaultView = function() {
        if (mapData['bounds']) {
            var bounds = mapData['bounds'].split(',');
            var southWest = [bounds[1], bounds[0]];
            var northEast = [bounds[3], bounds[2]];
            map.fitBounds([southWest, northEast]);
        } else {
            var bounds = markers.getBounds();
            if (bounds.isValid()) {
                map.fitBounds(bounds);
            } else {
                map.setView([20, 0], 2);
            }
        }
    };

    // Set the markers.
    $.each(markerData, function(index, data) {
        var markerId = data['o:id'];
        var itemId = data['o:item']['o:id'];
        // Note that we must explicitly specify a new icon so a timeline's event
        // markers can be reset correctly.
        // @see https://github.com/Leaflet/Leaflet.markercluster/issues/786
        var icon = new L.Icon.Default();
        var marker = L.marker(L.latLng(
            data['o-module-mapping:lat'],
            data['o-module-mapping:lng']
        ), {icon: icon});
        var popupContent = $('.mapping-marker-popup-content[data-marker-id="' + markerId + '"]');
        if (popupContent.length > 0) {
            popupContent = popupContent.clone().show();
            marker.bindPopup(popupContent[0]);
        }
        if (!(itemId in markersByItem)) {
            markersByItem[itemId] = new L.markerClusterGroup();
        }
        markersByItem[itemId].addLayer(marker);
        markers.addLayer(marker);
    });

    // Add the markers to the map.
    map.addLayer(markers);
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

    if (timeline) {
        // Reload the map when an event changes.
        timeline.on('change', function(e) {
            $.each(markersByItem, function(itemId, itemMarkers) {
                markers.removeLayer(itemMarkers);
            });
            if ($.isNumeric(e.unique_id)) {
                // Changed to an event slide. Set the event's map view.
                var currentEvent = this.config.event_dict[e.unique_id];
                var currentEventStart = currentEvent.start_date.data.date_obj;
                var currentEventEnd = ('undefined' === typeof currentEvent.end_date) ? null : currentEvent.end_date.data.date_obj;
                var eventMarkers = markersByItem[currentEvent.unique_id];
                markers.addLayer(eventMarkers);
                if ($.isNumeric(mapData['timeline']['fly_to'])) {
                    map.flyToBounds(eventMarkers.getBounds(), {maxZoom: parseInt(mapData['timeline']['fly_to'])});
                } else {
                    if (mapData['timeline']['show_contemporaneous']) {
                        // Show all event markers that are contemporaneous with the current event.
                        $.each(this.config.event_dict, function(index, event) {
                            if ($.isNumeric(index) && (index != currentEvent.unique_id)) {
                                var eventStart = event.start_date.data.date_obj;
                                var eventEnd = ('undefined' === typeof event.end_date) ? null : event.end_date.data.date_obj;
                                // For a timeline using intervals, a portion of this event
                                // must fall within the interval of the current event.
                                if (currentEventEnd && eventStart <= currentEventEnd && eventEnd >= currentEventStart) {
                                    markers.addLayer(markersByItem[event.unique_id])
                                }
                                // For a timeline using timestamps, this event must have
                                // the same timestamp as the current event.
                                if (!currentEventEnd && currentEventStart.getTime() == eventStart.getTime()) {
                                    markers.addLayer(markersByItem[event.unique_id])
                                }
                            }
                        });
                    }
                    setDefaultView();
                }
            } else {
                // Changed to the title slide. Set the default map view.
                $.each(markersByItem, function(itemId, itemMarkers) {
                    markers.addLayer(itemMarkers);
                });
                setDefaultView();
            }
        });
    }
}

$(document).ready( function() {
    $('.mapping-block').each(function() {
        var blockDiv = $(this);
        mappingBlock = new MappingBlock(
            blockDiv.children('.mapping-map'),
            blockDiv.children('.mapping-timeline')
        );
    });
});
