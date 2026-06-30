L.Control.GroupItemFeatures = L.Control.extend({
    options: {
        position: 'bottomleft'
    },

    initialize: function(filtersHtml, onReturn) {
        this._filtersHtml = filtersHtml;
        this._onReturn = onReturn;
    },

    onAdd: function(map) {
        var container = L.DomUtil.create('div', 'mapping-control-group-item-features');

        var button = L.DomUtil.create('button', '', container);
        button.textContent = Omeka.jsTranslate('Return to groups');
        button.type = 'button';

        var filters = L.DomUtil.create('div', 'mapping-control-group-item-filters', container);
        filters.innerHTML = this._filtersHtml;

        L.DomEvent
            .on(container, 'mousedown', L.DomEvent.stopPropagation)
            .on(container, 'click', L.DomEvent.stopPropagation)
            .on(container, 'dblclick', L.DomEvent.stopPropagation)
            .on(button, 'click', this._onReturn);

        return container;
    }
});

L.control.groupItemFeatures = function(filtersHtml, onReturn) {
    return new L.Control.GroupItemFeatures(filtersHtml, onReturn);
};
