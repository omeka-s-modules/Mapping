$(document).ready( function() {

const mappingMap = $('#mapping-map');
const mappingData = mappingMap.data('mapping');

const [
    map,
    features,
    featuresPoint,
    featuresPoly,
    baseMaps
] = Mapping.initializeMap(mappingMap[0], {}, {
    disableClustering: mappingMap.data('disable-clustering'),
    basemapProvider: mappingMap.data('basemap-provider')
});

let defaultBounds = null;
if (mappingData && mappingData['o-module-mapping:bounds'] !== null) {
    const bounds = mappingData['o-module-mapping:bounds'].split(',');
    const southWest = [bounds[1], bounds[0]];
    const northEast = [bounds[3], bounds[2]];
    defaultBounds = [southWest, northEast];
}

const setView = function() {
    if (defaultBounds) {
        map.fitBounds(defaultBounds);
    } else {
        const bounds = features.getBounds();
        if (bounds.isValid()) {
            map.fitBounds(bounds, {padding: [50, 50]});
        }
    }
};

Mapping.loadFeaturesAsync(
    map,
    featuresPoint,
    featuresPoly,
    mappingMap.data('featuresUrl'),
    mappingMap.data('featurePopupContentUrl'),
    JSON.stringify(mappingMap.data('itemsQuery')),
    JSON.stringify(mappingMap.data('featuresQuery')),
    setView
);

// Switching sections changes map dimensions, so make the necessary adjustments.
$('#mapping-section').one('o:section-opened', function(e) {
    map.invalidateSize();
    setView();
});

});
