$(document).ready( function() {

const mappingMap = $('#mapping-map');
const mappingData = mappingMap.data('mapping');

const map = L.map('mapping-map', {
    fullscreenControl: true,
    worldCopyJump:true
});
const features = L.featureGroup();
const featuresPoint = mappingMap.data('disable-clustering')
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

// Set base maps.
let defaultProvider;
try {
    defaultProvider = L.tileLayer.provider(mappingMap.data('basemap-provider'));
} catch (error) {
    defaultProvider = L.tileLayer.provider('OpenStreetMap.Mapnik');
}
const baseMaps = {
    'Default': defaultProvider,
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

const featurePopupContentUrl = mappingMap.data('featurePopupContentUrl');
$('.mapping-feature-popup-content').each(function() {
    const popup = $(this).clone().show();
    const geography = popup.data('feature-geography');
    L.geoJSON(geography, {
        onEachFeature: function(feature, layer) {
            layer.bindPopup(popup[0]);
            layer.on('popupopen', function() {
                $.get(featurePopupContentUrl, {feature_id: popup.data('featureId')}, function(data) {
                    popup.html(data);
                });
            });
            switch (feature.type) {
                case 'Point':
                    featuresPoint.addLayer(layer);
                    break;
                case 'LineString':
                case 'Polygon':
                    layer.on('popupopen', function() {
                        map.fitBounds(layer.getBounds(), {padding: [50, 50]});
                    });
                    featuresPoly.addLayer(layer);
                    break;
            }
        }
    });
});

features.addLayer(featuresPoint)
    .addLayer(featuresPoly);
map.addLayer(baseMaps['Default'])
    .addLayer(features)
    .addControl(new L.Control.Layers(baseMaps))
    .addControl(new L.Control.FitBounds(features));

const setView = function() {
    if (defaultBounds) {
        map.fitBounds(defaultBounds);
    } else {
        const bounds = features.getBounds();
        if (bounds.isValid()) {
            map.fitBounds(bounds, {padding: [50, 50]});
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
