<?php
namespace Mapping\Collecting;

use Collecting\Api\Representation\CollectingPromptRepresentation;
use Collecting\MediaType\MediaTypeInterface;
use Laminas\Form\Form;
use Laminas\View\Renderer\PhpRenderer;

class Map implements MediaTypeInterface
{
    public function getLabel()
    {
        return 'Map'; // @translate
    }

    public function prepareForm(PhpRenderer $view)
    {
        $view->headLink()->appendStylesheet($view->assetUrl('vendor/leaflet/leaflet.css', 'Mapping'));
        $view->headScript()->appendFile($view->assetUrl('vendor/leaflet/leaflet.js', 'Mapping'));
        $view->headScript()->appendFile($view->assetUrl('js/mapping-collecting-form.js', 'Mapping'));
        $view->headLink()->appendStylesheet($view->assetUrl('vendor/leaflet.geosearch/assets/css/leaflet.css', 'Mapping'));
        $view->headScript()->appendFile($view->assetUrl('vendor/leaflet.geosearch/dist/bundle.min.js', 'Mapping'));
        $view->headLink()->appendStylesheet($view->assetUrl('css/mapping.css', 'Mapping'));
        $view->formElement()->addType('promptMap', 'formPromptMap');
    }

    public function form(Form $form, CollectingPromptRepresentation $prompt, $name)
    {
        $element = new PromptMap($name);
        $element->setLabel($prompt->text())
            ->setIsRequired($prompt->required());
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
