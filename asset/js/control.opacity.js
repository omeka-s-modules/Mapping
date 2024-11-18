// Control that increases and decreases overlay opacity
L.Control.Opacity = L.Control.extend({
    options: {
        position: 'topleft',
        opacityIncText: '▲',
        opacityDecText: '▼',
    },

    initialize: function (overlay, label, map) {
        this._overlay = overlay;
        this._label = label;
        this._map = map;
        this._opacity = 1.0;
    },

    onAdd: function (map) {
        this._opacity = this._overlay.options.opacity;

        var opacityName = 'mapping-control-opacity';
        var container = L.DomUtil.create('div', opacityName + ' leaflet-bar');
        var opacityIncTitle = `Increase opacity of overlay "${this._label}"`;
        var opacityDecTitle = `Decrease opacity of overlay "${this._label}"`;

        this._opacityIncButton  = this._createButton(
            this.options.opacityIncText, opacityIncTitle,
            opacityName + '-inc',  container, this._opacityInc,  this);
        this._opacityDecButton = this._createButton(
            this.options.opacityDecText, opacityDecTitle,
            opacityName + '-dec', container, this._opacityDec, this);

        // Add the "Fly to overlay" button.
        if ('function' === typeof this._overlay.getBounds) {
            var fitButton = L.DomUtil.create('a', 'foo', container);
            fitButton.innerHTML = '⊡';
            fitButton.style.fontSize = '20px';
            fitButton.title = `Fly to overlay "${this._label}"`;
            fitButton.href = '#';
            L.DomEvent
                .on(fitButton, 'click', L.DomEvent.preventDefault)
                .on(fitButton, 'click', function (e) {
                    this._map.flyToBounds(this._overlay.getBounds());
                }, this);
        }

        return container;
    },

    _opacityInc: function (e) {
        if (this._opacity < 1.0) {
            this._opacity = this._opacity + 0.1;
        }
        this._overlay.setOpacity(this._opacity);
    },

    _opacityDec: function (e) {
        if (this._opacity > 0.1) {
            this._opacity = this._opacity - 0.1;
        }
        this._overlay.setOpacity(this._opacity);
    },

    _createButton: function (html, title, className, container, fn, context) {
        var link = L.DomUtil.create('a', className, container);
        link.innerHTML = html;
        link.href = '#';
        link.title = title;

        var stop = L.DomEvent.stopPropagation;

        L.DomEvent
            .on(link, 'click', stop)
            .on(link, 'mousedown', stop)
            .on(link, 'dblclick', stop)
            .on(link, 'click', L.DomEvent.preventDefault)
            .on(link, 'click', fn, context);

        return link;
    },
});
L.control.opacity = function (overlay, label) {
    return new L.Control.Opacity(overlay, label);
};
