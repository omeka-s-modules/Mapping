$(document).ready( function() {

const mappingMap = $('#mapping-map');

const [
    map,
    features,
    featuresPoint,
    featuresPoly,
    baseMaps
] = MappingModule.initializeMap(mappingMap[0], {}, {
    disableClustering: mappingMap.data('disable-clustering'),
    basemapProvider: mappingMap.data('basemap-provider')
});

const onFeaturesLoad = function() {
    if (!map.mapping_map_interaction) {
        // Call fitBounds only when there was no map interaction. This prevents
        // the map view from changing after a change has already been done.
        map.fitBounds(features.getBounds());
    }
};

MappingModule.loadFeaturesAsync(
    map,
    featuresPoint,
    featuresPoly,
    mappingMap.data('featuresUrl'),
    mappingMap.data('featurePopupContentUrl'),
    JSON.stringify(mappingMap.data('itemsQuery')),
    JSON.stringify(mappingMap.data('featuresQuery')),
    onFeaturesLoad
);

});
