// Control that sets the default view.
L.Control.DefaultView = L.Control.extend({
    options: {
        position: 'topleft'
    },

    initialize: function (setCallback, clearCallback) {
        this._setCallback = setCallback;
        this._clearCallback = clearCallback;
    },

    onAdd: function (map) {
        this._map = map;

        var container = L.DomUtil.create('div', 'mapping-control-default leaflet-bar');
        var setLink = L.DomUtil.create('a', 'mapping-control-default-view-set', container);
        var clearLink = L.DomUtil.create('a', 'mapping-control-default-view-clear', container);

        setLink.innerHTML = '↧';
        setLink.href = '#';
        setLink.title = 'Set the current view as the default center and zoom level';
        setLink.style.fontSize = '18px';

        clearLink.innerHTML = '↺';
        clearLink.href = '#';
        clearLink.title = 'Clear the default center and zoom level';
        clearLink.style.fontSize = '18px';

        L.DomEvent
            .on(setLink, 'mousedown', L.DomEvent.stopPropagation)
            .on(setLink, 'dblclick', L.DomEvent.stopPropagation)
            .on(setLink, 'click', L.DomEvent.stopPropagation)
            .on(setLink, 'click', L.DomEvent.preventDefault)
            .on(setLink, 'click', this._setCallback, this);
        L.DomEvent
            .on(clearLink, 'mousedown', L.DomEvent.stopPropagation)
            .on(clearLink, 'dblclick', L.DomEvent.stopPropagation)
            .on(clearLink, 'click', L.DomEvent.stopPropagation)
            .on(clearLink, 'click', L.DomEvent.preventDefault)
            .on(clearLink, 'click', this._clearCallback, this);

        return container;
    },
});
L.control.defaultView = function (setCallback, clearCallback) {
    return new L.Control.DefaultView(setCallback, clearCallback);
};
