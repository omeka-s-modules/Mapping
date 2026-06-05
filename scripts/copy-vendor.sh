#!/usr/bin/env bash
set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SRC="$ROOT/node_modules"
DEST="$ROOT/asset/vendor"

rm -rf "$DEST"

# leaflet — leaflet.css references dist/images/
mkdir -p "$DEST/leaflet/dist/images"
cp "$SRC/leaflet/dist/leaflet.css" "$DEST/leaflet/dist/"
cp "$SRC/leaflet/dist/leaflet.js" "$DEST/leaflet/dist/"
cp "$SRC/leaflet/dist/images/"* "$DEST/leaflet/dist/images/"

# leaflet-draw — leaflet.draw.css references dist/images/
mkdir -p "$DEST/leaflet-draw/dist/images"
cp "$SRC/leaflet-draw/dist/leaflet.draw.css" "$DEST/leaflet-draw/dist/"
cp "$SRC/leaflet-draw/dist/leaflet.draw.js" "$DEST/leaflet-draw/dist/"
cp "$SRC/leaflet-draw/dist/images/"* "$DEST/leaflet-draw/dist/images/"

# leaflet-geosearch — copy only the 2 referenced files (dist/ contains TS defs, docs, etc.)
mkdir -p "$DEST/leaflet-geosearch/dist"
cp "$SRC/leaflet-geosearch/dist/bundle.min.js" "$DEST/leaflet-geosearch/dist/"
cp "$SRC/leaflet-geosearch/dist/geosearch.css" "$DEST/leaflet-geosearch/dist/"

# leaflet-groupedlayercontrol — copy only the 2 referenced files
mkdir -p "$DEST/leaflet-groupedlayercontrol/dist"
cp "$SRC/leaflet-groupedlayercontrol/dist/leaflet.groupedlayercontrol.min.css" "$DEST/leaflet-groupedlayercontrol/dist/"
cp "$SRC/leaflet-groupedlayercontrol/dist/leaflet.groupedlayercontrol.min.js" "$DEST/leaflet-groupedlayercontrol/dist/"

# leaflet-providers — single file, no dist/ subdirectory
mkdir -p "$DEST/leaflet-providers"
cp "$SRC/leaflet-providers/leaflet-providers.js" "$DEST/leaflet-providers/"

# leaflet.fullscreen — no dist/; Control.FullScreen.css references icon-fullscreen.svg
mkdir -p "$DEST/leaflet.fullscreen"
cp "$SRC/leaflet.fullscreen/Control.FullScreen.css" "$DEST/leaflet.fullscreen/"
cp "$SRC/leaflet.fullscreen/Control.FullScreen.js" "$DEST/leaflet.fullscreen/"
cp "$SRC/leaflet.fullscreen/icon-fullscreen.svg" "$DEST/leaflet.fullscreen/"

# leaflet.markercluster — copy only the 3 referenced files
mkdir -p "$DEST/leaflet.markercluster/dist"
cp "$SRC/leaflet.markercluster/dist/MarkerCluster.css" "$DEST/leaflet.markercluster/dist/"
cp "$SRC/leaflet.markercluster/dist/MarkerCluster.Default.css" "$DEST/leaflet.markercluster/dist/"
cp "$SRC/leaflet.markercluster/dist/leaflet.markercluster-src.js" "$DEST/leaflet.markercluster/dist/"

# Leaflet.Deflate — single file
mkdir -p "$DEST/Leaflet.Deflate/dist"
cp "$SRC/Leaflet.Deflate/dist/L.Deflate.js" "$DEST/Leaflet.Deflate/dist/"

# @alcalin/leaflet-tilelayer-wmts — single file
mkdir -p "$DEST/@alcalin/leaflet-tilelayer-wmts/dist"
cp "$SRC/@alcalin/leaflet-tilelayer-wmts/dist/leaflet.tilelayer.wmts.min.js" "$DEST/@alcalin/leaflet-tilelayer-wmts/dist/"
