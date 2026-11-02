/**
 * Shared basemap handling for every Mapping map.
 *
 * Loaded alongside leaflet-providers.js by the mappingBasemapAssets view
 * helper, which also populates the credentials below from global settings.
 *
 * Every map in this module resolves its basemap through this object so that
 * the fallback policy, the credential handling, the zoom range, the
 * corrections to leaflet-providers and the layers control are defined once
 * rather than repeated per map.
 */
const MappingBasemap = {
    /**
     * The provider used when none is configured, when the configured provider
     * does not exist, and when a provider is missing a required credential.
     */
    defaultProviderName: 'OpenStreetMap.Mapnik',

    /**
     * Credentials required by certain providers, keyed by provider name, and
     * set by Mapping\View\Helper\MappingBasemapAssets. Every value listed is
     * required: an empty one substitutes the default provider, since the tiles
     * would otherwise be watermarked (CartoDB) or refused (MapBox).
     *
     * Extend this when adding a provider that needs a credential. Omitting one
     * that uses a {placeholder} in its leaflet-providers URL is not caught by
     * tileLayer(): L.Util.template throws per tile URL, long after the layer is
     * built.
     */
    credentials: {
        'CartoDB': {apikey: ''},
        'MapBox': {accessToken: ''}
    },

    /**
     * The zoom levels each basemap is stretched to cover. Leaflet hides a
     * layer entirely outside its own minZoom and maxZoom rather than scaling
     * its tiles, so a provider with a narrower range would leave the map blank
     * there. 19 matches the module's default maximum zoom.
     */
    minZoom: 0,
    maxZoom: 19,

    /**
     * Corrections to leaflet-providers' own definitions, keyed by provider
     * name or by the part of the name before the dot. A url replaces the
     * layer's tile URL, and anything else is passed as a layer option. Remove
     * each once leaflet-providers fixes it.
     */
    providerCorrections: {
        // The Licence Ouverte asks for the producer's name, which is IGN. The
        // service also has tiles at zooms 0 and 1.
        'GeoportailFrance': {
            attribution: '&copy; <a href="https://www.ign.fr">IGN</a>, <a href="https://www.geoportail.gouv.fr/">Géoportail</a>',
            minZoom: 0
        },
        // Tiles are 512 pixels with a zoom offset of -1, so map zoom 0 would
        // ask for tile zoom -1, which doesn't exist. With a minimum of 1, map
        // zoom 0 is drawn from tile zoom 0, scaled down.
        'MapBox': {minZoom: 1},
        // The tile server supports https. The BKG terms of use require a
        // source reference with the year the data was retrieved, which for
        // live tiles is the current year.
        'TopPlusOpen': {
            url: 'https://sgx.geodatenzentrum.de/wmts_topplus_open/tile/1.0.0/{variant}/default/WEBMERCATOR/{z}/{y}/{x}.png',
            attribution: `&copy; <a href="https://www.bkg.bund.de">BKG</a> (${new Date().getFullYear()}) <a href="https://www.govdata.de/dl-de/by-2-0">dl-de/by-2-0</a>, <a href="https://sgx.geodatenzentrum.de/web_public/gdz/datenquellen/datenquellen_topplusopen.html">Datenquellen</a>`
        },
        // The USGS services have nothing above zoom 16, and the imagery with
        // topo labels has nothing at zoom 0.
        'USGS': {maxZoom: 16},
        'USGS.USImageryTopo': {minZoom: 1}
    },

    /**
     * Get the credentials a provider requires, or null if it needs none.
     *
     * @param {string} providerName
     * @returns {object|null}
     */
    credentialsFor: function(providerName) {
        if ('string' !== typeof providerName) {
            return null;
        }
        // Providers are named "Provider" or "Provider.Variant".
        return this.credentials[providerName.split('.')[0]] || null;
    },

    /**
     * Get the corrections for a provider. Those for the exact name win over
     * those for the part of the name before the dot.
     *
     * @param {string} providerName
     * @returns {object}
     */
    correctionsFor: function(providerName) {
        return Object.assign(
            {},
            this.providerCorrections[providerName.split('.')[0]],
            this.providerCorrections[providerName]
        );
    },

    /**
     * Resolve a configured provider name to one that will actually render,
     * substituting the default provider when a required credential is missing.
     *
     * This has to happen on the client rather than in PHP: the map browse page
     * takes its provider from a URL query parameter, and the block and default
     * bounds forms take theirs from a live select, none of which passes through
     * PHP at render time.
     *
     * @param {string} providerName
     * @returns {string|null} Null if no provider name was given
     */
    resolveName: function(providerName) {
        if (!providerName) {
            return null;
        }
        const credentials = this.credentialsFor(providerName);
        if (credentials && Object.values(credentials).some((value) => !value)) {
            return this.defaultProviderName;
        }
        return providerName;
    },

    /**
     * Get the Leaflet options for an already resolved provider name.
     *
     * @param {string} providerName
     * @returns {object}
     */
    providerOptions: function(providerName) {
        const options = this.correctionsFor(providerName);
        // A corrected url isn't a layer option. providerLayer() applies it.
        delete options.url;
        const credentials = this.credentialsFor(providerName);
        if (credentials) {
            // Only non-empty credentials are passed. An empty one would reach
            // the tile URL as a bare parameter, which is worse than letting
            // the provider's own default stand.
            Object.entries(credentials).forEach(([name, value]) => {
                if (value) {
                    options[name] = value;
                }
            });
        }
        return options;
    },

    /**
     * Stretch a layer over this.minZoom to this.maxZoom. Beyond the provider's
     * own range, Leaflet then scales the nearest tiles it has instead of
     * showing nothing.
     *
     * A provider starting above zoom 2 isn't stretched down, since scaling
     * down that far would request a huge number of tiles. None that Mapping
     * lists does, but the map browse URL accepts any leaflet-providers name.
     *
     * @param {L.TileLayer} layer
     * @returns {L.TileLayer}
     */
    coverZoomRange: function(layer) {
        const options = layer.options;
        if (options.minZoom > this.minZoom && options.minZoom <= 2) {
            L.setOptions(layer, {minNativeZoom: options.minZoom, minZoom: this.minZoom});
        }
        if (options.maxZoom < this.maxZoom) {
            L.setOptions(layer, {maxNativeZoom: options.maxZoom, maxZoom: this.maxZoom});
        }
        return layer;
    },

    /**
     * Build the tile layer for an already resolved provider name.
     *
     * @param {string} providerName
     * @returns {L.TileLayer}
     */
    providerLayer: function(providerName) {
        const layer = L.tileLayer.provider(providerName, this.providerOptions(providerName));
        const url = this.correctionsFor(providerName).url;
        if (url) {
            layer.setUrl(url, true);
        }
        return this.coverZoomRange(layer);
    },

    /**
     * Get a tile layer for the first usable provider name given.
     *
     * Names are tried in order, so a caller can express a cascade: a URL
     * parameter, then a stored setting, for example. Only a name no provider
     * matches falls through to the next; a name missing a required credential
     * resolves to the default provider and stops there.
     *
     * @param {...string} providerNames
     * @returns {L.TileLayer}
     */
    tileLayer: function(...providerNames) {
        for (const providerName of providerNames) {
            const name = this.resolveName(providerName);
            if (!name) {
                continue;
            }
            try {
                return this.providerLayer(name);
            } catch (error) {
                // No such provider or variant. Try the next name.
            }
        }
        return this.providerLayer(this.defaultProviderName);
    },

    /**
     * Get the base maps for a layers control.
     *
     * @param {L.TileLayer} defaultLayer The layer for the "Default" entry
     * @returns {object}
     */
    baseMaps: function(defaultLayer) {
        return {
            'Default': defaultLayer,
            'Streets': this.tileLayer('OpenStreetMap.Mapnik'),
            'Grayscale': this.tileLayer('Esri.WorldGrayCanvas'),
            'Satellite': this.tileLayer('Esri.WorldImagery'),
            'Terrain': this.tileLayer('Esri.WorldShadedRelief')
        };
    }
};
