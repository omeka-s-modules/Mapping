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
        $params = $this->helpers->get('params');
        $escape = $this->helpers->get('escapeHtml');

        // If the form isn't valid, preserve any passed coordinates.
        $post = $params->fromPost($name, []);
        $lat = isset($post['lat']) ? $post['lat'] : '';
        $lng = isset($post['lng']) ? $post['lng'] : '';

        $element = new PromptMap($name);
        $element->setIsRequired($prompt->required());
        $element->setHtml(sprintf('
            <p>%1$s</p>
            <input type="hidden" class="collecting-map-lat" name="%2$s[lat]" value="%3$s">
            <input type="hidden" class="collecting-map-lng" name="%2$s[lng]" value="%4$s">
            <div class="collecting-map" style="height:300px;"></div>',
            $escape($prompt->text()), $name, $escape($lat), $escape($lng)
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
