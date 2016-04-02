// Control that sets the default view.
L.Control.DefaultView = L.Control.extend({
    options: {
        position: 'topleft'
    },

    onAdd: function (map) {
        this._map = map;

        var container = L.DomUtil.create('div', 'mapping-control-default leaflet-bar');
        var link = L.DomUtil.create('a', 'mapping-control-default-view', container);

        link.innerHTML = '⊕';
        link.href = '#';
        link.title = 'Set this view as the default center and zoom level';
        link.style.fontSize = '18px';

        L.DomEvent
            .on(link, 'mousedown', L.DomEvent.stopPropagation)
            .on(link, 'dblclick', L.DomEvent.stopPropagation)
            .on(link, 'click', L.DomEvent.stopPropagation)
            .on(link, 'click', L.DomEvent.preventDefault)
            .on(link, 'click', this._setDefaultView, this);
        return container;
    },

    _setDefaultView: function(e) {
        var zoom = this._map.getZoom();
        var center = this._map.getCenter();
        $('input[name="o-module-mapping:mapping[o-module-mapping:default_zoom]"]').val(zoom);
        $('input[name="o-module-mapping:mapping[o-module-mapping:default_lat]"]').val(center.lat);
        $('input[name="o-module-mapping:mapping[o-module-mapping:default_lng]"]').val(center.lng);
    },
});
L.control.defaultView = function () {
    return new L.Control.DefaultView();
};
