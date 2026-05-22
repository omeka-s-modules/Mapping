(function (global, factory) {
  typeof exports === 'object' && typeof module !== 'undefined' ? factory(exports, require('leaflet')) :
  typeof define === 'function' && define.amd ? define(['exports', 'leaflet'], factory) :
  (global = typeof globalThis !== 'undefined' ? globalThis : global || self, factory(global.LTileLayerWMTS = {}, global.L));
})(this, (function (exports, L) { 'use strict';

  function _interopNamespaceDefault(e) {
    var n = Object.create(null);
    if (e) {
      Object.keys(e).forEach(function (k) {
        if (k !== 'default') {
          var d = Object.getOwnPropertyDescriptor(e, k);
          Object.defineProperty(n, k, d.get ? d : {
            enumerable: true,
            get: function () { return e[k]; }
          });
        }
      });
    }
    n.default = e;
    return Object.freeze(n);
  }

  var L__namespace = /*#__PURE__*/_interopNamespaceDefault(L);

  class WMTS extends L__namespace.TileLayer {
    constructor(url, options = {}) {
      super(url, options);

      const {
        layer, tileMatrixSet, style = "default", format = "image/png",
        time, tileMatrixLabels, googleMapsCompatible = true,
        extraParams, baseQuery, crossOrigin, requestEncoding, ...tileLayerOpts
      } = options;

      if (!layer) throw new Error("WMTS: 'layer' is required.");
      if (!tileMatrixSet) throw new Error("WMTS: 'tileMatrixSet' is required.");

      L__namespace.setOptions(this, tileLayerOpts);

      this._labels = tileMatrixLabels;
      this._gmc = googleMapsCompatible === true;

      // Normalize encoding; detect from URL tokens if unspecified.
      const enc = String(requestEncoding ?? "").toUpperCase();
      const urlHasRestTokens = /\{(layer|style|time|tilematrixset|tilematrix|z|x|y)\}/i.test(url);
      this._encoding = enc === "RESTFUL" ? "REST" : enc;
      if (this._encoding !== "REST" && this._encoding !== "KVP") {
        this._encoding = urlHasRestTokens ? "REST" : "KVP";
      }

      this._baseUrl = baseQuery && baseQuery.length
        ? `${url}${url.includes("?") ? "&" : "?"}${baseQuery}`
        : url;

      const size = this.getTileSize();
      this._wmtsParams = {
        SERVICE: "WMTS",
        REQUEST: "GetTile",
        VERSION: "1.0.0",
        LAYER: String(layer),
        STYLE: String(style),
        TILEMATRIXSET: String(tileMatrixSet),
        FORMAT: String(format),
        WIDTH: String(size.x),
        HEIGHT: String(size.y)
      };

      if (extraParams) {
        for (const [k, v] of Object.entries(extraParams)) {
          this._wmtsParams[k.toUpperCase()] = String(v);
        }
      }

      this._rest = {
        layer: String(layer),
        style: String(style),
        time: time ? String(time) : undefined,
        tileMatrixSet: String(tileMatrixSet)
      };

      if (crossOrigin !== undefined) {
        this.options.crossOrigin = crossOrigin === true ? "" : crossOrigin;
      }
    }

    createTile(coords, done) {
      const tile = super.createTile(coords, done);
      if (this.options.crossOrigin && !tile.crossOrigin) {
        tile.crossOrigin = this.options.crossOrigin;
      }
      return tile;
    }

    getTileUrl(coords) {
      const z = this._tileZoom;
      const matrix = this._labels && this._labels[z] !== undefined ? this._labels[z] : z;

      if (!this._gmc && !this._labels) {
        throw new Error("WMTS: Non-GoogleMapsCompatible grids require tileMatrixLabels mapping.");
      }

      // REST branch: expand ALL placeholders in one go (incl. {s})
      if (this._encoding === "REST") {
        const ctx = {
          s: this._getSubdomain(coords),
          layer: this._rest.layer,
          style: this._rest.style,
          time: this._rest.time,
          tilematrixset: this._rest.tileMatrixSet,
          tileMatrixSet: this._rest.tileMatrixSet, // camelCase too, if template uses it
          z, x: coords.x, y: coords.y,
          tilematrix: String(matrix),
          tileMatrix: String(matrix)
        };
        return L__namespace.Util.template(this._baseUrl, ctx);
      }

      // KVP branch: URL must NOT contain REST placeholders besides optional {s}
      const hasNonSubdomainTokens = /\{(?!s\})[^}]+\}/.test(this._baseUrl);
      if (hasNonSubdomainTokens) {
        throw new Error("WMTS KVP: base URL contains REST placeholders. Use REST encoding or a KVP base.");
      }

      const url = L__namespace.Util.template(this._baseUrl, { s: this._getSubdomain(coords) });
      const params = {
        ...this._wmtsParams,
        TILEMATRIX: String(matrix),
        TILEROW: String(coords.y),
        TILECOL: String(coords.x)
      };
      return url + L__namespace.Util.getParamString(params, url);
    }

    setParams(params = {}, noRedraw) {
      if (params.requestEncoding) {
        const enc = String(params.requestEncoding).toUpperCase();
        this._encoding = enc === "RESTFUL" ? "REST" : enc;
      }

      if (params.layer) {
        this._wmtsParams.LAYER = String(params.layer);
        this._rest.layer = String(params.layer);
      }
      if (params.style) {
        this._wmtsParams.STYLE = String(params.style);
        this._rest.style = String(params.style);
      }
      if (params.tileMatrixSet) {
        this._wmtsParams.TILEMATRIXSET = String(params.tileMatrixSet);
        this._rest.tileMatrixSet = String(params.tileMatrixSet);
      }
      if (params.format) this._wmtsParams.FORMAT = String(params.format);
      if (params.time !== undefined) this._rest.time = params.time ? String(params.time) : undefined;

      if (params.tileMatrixLabels) this._labels = params.tileMatrixLabels;
      if (typeof params.googleMapsCompatible === "boolean") this._gmc = params.googleMapsCompatible;

      if (params.extraParams) {
        for (const [k, v] of Object.entries(params.extraParams)) {
          this._wmtsParams[k.toUpperCase()] = String(v);
        }
      }

      if (params.baseQuery !== undefined) {
        const sep = this._url.includes("?") ? "&" : "?";
        this._baseUrl = `${this._url}${sep}${params.baseQuery ?? ""}`;
      }

      if (!noRedraw) this.redraw();
      return this;
    }
  }

  function wmts(url, options) {
    return new WMTS(url, options);
  }

  exports.WMTS = WMTS;
  exports.wmts = wmts;

}));
//# sourceMappingURL=leaflet.tilelayer.wmts.umd.js.map
