<?php
namespace Mapping\View\Helper;

use Laminas\Form\ElementInterface;
use Laminas\Form\View\Helper\AbstractHelper;

class FormDefaultBounds extends AbstractHelper
{
    public function __invoke(ElementInterface $element)
    {
        return $this->render($element);
    }

    public function render(ElementInterface $element)
    {
        $view = $this->getView();

        $view->headLink()->appendStylesheet($view->assetUrl('node_modules/leaflet/dist/leaflet.css', 'Mapping'));
        $view->headScript()->appendFile($view->assetUrl('node_modules/leaflet/dist/leaflet.js', 'Mapping'));
        $view->headScript()->appendFile($view->assetUrl('node_modules/leaflet-providers/leaflet-providers.js', 'Mapping'));
        $view->headScript()->appendFile($view->assetUrl('js/control.default-view.js', 'Mapping'));
        $view->headScript()->appendFile($view->assetUrl('js/form-element.default-bounds.js', 'Mapping'));

        return sprintf(
            '<div class="mapping-default-bounds" data-global-bounds="%s"><input type="hidden" name="%s" value="%s"><div class="mapping-default-bounds-map" style="height:300px;"></div></div>',
            $view->escapeHtml($element->getOption('global_bounds') ?? ''),
            $view->escapeHtml($element->getName()),
            $view->escapeHtml($element->getValue() ?? '')
        );
    }
}
