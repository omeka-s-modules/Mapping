<?php
namespace Mapping\Collecting;

use Collecting\Api\Representation\CollectingPromptRepresentation;
use Collecting\Form\Element;
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
        $element = new Element\PromptHtml($name);
        $element->setValue(sprintf('
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
        $itemData['o-module-mapping:marker'][] = [
            'o-module-mapping:lat' => $postedPrompt['lat'],
            'o-module-mapping:lng' => $postedPrompt['lng'],
            'o-module-mapping:label' => $prompt->text(),
        ];
        return $itemData;
    }
}
