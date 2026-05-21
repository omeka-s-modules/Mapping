L.Control.GroupSelect = L.Control.extend({
    options: {
        position: 'bottomleft'
    },

    initialize: function(groups) {
        this._groups = groups;
    },

    onAdd: function(map) {
        this._map = map;

        var container = L.DomUtil.create('div', 'mapping-control-group-select');
        var select = L.DomUtil.create('select', '', container);

        var defaultOption = L.DomUtil.create('option', '', select);
        defaultOption.value = '';
        defaultOption.text = Omeka.jsTranslate('Select a group');

        this._groups.forEach(function(group, index) {
            var option = L.DomUtil.create('option', '', select);
            option.value = index;
            option.text = group.label;
        });

        L.DomEvent
            .on(select, 'mousedown', L.DomEvent.stopPropagation)
            .on(select, 'click', L.DomEvent.stopPropagation)
            .on(select, 'dblclick', L.DomEvent.stopPropagation)
            .on(select, 'change', this._onChange, this);

        this._select = select;
        return container;
    },

    _onChange: function() {
        var index = parseInt(this._select.value);
        if (isNaN(index)) return;
        var layer = this._groups[index].layer;
        var map = this._map;
        if (layer.getBounds) {
            map.once('moveend', function() {
                // Defer openPopup() so L.Deflate's synchronous moveend handler
                // runs first and inflates the polygon before the popup opens.
                setTimeout(function() { layer.openPopup(); }, 0);
            });
            map.fitBounds(layer.getBounds());
        } else {
            layer.openPopup();
        }
    },

    reset: function() {
        this._select.value = '';
    }
});

L.control.groupSelect = function(groups) {
    return new L.Control.GroupSelect(groups);
};
