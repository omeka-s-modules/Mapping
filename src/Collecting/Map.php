<?php
namespace Mapping\Collecting;

use Collecting\Api\Representation\CollectingPromptRepresentation;
use Collecting\MediaType\MediaTypeInterface;
use Zend\Form\Form;
use Zend\View\HelperPluginManager;
use Zend\View\Renderer\PhpRenderer;

class Map implements MediaTypeInterface
{
    protected $helpers;

    public function __construct(HelperPluginManager $helpers)
    {
        $this->helpers = $helpers;
    }

    public function getLabel()
    {
        return 'Map'; // @translate
    }

    public function prepareForm(PhpRenderer $view)
    {
        $view->headLink()->appendStylesheet($view->assetUrl('js/Leaflet/0.7.7/leaflet.css', 'Mapping'));
        $view->headScript()->appendFile($view->assetUrl('js/Leaflet/0.7.7/leaflet.js', 'Mapping'));
        $view->headScript()->appendFile($view->assetUrl('js/mapping-collecting-form.js', 'Mapping'));
    }

    public function form(Form $form, CollectingPromptRepresentation $prompt, $name)
    {
        $escape = $this->helpers->get('escapeHtml');
        $element = new PromptMap($name);
        $element->setIsRequired($prompt->required());
        $element->setHtml(sprintf('
            <p>%1$s</p>
            <input type="hidden" name="%2$s[lat]" class="collecting-map-lat">
            <input type="hidden" name="%2$s[lng]" class="collecting-map-lng">
            <div class="collecting-map" style="height:300px;"></div>',
            $escape($prompt->text()),
            $name
        ));
        $form->add($element);
    }

    public function itemData(array $itemData, $postedPrompt,
        CollectingPromptRepresentation $prompt
    ) {
        $lat = null;
        $lng = null;
        if (isset($postedPrompt['lat']) && is_numeric($postedPrompt['lat'])) {
            $lat = trim($postedPrompt['lat']);
        }
        if (isset($postedPrompt['lng']) && is_numeric($postedPrompt['lng'])) {
            $lng = trim($postedPrompt['lng']);
        }
        if ($lat && $lng) {
            // Add marker data only when latitude and longitude are valid.
            $itemData['o-module-mapping:marker'][] = [
                'o-module-mapping:lat' => $lat,
                'o-module-mapping:lng' => $lng,
                'o-module-mapping:label' => $prompt->text(),
            ];
        }
        return $itemData;
    }
}
