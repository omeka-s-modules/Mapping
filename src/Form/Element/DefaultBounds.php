<?php
namespace Mapping\Form\Element;

use Laminas\Form\Element;

/**
 * A map-based form element for setting a default bounds value.
 *
 * Supported options:
 *
 * - global_bounds: The fallback bounds string (minx,miny,maxx,maxy) to show
 *   when no user-level value is set. When cleared, the map returns to this
 *   view rather than the world view.
 *
 * - global_basemap_provider: The fallback basemap provider name to use when
 *   the associated basemap select has no value (i.e. the user has chosen to
 *   inherit from a higher-level setting). Defaults to OpenStreetMap.Mapnik.
 *
 * - basemap_select: A CSS selector for the basemap provider select element
 *   associated with this DefaultBounds instance. The map loads with that
 *   select's current provider and updates live when the selection changes.
 *   Omit if no associated select exists.
 */
class DefaultBounds extends Element
{
}
