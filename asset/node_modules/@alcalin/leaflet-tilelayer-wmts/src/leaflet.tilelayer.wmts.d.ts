// leaflet-wmts.d.ts
// Type definitions for leaflet-wmts.js
// Project: Leaflet WMTS TileLayer
// Minimum TypeScript Version: 4.4

declare module '@alcalin/leaflet-tilelayer-wmts' {
  import * as L from 'leaflet';

  export interface WMTSOptions extends L.TileLayerOptions {
    layer: string;
    tileMatrixSet: string;
    style?: string;
    format?: string;                        // used in KVP; REST URLs carry the extension
    time?: string;                          // e.g., '2004-01-01' for GIBS
    requestEncoding?: 'REST' | 'KVP' | 'RESTful';
    tileMatrixLabels?: Array<string | number>;
    googleMapsCompatible?: boolean;
    extraParams?: Record<string, string | number | boolean>;
    baseQuery?: string;
    crossOrigin?: boolean | '' | 'anonymous' | 'use-credentials';
  }

  export class WMTS extends L.TileLayer {
    constructor(url: string, options: WMTSOptions);
    setParams(
      params: Partial<WMTSOptions & { extraParams: Record<string, string | number | boolean> }>,
      noRedraw?: boolean
    ): this;
  }

  export function wmts(url: string, options: WMTSOptions): WMTS;
}
