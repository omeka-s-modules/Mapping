/**
 * Shared basemap handling for every Mapping map.
 *
 * Loaded alongside leaflet-providers.js by the mappingBasemapAssets view
 * helper, which also populates the credentials below from global settings.
 *
 * Every map in this module resolves its basemap through this object so that
 * the fallback policy, the credential handling and the layers control are
 * defined once rather than repeated per map.
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
     * Leaflet option overrides applied on top of a provider's own options.
     */
    providerOverrides: {
        // Esri's gray canvas has no tiles above zoom 16, and Leaflet hides a
        // layer entirely above its maxZoom rather than upscaling. Serve the
        // zoom 16 tiles scaled up instead of showing nothing; 19 matches the
        // module's default maximum zoom.
        'Esri.WorldGrayCanvas': {maxNativeZoom: 16, maxZoom: 19}
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
        const options = Object.assign({}, this.providerOverrides[providerName]);
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
                return L.tileLayer.provider(name, this.providerOptions(name));
            } catch (error) {
                // No such provider or variant. Try the next name.
            }
        }
        return L.tileLayer.provider(
            this.defaultProviderName,
            this.providerOptions(this.defaultProviderName)
        );
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
