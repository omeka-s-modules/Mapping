$(document).ready( function() {

const mappingMap = $('#mapping-map');
const mappingData = mappingMap.data('mapping');
console.log(mappingMap.data('disable-clustering'));

const map = L.map('mapping-map', {
    fullscreenControl: true,
    worldCopyJump:true
});
const features = L.featureGroup();
const featuresPoint = mappingMap.data('disable-clustering') ? L.featureGroup() : L.markerClusterGroup();
const featuresPoly = L.deflate({
    markerLayer: featuresPoint, // Enable clustering of poly features
    greedyCollapse: false // Must set to false or small poly features will not be inflated at high zoom.
});

const baseMaps = {
    'Streets': L.tileLayer.provider('OpenStreetMap.Mapnik'),
    'Grayscale': L.tileLayer.provider('CartoDB.Positron'),
    'Satellite': L.tileLayer.provider('Esri.WorldImagery'),
    'Terrain': L.tileLayer.provider('Esri.WorldShadedRelief')
};

let defaultBounds = null;
if (mappingData && mappingData['o-module-mapping:bounds'] !== null) {
    const bounds = mappingData['o-module-mapping:bounds'].split(',');
    const southWest = [bounds[1], bounds[0]];
    const northEast = [bounds[3], bounds[2]];
    defaultBounds = [southWest, northEast];
}

$('.mapping-feature-popup-content').each(function() {
    const popup = $(this).clone().show();
    const geography = popup.data('feature-geography');
    L.geoJSON(geography, {
        onEachFeature: function(feature, layer) {
            layer.bindPopup(popup[0]);
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
        }
    });
});

features.addLayer(featuresPoint)
    .addLayer(featuresPoly);
map.addLayer(baseMaps['Streets'])
    .addLayer(features)
    .addControl(new L.Control.Layers(baseMaps))
    .addControl(new L.Control.FitBounds(features));

const setView = function() {
    if (defaultBounds) {
        map.fitBounds(defaultBounds);
    } else {
        const bounds = features.getBounds();
        if (bounds.isValid()) {
            map.fitBounds(bounds);
        } else {
            map.setView([20, 0], 2)
        }
    }
};

setView();

// Switching sections changes map dimensions, so make the necessary adjustments.
$('#mapping-section').one('o:section-opened', function(e) {
    map.invalidateSize();
    setView();
});

});
