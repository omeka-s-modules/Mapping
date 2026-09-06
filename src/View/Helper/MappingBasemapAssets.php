<?php
namespace Mapping\View\Helper;

use Laminas\View\Helper\AbstractHelper;

/**
 * Prepare the assets every Mapping map needs to resolve its basemap.
 *
 * Registers the leaflet-providers library and the shared basemap helper, and
 * passes the configured provider credentials to the latter. Every view that
 * renders a map calls this instead of registering leaflet-providers directly.
 */
class MappingBasemapAssets extends AbstractHelper
{
    /**
     * @var bool Have the credentials already been set this request?
     */
    protected $credentialsSet = false;

    public function __invoke()
    {
        $view = $this->getView();
        $headScript = $view->headScript();

        $headScript->appendFile($view->assetUrl('vendor/leaflet-providers/leaflet-providers.js', 'Mapping'));
        $headScript->appendFile($view->assetUrl('js/mapping-basemap.js', 'Mapping'));

        // Unlike appendFile(), appendScript() does not skip duplicates, and a
        // page may render more than one map. Set the credentials only once.
        if (!$this->credentialsSet) {
            $headScript->appendScript(sprintf(
                'MappingBasemap.credentials.CartoDB.apikey = "%s";'
                    . "\nMappingBasemap.credentials.MapBox.accessToken = \"%s\";",
                $view->escapeJs($view->setting('mapping_carto_api_key') ?? ''),
                $view->escapeJs($view->setting('mapping_mapbox_access_token') ?? '')
            ));
            $this->credentialsSet = true;
        }
    }
}
