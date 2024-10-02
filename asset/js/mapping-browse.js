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

MappingModule.loadFeaturesAsync(
    map,
    featuresPoint,
    featuresPoly,
    mappingMap.data('featuresUrl'),
    mappingMap.data('featurePopupContentUrl'),
    JSON.stringify(mappingMap.data('itemsQuery')),
    JSON.stringify(mappingMap.data('featuresQuery')),
    () => map.fitBounds(features.getBounds())
);

});
